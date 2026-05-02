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
     * Auto-index an online Document into ChromaDB.
     * Tries docx path first, then falls back to the document storage directory.
     */
    public function indexDocument(Document $document): void
    {
        if ($this->baseUrl === '') return;

        // Prefer the physical docx/xlsx/pptx file if available
        $filePath = $document->docx_path ?? $document->xlsx_path ?? $document->pptx_path ?? null;

        if ($filePath && Storage::disk('local')->exists($filePath)) {
            $this->indexFile(
                $filePath,
                'online_doc_' . $document->id,
                'online_doc'
            );
            return;
        }

        // Fall back: look for any supported file inside documents/{id}/
        $dir = storage_path('app/documents/' . $document->id);
        if (is_dir($dir)) {
            foreach (scandir($dir) as $f) {
                $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
                if (in_array($ext, ['pdf', 'docx', 'xlsx', 'txt', 'md'], true)) {
                    $this->indexFile(
                        'documents/' . $document->id . '/' . $f,
                        'online_doc_' . $document->id,
                        'online_doc'
                    );
                    return;
                }
            }
        }
    }

    /**
     * Remove an online Document from ChromaDB.
     */
    public function deleteDocument(Document $document): void
    {
        $this->deleteDoc('online_doc_' . $document->id, 'online_doc');
    }

    /**
     * Index any file by stored path.
     */
    public function indexFile(string $storedPath, string $docId, string $sourceType = 'personal_file'): void
    {
        if ($this->baseUrl === '') return;

        $absolutePath = storage_path('app/' . ltrim($storedPath, '/'));

        try {
            Http::timeout(60)->post($this->baseUrl . '/ingest', [
                'path'        => $absolutePath,
                'doc_id'      => $docId,
                'source_type' => $sourceType,
            ]);
        } catch (\Throwable $e) {
            Log::warning('RagIndexService::indexFile failed', [
                'path'  => $absolutePath,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Remove a document from ChromaDB by doc_id.
     */
    public function deleteDoc(string $docId, string $sourceType = 'personal_file'): void
    {
        if ($this->baseUrl === '') return;

        try {
            Http::timeout(30)->post($this->baseUrl . '/delete', [
                'doc_id'      => $docId,
                'source_type' => $sourceType,
            ]);
        } catch (\Throwable $e) {
            Log::warning('RagIndexService::deleteDoc failed', [
                'doc_id' => $docId,
                'error'  => $e->getMessage(),
            ]);
        }
    }
}
