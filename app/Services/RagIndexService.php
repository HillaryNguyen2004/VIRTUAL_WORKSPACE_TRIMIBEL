<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;


class RagIndexService
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.chatbot.base_url', 'http://127.0.0.1:8002'), '/');
    }

    /**
     * Auto-index an online Document into ChromaDB via S3 key.
     */
    public function indexDocument(Document $document, ?string $userId = null): void
    {
        if ($this->baseUrl === '') return;

        $s3Key        = $document->docx_path ?? $document->xlsx_path ?? $document->pptx_path ?? null;
        $originalName = $document->title ?? $document->name ?? null;

        if ($s3Key && Storage::disk()->exists($s3Key)) {
            $this->ingestS3(
                s3Key: $s3Key,
                workspaceId: 'online_doc_' . $document->id,
                originalName: $originalName,
                storageFileName: basename($s3Key),
                userId: $userId,
            );
            return;
        }

        $dir = 'documents/' . $document->id;
        foreach (Storage::disk()->files($dir) as $file) {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($ext, ['pdf', 'docx', 'xlsx', 'pptx', 'txt', 'md'], true)) {
                $this->ingestS3(
                    s3Key: $file,
                    workspaceId: 'online_doc_' . $document->id,
                    originalName: $originalName,
                    storageFileName: basename($file),
                    userId: $userId,
                );
                return;
            }
        }
    }

    /**
     * Remove an online Document from ChromaDB.
     */
    public function deleteDocument(Document $document, ?string $userId = null): void
    {
        $s3Key       = $document->docx_path ?? $document->xlsx_path ?? $document->pptx_path ?? null;
        $storageFile = $s3Key ? basename($s3Key) : null;

        if ($storageFile) {
            $this->removeDocument(
                storageFile: $storageFile,
                workspaceId: 'online_doc_' . $document->id,
                userId: $userId,
            );
        }
    }

    /**
     * Index any file stored under the default disk via /ingest-s3.
     */
    public function indexFile(
        string $storedPath,
        string $workspaceId,
        string $originalName = '',
        ?string $userId = null,
    ): int {
        if (!Storage::disk()->exists($storedPath)) {
            Log::warning('RagIndexService::indexFile – file not found', ['path' => $storedPath]);
            return 0;
        }

        return $this->ingestS3(
            s3Key: $storedPath,
            workspaceId: $workspaceId,
            originalName: $originalName ?: basename($storedPath),
            storageFileName: basename($storedPath),
            userId: $userId,
        );
    }

    /**
     * Batch-index multiple files (passed as s3_key items).
     *
     * @param  array  $items  [['s3_key' => '...', 'original_name' => '...', 'storage_file_name' => '...'], ...]
     */
    public function indexBatch(array $items, string $workspaceId, ?string $userId = null): void
    {
        if ($this->baseUrl === '' || empty($items)) return;

        try {
            $response = Http::timeout(300)->post($this->baseUrl . '/agent/ingest-batch', [
                'items'        => $items,
                'workspace_id' => $workspaceId,
                'user_id'      => $userId,
            ]);

            if ($response->failed()) {
                Log::warning('RagIndexService::indexBatch failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('RagIndexService::indexBatch exception', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Remove a document from ChromaDB by storage_file + workspace_id.
     */
    public function removeDocument(string $storageFile, string $workspaceId, ?string $userId = null): void
    {
        if ($this->baseUrl === '') return;

        try {
            $response = Http::timeout(30)->delete($this->baseUrl . '/agent/document', [
                'storage_file' => $storageFile,
                'workspace_id' => $workspaceId,
                'user_id'      => $userId,
            ]);

            if ($response->failed()) {
                Log::warning('RagIndexService::removeDocument failed', [
                    'status'       => $response->status(),
                    'body'         => $response->body(),
                    'storage_file' => $storageFile,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('RagIndexService::removeDocument exception', [
                'storage_file' => $storageFile,
                'error'        => $e->getMessage(),
            ]);
        }
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function ingestS3(
        string $s3Key,
        string $workspaceId,
        ?string $originalName = null,
        ?string $storageFileName = null,
        ?string $userId = null,
    ): int {
        $response = Http::timeout(600)->post($this->baseUrl . '/ingest-s3', [
            's3_key'            => $s3Key,
            'workspace_id'      => $workspaceId,
            'original_name'     => $originalName,
            'storage_file_name' => $storageFileName,
            'user_id'           => $userId,
        ]);

        if ($response->failed()) {
            throw new \RuntimeException(
                'Chatbot ingest failed (HTTP ' . $response->status() . '): ' . $response->body()
            );
        }

        return (int) ($response->json('total_chunks') ?? 0);
    }
}
