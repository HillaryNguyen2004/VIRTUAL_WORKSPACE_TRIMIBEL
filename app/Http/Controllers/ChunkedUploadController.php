<?php

namespace App\Http\Controllers;

use App\Models\UploadSession;
use App\Models\FileChunk;
use App\Models\PersonalFile;
use App\Models\PersonalFolder;
use App\Services\PersonalFileSearchService;
use App\Services\RagIndexService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ChunkedUploadController extends Controller
{
    const CHUNK_SIZE_MB = 5;
    const MAX_FILE_SIZE_MB = 2048;
    const SESSION_EXPIRY_HOURS = 24;

    public function __construct(
        private PersonalFileSearchService $searchService,
        private RagIndexService $ragIndex
    ) {}


    /**
     * Initiate a chunked upload session
     */
    public function initiate(Request $request)
    {
        $user = auth()->user();
        $data = $request->validate([
            'filename' => ['required', 'string', 'max:255'],
            'total_size' => ['required', 'integer', 'min:1', 'max:' . (self::MAX_FILE_SIZE_MB * 1024 * 1024)],
            'total_chunks' => ['required', 'integer', 'min:1'],
            'folder_id' => ['nullable', 'integer'],
        ]);

        $folderId = $data['folder_id'] ?? null;
        if ($folderId) {
            $folder = PersonalFolder::where('user_id', $user->id)->findOrFail($folderId);
            $folderId = $folder->id;
        }

        $sessionId = Str::random(32);
        $session = UploadSession::create([
            'user_id' => $user->id,
            'session_id' => $sessionId,
            'original_filename' => (string) $data['filename'],
            'total_size' => (int) $data['total_size'],
            'total_chunks' => (int) $data['total_chunks'],
            'folder_id' => $folderId,
            'expires_at' => Carbon::now()->addHours(self::SESSION_EXPIRY_HOURS),
        ]);

        return response()->json([
            'session_id' => $sessionId,
            'upload_id' => $session->id,
            'chunk_size_mb' => self::CHUNK_SIZE_MB,
        ], 201);
    }

    /**
     * Upload a single chunk
     */
    public function uploadChunk(Request $request)
    {
        $user = auth()->user();
        $data = $request->validate([
            'session_id' => ['required', 'string', 'exists:upload_sessions,session_id'],
            'chunk_number' => ['required', 'integer', 'min:1'],
            'chunk' => ['required', 'file'],
        ]);

        $session = UploadSession::where([
            'user_id' => $user->id,
            'session_id' => (string) $data['session_id'],
        ])->firstOrFail();

        if ($session->isExpired()) {
            return response()->json(['message' => 'Upload session expired'], 410);
        }

        if ($session->status === 'completed' || $session->status === 'failed') {
            return response()->json(['message' => 'Upload session is ' . $session->status], 400);
        }

        $chunkNumber = (int) $data['chunk_number'];
        $chunkFile = $request->file('chunk');

        // Find or create chunk record
        $chunk = FileChunk::firstOrCreate(
            [
                'upload_session_id' => $session->id,
                'chunk_number' => $chunkNumber,
            ],
            [
                'chunk_size' => $chunkFile->getSize(),
                'status' => 'pending',
            ]
        );

        // Store chunk temporarily
        $chunkPath = 'upload-chunks/' . $user->id . '/session-' . $session->id . '/chunk-' . $chunkNumber;
        $storedPath = $chunkFile->storeAs(
            dirname($chunkPath),
            basename($chunkPath),
            'local'
        );

        // Calculate hash for integrity verification
        $chunkHash = hash_file('sha256', $chunkFile->getRealPath());

        // Update chunk
        $chunk->update([
            'stored_path' => $storedPath,
            'chunk_hash' => $chunkHash,
            'status' => 'uploaded',
        ]);

        // Increment session counter
        $session->increment('uploaded_chunks');
        $session->update(['status' => 'uploading']);

        return response()->json([
            'chunk_number' => $chunkNumber,
            'hash' => $chunkHash,
            'progress_percent' => $session->progressPercent(),
        ]);
    }

    /**
     * Assemble chunks into final file
     */
    public function assemble(Request $request)
    {
        $user = auth()->user();
        $data = $request->validate([
            'session_id' => ['required', 'string', 'exists:upload_sessions,session_id'],
            'conflict_strategy' => ['nullable', 'in:error,auto_rename,replace'],
            'target_name' => ['nullable', 'string', 'max:255'],
        ]);

        $session = UploadSession::where([
            'user_id' => $user->id,
            'session_id' => (string) $data['session_id'],
        ])->firstOrFail();

        if ($session->isExpired()) {
            return response()->json(['message' => 'Upload session expired'], 410);
        }

        if (!$session->isComplete()) {
            return response()->json([
                'message' => 'Not all chunks uploaded',
                'uploaded' => $session->uploaded_chunks,
                'total' => $session->total_chunks,
            ], 400);
        }

        try {
            // Get all chunks sorted
            $chunks = $session->chunks()
                ->where('status', 'uploaded')
                ->orderBy('chunk_number')
                ->get();

            if ($chunks->count() !== $session->total_chunks) {
                throw new \Exception('Missing chunks');
            }

            // Assemble final file
            $targetFilename = $data['target_name'] ?? $session->original_filename;
            $extension = pathinfo($targetFilename, PATHINFO_EXTENSION);
            $finalFilename = $extension ? Str::uuid() . '.' . $extension : (string) Str::uuid();
            $baseDir = 'personal-files/' . $user->id . '/' . ($session->folder_id ? ('folder-' . $session->folder_id) : 'root');

            $finalPath = $baseDir . '/' . $finalFilename;
            $destinationFile = Storage::disk('local')->path($finalPath);
            @mkdir(dirname($destinationFile), 0755, true);

            $handle = fopen($destinationFile, 'wb');
            if (!$handle) {
                throw new \Exception('Cannot open destination file');
            }

            foreach ($chunks as $chunk) {
                $chunkPath = Storage::disk('local')->path($chunk->stored_path);
                $chunkContent = file_get_contents($chunkPath);
                if ($chunkContent === false) {
                    fclose($handle);
                    throw new \Exception('Cannot read chunk: ' . $chunk->stored_path);
                }
                fwrite($handle, $chunkContent);
            }
            fclose($handle);

            // Get final file size
            $finalFileSize = filesize($destinationFile);

            // Check if filename exists (conflict handling)
            $originalName = $this->normalizeStorageName($targetFilename, 255, 'untitled');
            $conflictStrategy = $this->normalizeConflictStrategy($data['conflict_strategy'] ?? null, 'auto_rename');

            $existingFile = $this->findPersonalFileByName($user->id, $session->folder_id, $originalName);
            if ($existingFile) {
                if ($conflictStrategy === 'error') {
                    @unlink($destinationFile);
                    return response()->json([
                        'message' => __('online_docs.file_name_conflict'),
                    ], 409);
                }

                if ($conflictStrategy === 'replace') {
                    Storage::disk('local')->delete($existingFile->stored_path);
                    $existingFile->delete();
                } else {
                    $originalName = $this->resolveUniquePersonalFileName($user->id, $session->folder_id, $originalName);
                }
            }

            // Build searchable text from the assembled file
            $searchableText = $this->buildSearchableTextFromPath($destinationFile, $originalName);

            // Create PersonalFile record
            $file = PersonalFile::create([
                'user_id' => $user->id,
                'folder_id' => $session->folder_id,
                'stored_path' => $finalPath,
                'original_name' => $originalName,
                'mime_type' => $this->detectMimeType($finalFilename),
                'size' => $finalFileSize,
                'searchable_text' => $searchableText,
            ]);

            // Mark chunks as assembled and clean up
            $session->chunks()->update(['status' => 'assembled']);
            $this->cleanupChunks($session);

            $session->update([
                'status' => 'completed',
                'assembled_path' => $finalPath,
            ]);

            // Auto-index into ChromaDB (best-effort, non-blocking)
            $this->ragIndex->indexFile($finalPath, 'personal_file_' . $file->id, 'personal_file');

            return response()->json([
                'file_id' => $file->id,
                'name' => $file->original_name,
                'size' => $file->size,
                'message' => __('online_docs.upload_done'),
            ]);
        } catch (\Exception $e) {
            $session->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Assembly failed: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get session status
     */
    public function status(Request $request)
    {
        $user = auth()->user();
        $data = $request->validate([
            'session_id' => ['required', 'string', 'exists:upload_sessions,session_id'],
        ]);

        $session = UploadSession::where([
            'user_id' => $user->id,
            'session_id' => (string) $data['session_id'],
        ])->firstOrFail();

        $uploadedChunks = $session->chunks()
            ->whereIn('status', ['uploaded', 'verified', 'assembled'])
            ->pluck('chunk_number')
            ->toArray();

        return response()->json([
            'session_id' => $session->session_id,
            'status' => $session->status,
            'uploaded_chunks' => count($uploadedChunks),
            'total_chunks' => $session->total_chunks,
            'progress_percent' => $session->progressPercent(),
            'uploaded_chunk_numbers' => $uploadedChunks,
            'is_expired' => $session->isExpired(),
        ]);
    }

    /**
     * Cancel/cleanup session
     */
    public function cancel(Request $request)
    {
        $user = auth()->user();
        $data = $request->validate([
            'session_id' => ['required', 'string', 'exists:upload_sessions,session_id'],
        ]);

        $session = UploadSession::where([
            'user_id' => $user->id,
            'session_id' => (string) $data['session_id'],
        ])->firstOrFail();

        $this->cleanupChunks($session);
        $session->delete();

        return response()->json(['message' => 'Session cancelled']);
    }

    // ===== Helper Methods =====

    private function cleanupChunks(UploadSession $session): void
    {
        foreach ($session->chunks as $chunk) {
            if ($chunk->stored_path) {
                Storage::disk('local')->delete($chunk->stored_path);
            }
        }
    }

    private function normalizeStorageName(string $name, int $maxLen, string $default): string
    {
        $name = trim((string) $name);
        if (empty($name)) {
            return $default;
        }
        return mb_substr($name, 0, $maxLen, 'UTF-8');
    }

    private function normalizeConflictStrategy(?string $strategy, string $default = 'error'): string
    {
        return in_array($strategy, ['error', 'auto_rename', 'replace'], true) ? $strategy : $default;
    }

    private function findPersonalFileByName($userId, $folderId, $name)
    {
        return \App\Models\PersonalFile::where('user_id', $userId)
            ->where('folder_id', $folderId)
            ->whereRaw('LOWER(original_name) = ?', [mb_strtolower($name)])
            ->first();
    }

    private function resolveUniquePersonalFileName($userId, $folderId, $name): string
    {
        $pathInfo = pathinfo($name);
        $baseName = $pathInfo['filename'] ?? 'untitled';
        $extension = $pathInfo['extension'] ?? '';
        $counter = 1;

        while (true) {
            $newName = $extension ? $baseName . '(' . $counter . ').' . $extension : $baseName . '(' . $counter . ')';
            if (!$this->findPersonalFileByName($userId, $folderId, $newName)) {
                return $newName;
            }
            $counter++;
        }
    }

    private function buildSearchableTextFromPath(string $absolutePath, string $originalName): string
    {
        $ext = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
        $content = '';

        try {
            if (in_array($ext, ['txt', 'md'], true)) {
                $content = file_get_contents($absolutePath) ?: '';
            } elseif ($ext === 'docx') {
                $zip = new \ZipArchive();
                if ($zip->open($absolutePath) === true) {
                    $xml = $zip->getFromName('word/document.xml');
                    $zip->close();
                    if ($xml !== false) {
                        $xml = preg_replace('/<[^>]+>/', ' ', $xml) ?? '';
                        $content = html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    }
                }
            } elseif ($ext === 'xlsx') {
                $zip = new \ZipArchive();
                if ($zip->open($absolutePath) === true) {
                    $xml = $zip->getFromName('xl/sharedStrings.xml');
                    $zip->close();
                    if ($xml !== false) {
                        preg_match_all('/<t[^>]*>([^<]*)<\/t>/', $xml, $m);
                        $content = implode(' ', $m[1]);
                    }
                }
            }
        } catch (\Throwable) {
            $content = '';
        }

        return mb_substr(trim($originalName . "\n" . $content), 0, 65535);
    }

    private function detectMimeType(string $filename): string
    {
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'txt' => 'text/plain',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            '7z' => 'application/x-7z-compressed',
        ];

        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return $mimeTypes[$ext] ?? 'application/octet-stream';
    }
}
