<?php

namespace App\Services;

use App\Models\AIWorkspace;
use App\Models\AIWorkspaceFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ExecutableFinder;
// use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Symfony\Component\Process\Exception\ProcessFailedException;

class AIWorkspaceService
{
    /**
     * Get visible active workspaces for a user (same visibility rules as AI Workspace listing).
     */
    public function getVisibleWorkspacesForUser($user)
    {
        $query = AIWorkspace::query()->active();

        // Private workspaces are owner-only regardless of role.
        // Admins see all public/team workspaces but only their own private ones.
        if ($user->hasRole('admin')) {
            $query->where(function ($q) use ($user) {
                $q->where(function ($q2) use ($user) {
                    $q2->where('visibility', 'private')->where('user_id', $user->id);
                })->orWhereIn('visibility', ['public', 'team']);
            });
        } else {
            $teamUserIds = $this->getTeamScopeUserIds($user);
            $query->where(function ($q) use ($user, $teamUserIds) {
                $q->where(function ($q2) use ($user) {
                    $q2->where('visibility', 'private')->where('user_id', $user->id);
                })->orWhere('visibility', 'public')
                    ->orWhere(function ($teamQ) use ($teamUserIds) {
                        $teamQ->where('visibility', 'team')
                            ->whereIn('user_id', $teamUserIds);
                    });
            });
        }

        return $query
            ->orderBy('created_at', 'desc')
            ->get(['id', 'name', 'slug', 'visibility']);
    }

    /**
     * Get all workspaces with filtering, searching and sorting
     */
    public function getAllWorkspaces(\Illuminate\Http\Request $request)
    {
        $user = auth()->user();
        $query = AIWorkspace::query();

        // Private workspaces are owner-only regardless of role.
        // Admins see all public/team workspaces but only their own private ones.
        if ($user->hasRole('admin')) {
            $query->where(function ($q) use ($user) {
                $q->where(function ($q2) use ($user) {
                    $q2->where('visibility', 'private')->where('user_id', $user->id);
                })->orWhereIn('visibility', ['public', 'team']);
            });
        } else {
            $teamUserIds = $this->getTeamScopeUserIds($user);
            $query->where(function ($q) use ($user, $teamUserIds) {
                $q->where(function ($q2) use ($user) {
                    $q2->where('visibility', 'private')->where('user_id', $user->id);
                })->orWhere('visibility', 'public')
                    ->orWhere(function ($teamQ) use ($teamUserIds) {
                        $teamQ->where('visibility', 'team')
                            ->whereIn('user_id', $teamUserIds);
                    });
            });
        }

        $query->active();

        // Search by name
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Filter by visibility
        if ($request->filled('visibility')) {
            $query->where('visibility', $request->visibility);
        }

        // Sort
        $sort = $request->get('sort', 'desc');
        $query->orderBy('created_at', $sort === 'asc' ? 'asc' : 'desc');

        /** @var \Illuminate\Pagination\LengthAwarePaginator $paginator */
        $paginator = $query->paginate($request->get('per_page', 10));

        return $paginator->withQueryString();
    }

    /**
     * Resolve team scope for a user:
     * - staff leader: self + members
     * - team member/substaff: leader + peers + self
     * - user without team: only self
     */
    private function getTeamScopeUserIds($user): array
    {
        $leaderId = $user->team_leader_id ?: $user->id;

        $ids = \App\Models\User::query()
            ->where('id', $leaderId)
            ->orWhere('team_leader_id', $leaderId)
            ->pluck('id')
            ->all();

        if (!in_array($user->id, $ids, true)) {
            $ids[] = $user->id;
        }

        return $ids;
    }

