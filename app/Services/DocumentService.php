<?php

namespace App\Services;

use App\Models\Document;
use App\Models\User;
use App\Repositories\DocumentRepository;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class DocumentService
{
    public function __construct(
        private DocumentRepository $repository,
        private DocumentConversionService $converter
    ) {
    }

    /**
     * Create a new document
     */
    public function createDocument(User $user, string $title): Document
    {
        $document = Document::create([
            'owner_id' => $user->id,
            'title' => $title,
            'html_path' => 'pending',
            'last_edited_by' => $user->id,
        ]);

        $contentPath = "documents/{$document->id}/content.html";
        Storage::disk('local')->put($contentPath, '');
        $document->update(['html_path' => $contentPath]);

        return $document;
    }

    /**
     * Update document title and content
     */
    public function updateDocument(Document $document, User $user, array $data): Document
    {
        if (!$document->html_path) {
            $contentPath = "documents/{$document->id}/content.html";
            Storage::disk('local')->makeDirectory("documents/{$document->id}");
            $document->update(['html_path' => $contentPath]);
        }

        Storage::disk('local')->put($document->html_path, $data['content'] ?? '');

        $document->update([
            'title' => $data['title'],
            'last_edited_by' => $user->id,
        ]);

        return $document;
    }

    /**
     * Get document content
     */
    public function getDocumentContent(Document $document): string
    {
        if ($document->html_path && Storage::disk('local')->exists($document->html_path)) {
            return Storage::disk('local')->get($document->html_path);
        }

        return '';
    }

    /**
     * Import DOCX to HTML
     */
    public function importDocx(Document $document, User $user, $file): Document
    {
        $importPath = $file->store("documents/{$document->id}/imports");
        $contentPath = $document->html_path ?: "documents/{$document->id}/content.html";

        Storage::disk('local')->makeDirectory("documents/{$document->id}");

        $this->converter->importDocxToHtml(
            Storage::disk('local')->path($importPath),
            Storage::disk('local')->path($contentPath)
        );

        $document->update([
            'html_path' => $contentPath,
            'docx_path' => $importPath,
            'last_edited_by' => $user->id,
        ]);

        return $document;
    }

    /**
     * Export document to DOCX
     */
    public function exportDocx(Document $document): string
    {
        if (!$document->html_path) {
            $contentPath = "documents/{$document->id}/content.html";
            Storage::disk('local')->makeDirectory("documents/{$document->id}");
            Storage::disk('local')->put($contentPath, '');
            $document->update(['html_path' => $contentPath]);
        }

        $exportDir = "documents/{$document->id}/exports";
        Storage::disk('local')->makeDirectory($exportDir);

        $exportPath = $exportDir . '/' . now()->format('YmdHis') . '.docx';
        $htmlPath = Storage::disk('local')->path($document->html_path);
        $docxPath = Storage::disk('local')->path($exportPath);

        $this->converter->exportHtmlToDocx($htmlPath, $docxPath);

        return $docxPath;
    }

    /**
     * Share document with a user
     */
    public function shareDocument(Document $document, string $email, string $permission): bool
    {
        $user = $this->repository->getUserByEmail($email);

        if (!$user || $user->id === $document->owner_id) {
            return false;
        }

        $document->sharedUsers()->syncWithoutDetaching([
            $user->id => ['permission' => $permission],
        ]);

        return true;
    }

    /**
     * Update share permission
     */
    public function updateSharePermission(Document $document, int $userId, string $permission): bool
    {
        if ($userId === $document->owner_id) {
            return false;
        }

        $document->sharedUsers()->updateExistingPivot($userId, [
            'permission' => $permission,
        ]);

        return true;
    }

    /**
     * Remove share access
     */
    public function removeShare(Document $document, int $userId): bool
    {
        if ($userId === $document->owner_id) {
            return false;
        }

        $document->sharedUsers()->detach($userId);

        return true;
    }
}
