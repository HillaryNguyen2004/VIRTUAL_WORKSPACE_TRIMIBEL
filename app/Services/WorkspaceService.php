<?php

namespace App\Services;

use App\Models\AIWorkspace;
use App\Models\AIWorkspaceFile;
use Illuminate\Http\UploadedFile;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ExecutableFinder;
// use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Symfony\Component\Process\Exception\ProcessFailedException;

class WorkspaceService
{
    /**
     * Get all workspaces with filtering, searching and sorting
     */
    public function getAllWorkspaces(\Illuminate\Http\Request $request)
    {
        $user = auth()->user();
        $query = AIWorkspace::query();

        // Admin can see all active workspaces, users see their own or public ones
        if (!$user->hasRole('admin')) {
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('visibility', 'public');
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
     * Create a new workspace with folder
     */
    public function createWorkspace(array $data): AIWorkspace
    {
        $slug = AIWorkspace::generateSlug($data['name']);
        $folderPath = AIWorkspace::getStoragePath() . '/' . $slug;

        $workspace = AIWorkspace::create([
            'user_id' => $data['user_id'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'slug' => $slug,
            'visibility' => $data['visibility'] ?? 'private',
            'folder_path' => $folderPath,
            'status' => 'active',
        ]);

        // Create the folder
        $workspace->createFolder();

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
        ]);

        return $workspace;
    }

    /**
     * Delete workspace with all files and folder
     */
    public function deleteWorkspace(AIWorkspace $workspace): bool
    {
        // Delete all files from database
        $workspace->files()->delete();

        // Delete folder from filesystem
        if (is_dir($workspace->folder_path)) {
            $this->deleteDirectory($workspace->folder_path);
        }

        // Delete workspace record
        return $workspace->delete();
    }

    /**
     * Upload files to workspace
     */
    public function uploadFiles(AIWorkspace $workspace, array $files): array
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

                $workspaceFile = $this->storeFile($workspace, $file);
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
     * Store a single file
     */
    private function storeFile(AIWorkspace $workspace, UploadedFile $file): AIWorkspaceFile
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $originalName = $file->getClientOriginalName();
        $temporaryPath = $file->getRealPath();

        // Validate file type
        if (!AIWorkspaceFile::isSupportedFormat($extension)) {
            throw new \Exception(
                "File type '{$extension}' is not supported. Supported types: " . implode(', ', AIWorkspaceFile::supportedFormats())
            );
        }

        $fileSize = $file->getSize();
        if (($fileSize === false || $fileSize === null) && $temporaryPath && is_readable($temporaryPath)) {
            $fileSize = filesize($temporaryPath);
        }

        if ($fileSize === false || $fileSize === null) {
            throw new \Exception('Cannot determine uploaded file size');
        }

        // Validate file size (max 50MB per file)
        $maxSize = 50 * 1024 * 1024;
        if ($fileSize > $maxSize) {
            throw new \Exception("File size exceeds maximum limit of 50MB");
        }

        // Check if workspace folder exists and is writable
        if (!is_dir($workspace->folder_path)) {
            throw new \Exception("Workspace folder does not exist: " . $workspace->folder_path);
        }

        if (!is_writable($workspace->folder_path)) {
            throw new \Exception("Workspace folder is not writable: " . $workspace->folder_path);
        }

        // Capture metadata before the temporary upload file is moved.
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

        // Generate unique filename
        $fileName = Str::uuid() . '.' . $extension;
        $filePath = $workspace->folder_path . '/' . $fileName;

        // Store file - use stream if move fails
        try {
            $file->move($workspace->folder_path, $fileName);
        } catch (\Exception $e) {
            if (!$temporaryPath || !is_readable($temporaryPath)) {
                throw new \Exception('Cannot read uploaded file', 0, $e);
            }

            $content = file_get_contents($temporaryPath);
            if ($content === false) {
                throw new \Exception("Cannot read uploaded file");
            }

            $written = file_put_contents($filePath, $content);
            if ($written === false) {
                throw new \Exception("Cannot write file to workspace folder: " . $workspace->folder_path);
            }
        }

        // Verify file was actually written
        if (!file_exists($filePath)) {
            throw new \Exception("File was not saved successfully: " . $filePath);
        }

        try {
            return AIWorkspaceFile::create([
                'workspace_id' => $workspace->id,
                'file_name' => $fileName,
                'original_name' => $originalName,
                'file_path' => $filePath,
                'mime_type' => $mimeType,
                'file_size' => $fileSize,
                'ingest_status' => 'pending',
            ]);
        } catch (\Throwable $e) {
            if (file_exists($filePath)) {
                @unlink($filePath);
            }

            throw $e;
        }
    }

    /**
     * Run ingest for workspace files
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
     * Ingest a single file using Python script
     */
    private function ingestFile(AIWorkspace $workspace, AIWorkspaceFile $file): array
    {
        try {
            // Update status to processing
            $file->update(['ingest_status' => 'processing']);

            // Resolve interpreter + build command
            $pythonBinary = $this->resolvePythonBinary();
            $pythonScript = base_path('chatbot_service/cli/ingest_workspace.py');
            $workspaceDir = $workspace->folder_path;
            $targetFile = $file->file_path;
            $processEnv = [
                'PYTHONPATH' => base_path('chatbot_service'),
            ];

            // Run the ingest process inside chatbot_service so relative imports/config work.
            $process = new Process(
                [$pythonBinary, $pythonScript, $workspaceDir, $targetFile],
                base_path('chatbot_service'),
                $processEnv
            );
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
            base_path('.venv/bin/python'),
            base_path('chatbot_service/.venv/bin/python'),
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
     * Delete a workspace file
     */
    public function deleteFile(AIWorkspaceFile $file): bool
    {
        $workspace = $file->workspace;

        // Delete physical file
        if (file_exists($file->file_path)) {
            unlink($file->file_path);
        }

        // Delete database record
        $file->delete();

        // Update workspace stats
        $workspace->updateFileCount();
        $workspace->updateStorageSize();

        return true;
    }

    /**
     * Get workspace statistics
     */
    public function getWorkspaceStats(AIWorkspace $workspace): array
    {
        $files = $workspace->files()->get();

        return [
            'total_files' => $files->count(),
            'total_size' => $files->sum('file_size'),
            'ingested_files' => $files->where('ingest_status', 'completed')->count(),
            'pending_files' => $files->where('ingest_status', 'pending')->count(),
            'failed_files' => $files->where('ingest_status', 'failed')->count(),
            'total_chunks' => $files->sum('chunk_count'),
            'storage_used_mb' => round($files->sum('file_size') / (1024 * 1024), 2),
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