    /**
     * Create a new workspace with S3 storage
     */
    public function createWorkspace(array $data): AIWorkspace
    {
        $slug = AIWorkspace::generateSlug($data['name']);

        $workspace = AIWorkspace::create([
            'user_id' => $data['user_id'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'slug' => $slug,
            'visibility' => $data['visibility'] ?? 'private',
            'allow_others_upload' => (bool) ($data['allow_others_upload'] ?? false),
            'folder_path' => '',  // Temporary placeholder
            'status' => 'active',
        ]);

        // Set S3 prefix using workspace ID
        $s3Prefix = 'ai-workspace/' . $workspace->id;
        $workspace->folder_path = $s3Prefix;
        $workspace->save();

        // Ensure vector workspace folders exist for Chroma
        $this->ensureVectorWorkspaceFolders($workspace);

        return $workspace;
    }

    /**
     * Update workspace
     */
    public function updateWorkspace(AIWorkspace $workspace, array $data): AIWorkspace
    {
        $workspace->update([
            'name' => $data['name'] ?? $workspace->name,
            'description' => $data['description'] ?? $workspace->description,
            'visibility' => $data['visibility'] ?? $workspace->visibility,
            'allow_others_upload' => array_key_exists('allow_others_upload', $data)
                ? (bool) $data['allow_others_upload']
                : $workspace->allow_others_upload,
        ]);

        // Ensure matching vector workspace folders exist when visibility changes.
        $this->ensureVectorWorkspaceFolders($workspace);

        return $workspace;
    }

    /**
     * Delete workspace with all files from S3
     */
    public function deleteWorkspace(AIWorkspace $workspace): bool
    {
        // Delete all files from S3
        $s3Prefix = $workspace->folder_path;
        try {
            $files = Storage::disk('s3')->files($s3Prefix);
            foreach ($files as $file) {
                Storage::disk('s3')->delete($file);
            }
        } catch (\Exception $e) {
            \Log::warning('Failed to delete workspace files from S3', [
                'prefix' => $s3Prefix,
                'error' => $e->getMessage()
            ]);
        }

        // Delete database records
        $workspace->files()->delete();

        // Delete workspace record
        return $workspace->delete();
    }

    /**
     * Upload files to workspace
     */
    public function uploadFiles(AIWorkspace $workspace, array $files, ?int $uploadedBy = null): array
    {
        $uploaded = [];
        $errors = [];

        if (empty($files)) {
            $errors[] = 'No files provided';
            return [
                'uploaded' => $uploaded,
                'errors' => $errors,
            ];
        }

        foreach ($files as $file) {
            try {
                if (!$file instanceof UploadedFile) {
                    throw new \Exception("Invalid file object");
                }

                $workspaceFile = $this->storeFile($workspace, $file, $uploadedBy);
                $uploaded[] = $workspaceFile;
            } catch (\Exception $e) {
                $errors[] = [
                    'file' => isset($file) ? $file->getClientOriginalName() : 'Unknown',
                    'error' => $e->getMessage(),
                ];
                \Log::error('File upload error', [
                    'workspace_id' => $workspace->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        // Update workspace stats only if there were successful uploads
        if (!empty($uploaded)) {
            $workspace->updateFileCount();
            $workspace->updateStorageSize();
        }

        return [
            'uploaded' => $uploaded,
            'errors' => $errors,
        ];
    }

    /**
     * Store a single file to AWS S3
     */
    private function storeFile(AIWorkspace $workspace, UploadedFile $file, ?int $uploadedBy = null): AIWorkspaceFile
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $originalName = $file->getClientOriginalName();

        // Validate file type
        if (!AIWorkspaceFile::isSupportedFormat($extension)) {
            throw new \Exception(
                "File type '{$extension}' is not supported. Supported types: " . implode(', ', AIWorkspaceFile::supportedFormats())
            );
        }

        $fileSize = $file->getSize();
        if ($fileSize === false || $fileSize === null) {
            throw new \Exception('Cannot determine uploaded file size');
        }

        // Validate file size (max 512MB per file - matches server config in .htaccess)
        $maxSize = 512 * 1024 * 1024;
        if ($fileSize > $maxSize) {
            throw new \Exception("File size exceeds maximum limit of 512MB");
        }

        // Capture metadata before upload
        try {
            $mimeType = $file->getMimeType();
        } catch (\Throwable $e) {
            $mimeType = null;
        }

        if (empty($mimeType)) {
            $mimeType = $file->getClientMimeType();
        }

        if (empty($mimeType)) {
            $mimeType = match ($extension) {
                'pdf' => 'application/pdf',
                'txt' => 'text/plain',
                'md' => 'text/markdown',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                default => 'application/octet-stream'
            };
        }

        // Normalize folder path - migrate old local paths to S3 prefix format
        $folderPath = $workspace->folder_path;
        if (strpos($folderPath, '/') === 0 || strpos($folderPath, 'Users/') === 0 || strpos($folderPath, 'chatbot_service') === 0) {
            // This is a local filesystem path, convert to S3 prefix
            $folderPath = 'ai-workspace/' . $workspace->id;
            $workspace->folder_path = $folderPath;
            $workspace->save();
        }

        // Generate unique filename and S3 path
        $fileName = Str::uuid() . '.' . $extension;
        $s3Path = $folderPath . '/' . $fileName;  // e.g., ai-workspace/{id}/{file-uuid}.ext

        // Upload to S3 using a stream to avoid loading the entire file into memory
        try {
            $stream = fopen($file->getRealPath(), 'rb');
            if ($stream === false) {
                throw new \Exception("Cannot read uploaded file");
            }

            try {
                Storage::disk('s3')->put($s3Path, $stream, 'private');
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }
        } catch (\Exception $e) {
            throw new \Exception("Failed to upload file to S3: " . $e->getMessage(), 0, $e);
        }

        // Verify file exists in S3
        if (!Storage::disk('s3')->exists($s3Path)) {
            throw new \Exception("File was not saved successfully to S3: " . $s3Path);
        }

        try {
            return AIWorkspaceFile::create([
                'workspace_id' => $workspace->id,
                'uploaded_by' => $uploadedBy,
                'file_name' => $fileName,
                'original_name' => $originalName,
                'file_path' => $s3Path,  // Store S3 path
                'mime_type' => $mimeType,
                'file_size' => $fileSize,
                'chunk_count' => 0,
                'ingest_status' => 'pending',
            ]);
        } catch (\Throwable $e) {
            // Clean up S3 file on database error
            try {
                Storage::disk('s3')->delete($s3Path);
            } catch (\Throwable $deleteError) {
                \Log::warning('Failed to cleanup S3 file on error', ['path' => $s3Path]);
            }

            throw $e;
        }
    }

    /**
     * Ingest files to workspace
     */
    public function ingestWorkspace(AIWorkspace $workspace): array
    {
        $results = [];
        $filesToIngest = $workspace->files()
            ->where(function ($query) {
                $query->whereIn('ingest_status', ['pending', 'failed'])
                    ->orWhere(function ($completedQuery) {
                        $completedQuery
                            ->where('ingest_status', 'completed')
                            ->where('chunk_count', 0);
                    });
            })
            ->get();

        foreach ($filesToIngest as $file) {
            try {
                $result = $this->ingestFile($workspace, $file);
                $results[] = $result;
            } catch (\Exception $e) {
                $file->update([
                    'ingest_status' => 'failed',
                    'ingest_error' => $e->getMessage(),
                ]);
                $results[] = [
                    'file' => $file->original_name,
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ];
            }
        }

        // Update last ingested time
        $workspace->update(['last_ingested_at' => now()]);

        return $results;
    }

    /**
     * Ingest a single file by ID (for per-file retry from UI)
     */
    public function ingestSingleFile(AIWorkspace $workspace, AIWorkspaceFile $file): array
    {
        $result = $this->ingestFile($workspace, $file);
        $workspace->update(['last_ingested_at' => now()]);
        return $result;
    }

    /**
     * Ingest a single file from S3 using Python script
     */
    private function ingestFile(AIWorkspace $workspace, AIWorkspaceFile $file): array
    {
        $tempFilePath = null;

        try {
            // Update status to processing
            $file->update(['ingest_status' => 'processing']);

            // Download file from S3 to temporary location for processing
            $s3Path = $file->file_path;
            $fileContent = Storage::disk('s3')->get($s3Path);
            $tempFilePath = sys_get_temp_dir() . '/' . Str::uuid() . '.' . pathinfo($s3Path, PATHINFO_EXTENSION);
            file_put_contents($tempFilePath, $fileContent);

            // Resolve interpreter + build command
            $pythonBinary = $this->resolvePythonBinary();
            $pythonScript = base_path('chatbot_service/cli/ingest_workspace.py');
            $targetFile = $tempFilePath;  // Temporary local copy
            $originalName = (string) ($file->original_name ?? $file->file_name ?? basename((string) $s3Path));
            $workspaceScope = $this->resolveWorkspaceVectorScope($workspace);
            $processEnv = [
                'PYTHONPATH' => base_path('chatbot_service'),
            ];

            // Run the ingest process inside chatbot_service so relative imports/config work.
            // Use temp directory instead of S3 path for workspace directory
            $process = new Process(
                [$pythonBinary, $pythonScript, sys_get_temp_dir(), $targetFile, $workspaceScope, $originalName, $file->file_name],
                base_path('chatbot_service'),
                $processEnv
            );
            $process->setTimeout(300);
            $process->run();

            if (!$process->isSuccessful()) {
                $stderr = trim($process->getErrorOutput());
                $stdout = trim($process->getOutput());

                // Ignore known telemetry noise so the real ingest error is shown.
                $stderrLines = array_filter(
                    preg_split('/\r\n|\r|\n/', $stderr) ?: [],
                    static fn(string $line): bool => stripos($line, 'Failed to send telemetry event') === false
                );
                $cleanStderr = trim(implode(PHP_EOL, $stderrLines));

                $details = '';
                if (preg_match('/^Error:\s*(.+)$/mi', $stdout, $matches)) {
                    $details = trim($matches[1]);
                } elseif ($stdout !== '') {
                    $details = $stdout;
                } elseif ($cleanStderr !== '') {
                    $details = $cleanStderr;
                } else {
                    $details = 'Unknown ingest error';
                }

                throw new \Exception('Ingest process failed: ' . $details);
            }

            // Parse output to get chunk count
            $chunkCount = $this->parseChunkCount($process->getOutput());

            // Update file status
            $file->update([
                'ingest_status' => 'completed',
                'chunk_count' => $chunkCount,
                'ingested_at' => now(),
                'ingest_error' => null,
            ]);

            return [
                'file' => $file->original_name,
                'status' => 'completed',
                'chunks' => $chunkCount,
            ];

        } catch (\Exception $e) {
            $file->update([
                'ingest_status' => 'failed',
                'ingest_error' => $e->getMessage(),
            ]);

            throw $e;
        } finally {
            // Clean up temporary file
            if ($tempFilePath && file_exists($tempFilePath)) {
                @unlink($tempFilePath);
            }
        }
    }

    /**
     * Resolve python binary for RAG ingest.
     */
    private function resolvePythonBinary(): string
    {
        $configuredPython = getenv('RAG_PYTHON_BIN');
        if (is_string($configuredPython) && $configuredPython !== '') {
            return $configuredPython;
        }

        $venvCandidates = [
            base_path('chatbot_service/.venv/bin/python3'),
            base_path('chatbot_service/.venv/bin/python'),
            base_path('.venv/bin/python3'),
            base_path('.venv/bin/python'),
            '/opt/homebrew/bin/python3',
            '/usr/bin/python3',
        ];

        foreach ($venvCandidates as $candidate) {
            if (is_executable($candidate)) {
                return $candidate;
            }
        }

        $finder = new ExecutableFinder();
        $python3 = $finder->find('python3');
        if ($python3 !== null) {
            return $python3;
        }

        $python = $finder->find('python');
        if ($python !== null) {
            return $python;
        }

        throw new \RuntimeException(
            'Python interpreter not found. Set RAG_PYTHON_BIN in .env or install python3.'
        );
    }

    private function normalizeRoleForRag(?string $role): string
    {
        $normalized = strtolower((string) $role);

        return match ($normalized) {
            'admin', 'subadmin' => 'admin',
            'staff', 'substaff' => 'staff',
            default => 'user',
        };
    }

    private function resolveWorkspaceVectorScope(AIWorkspace $workspace): string
    {
        if ($workspace->visibility === 'public') {
            return 'public';
        }

        return (string) $workspace->id;
    }

    private function ensureVectorWorkspaceFolders(AIWorkspace $workspace): void
    {
        $scope = $this->resolveWorkspaceVectorScope($workspace);

        $chromaWorkspacePath = base_path('chatbot_service/var/chroma_db/workspaces/' . $scope);

        if (!is_dir($chromaWorkspacePath)) {
            @mkdir($chromaWorkspacePath, 0755, true);
        }
    }

    /**
     * Parse chunk count from ingest script output
     */
    private function parseChunkCount(string $output): int
    {
        // Prefer the latest per-file line: "Added N chunks"
        if (preg_match_all('/Added (\d+) chunks/', $output, $matches) && !empty($matches[1])) {
            return (int) end($matches[1]);
        }
        // Look for pattern like "Total chunks: 25"
        if (preg_match('/Total chunks:\s*(\d+)/', $output, $matches)) {
            return (int) $matches[1];
        }
        return 0;
    }

    /**
     * Delete a workspace file from S3 and vector DB
     */
    public function deleteFile(AIWorkspaceFile $file): bool
    {
        $workspace = $file->workspace;

        // Delete chunks from vector DB before removing the DB record
        $this->deleteFileFromVectorDb($workspace, $file);

        // Delete from S3
        try {
            Storage::disk('s3')->delete($file->file_path);
        } catch (\Exception $e) {
            \Log::warning('Failed to delete file from S3', [
                'path' => $file->file_path,
                'error' => $e->getMessage()
            ]);
        }

        // Delete database record
        $file->delete();

        // Update workspace stats
        $workspace->updateFileCount();
        $workspace->updateStorageSize();

        return true;
    }

    /**
     * Remove a file's chunks from the ChromaDB vector store.
     */
    private function deleteFileFromVectorDb(AIWorkspace $workspace, AIWorkspaceFile $file): void
    {
        try {
            $pythonBinary = $this->resolvePythonBinary();
            $pythonScript = base_path('chatbot_service/cli/delete_file_chunks.py');
            $workspaceScope = $this->resolveWorkspaceVectorScope($workspace);

            $process = new Process(
                [$pythonBinary, $pythonScript, $file->file_name, $workspaceScope],
                base_path('chatbot_service'),
                ['PYTHONPATH' => base_path('chatbot_service')]
            );
            $process->setTimeout(60);
            $process->run();

            if (!$process->isSuccessful()) {
                \Log::warning('Failed to delete file chunks from vector DB', [
                    'file' => $file->file_name,
                    'workspace' => $workspaceScope,
                    'stderr' => trim($process->getErrorOutput()),
                ]);
            } else {
                \Log::info('Deleted file chunks from vector DB', [
                    'file' => $file->file_name,
                    'workspace' => $workspaceScope,
                    'output' => trim($process->getOutput()),
                ]);
            }
        } catch (\Exception $e) {
            \Log::warning('Exception while deleting file chunks from vector DB', [
                'file' => $file->file_name,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get workspace statistics
     */
    public function getWorkspaceStats(AIWorkspace $workspace): array
    {
        $files = $workspace->files()->get();

        return [
            'total_files' => $files->count(),
            'storage_size' => $workspace->storage_size,
            'ingested_files' => $files->where('ingest_status', 'completed')->count(),
            'pending_files' => $files->where('ingest_status', 'pending')->count(),
            'failed_files' => $files->where('ingest_status', 'failed')->count(),
            'total_chunks' => $files->sum('chunk_count'),
        ];
    }

    /**
     * Recursively delete directory
     */
    private function deleteDirectory(string $path): bool
    {
        if (!is_dir($path)) {
            return false;
        }

        $files = array_diff(scandir($path), ['.', '..']);
        foreach ($files as $file) {
            $filePath = $path . '/' . $file;
            if (is_dir($filePath)) {
                $this->deleteDirectory($filePath);
            } else {
                unlink($filePath);
            }
        }

        return rmdir($path);
    }
}
