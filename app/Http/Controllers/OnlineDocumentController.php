<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportDocxRequest;
use App\Http\Requests\RemoveShareRequest;
use App\Http\Requests\ShareDocumentRequest;
use App\Http\Requests\StoreDocumentRequest;
use App\Http\Requests\UpdateDocumentRequest;
use App\Http\Requests\UpdateShareRequest;
use App\Models\Document;
use App\Models\PersonalDocumentLink;
use App\Models\PersonalFile;
use App\Models\PersonalFolder;
use App\Repositories\DocumentRepository;
use App\Services\DocumentService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class OnlineDocumentController extends Controller
{
    public function __construct(
        private DocumentRepository $repository,
        private DocumentService $service
    ) {
    }

    public function landing(Request $request)
    {
        $user = auth()->user();
        $recentDocuments = $this->repository->getRecentDocumentsForUser($user, 5, 'recent_page');

        $currentFolder = null;
        $currentFolderId = $request->query('folder');
        if ($currentFolderId) {
            $currentFolder = PersonalFolder::query()
                ->where('user_id', $user->id)
                ->whereKey($currentFolderId)
                ->firstOrFail();
        }

        $folders = PersonalFolder::query()
            ->where('user_id', $user->id)
            ->where('parent_id', $currentFolder?->id)
            ->orderBy('name')
            ->get();

        $files = PersonalFile::query()
            ->where('user_id', $user->id)
            ->where('folder_id', $currentFolder?->id)
            ->latest('created_at')
            ->get();

        $links = PersonalDocumentLink::query()
            ->with('document')
            ->where('user_id', $user->id)
            ->where('folder_id', $currentFolder?->id)
            ->latest('created_at')
            ->get();

        $allFolders = PersonalFolder::query()
            ->where('user_id', $user->id)
            ->orderBy('name')
            ->get();

        $folderBreadcrumbs = $this->buildFolderBreadcrumbs($currentFolder);

        return view('online-docs.docs', compact(
            'recentDocuments',
            'currentFolder',
            'folders',
            'files',
            'links',
            'allFolders',
            'folderBreadcrumbs'
        ));
    }

    public function createFolder(Request $request)
    {
        $user = auth()->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'parent_id' => ['nullable', 'integer'],
        ]);

        $parentId = $data['parent_id'] ?? null;
        if ($parentId) {
            $parentFolder = PersonalFolder::query()
                ->where('user_id', $user->id)
                ->whereKey($parentId)
                ->firstOrFail();
            $parentId = $parentFolder->id;
        }

        PersonalFolder::create([
            'user_id' => $user->id,
            'parent_id' => $parentId,
            'name' => $data['name'],
        ]);

        return redirect()->route('online-docs.home', $parentId ? ['folder' => $parentId] : []);
    }

    public function uploadPersonalFile(Request $request)
    {
        $user = auth()->user();
        $data = $request->validate([
            'file' => ['required', 'file'],
            'folder_id' => ['nullable', 'integer'],
        ]);

        $folderId = $data['folder_id'] ?? null;
        if ($folderId) {
            $folder = PersonalFolder::query()
                ->where('user_id', $user->id)
                ->whereKey($folderId)
                ->firstOrFail();
            $folderId = $folder->id;
        }

        $file = $request->file('file');
        $extension = $file->getClientOriginalExtension();
        $filename = $extension ? Str::uuid() . '.' . $extension : (string) Str::uuid();
        $baseDir = 'personal-files/' . $user->id . '/' . ($folderId ? ('folder-' . $folderId) : 'root');
        $storedPath = $file->storeAs($baseDir, $filename, 'local');

        PersonalFile::create([
            'user_id' => $user->id,
            'folder_id' => $folderId,
            'stored_path' => $storedPath,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);

        return redirect()->route('online-docs.home', $folderId ? ['folder' => $folderId] : []);
    }

    public function downloadPersonalFile(PersonalFile $file)
    {
        $user = auth()->user();
        if ($file->user_id !== $user->id) {
            abort(403);
        }

        if (!Storage::disk('local')->exists($file->stored_path)) {
            abort(404);
        }

        return Storage::disk('local')->download($file->stored_path, $file->original_name);
    }

    public function previewPersonalFile(PersonalFile $file)
    {
        $user = auth()->user();
        if ($file->user_id !== $user->id) {
            abort(403);
        }

        if (!Storage::disk('local')->exists($file->stored_path)) {
            abort(404);
        }

        return response()->file(Storage::disk('local')->path($file->stored_path), [
            'Content-Disposition' => 'inline; filename="' . basename($file->original_name) . '"',
        ]);
    }

    public function openPersonalFile(Request $request, PersonalFile $file)
    {
        $user = auth()->user();
        if ($file->user_id !== $user->id) {
            abort(403);
        }

        if (!Storage::disk('local')->exists($file->stored_path)) {
            abort(404);
        }

        $mode = $request->query('mode') === 'view' ? 'view' : 'edit';
        $extension = strtolower(pathinfo((string) $file->original_name, PATHINFO_EXTENSION));
        $title = pathinfo((string) $file->original_name, PATHINFO_FILENAME) ?: $file->original_name;

        $type = match ($extension) {
            'doc', 'docx' => 'docs',
            'xls', 'xlsx' => 'excel',
            'ppt', 'pptx' => 'powerpoint',
            default => null,
        };

        if (!$type) {
            abort(422, 'Unsupported office file format');
        }

        $linkedDocument = $file->document;
        if (
            $linkedDocument
            && $linkedDocument->owner_id === $user->id
            && $linkedDocument->type === $type
        ) {
            return redirect()->route('online-docs.docs.show', [
                'document' => $linkedDocument,
                'mode' => $mode,
            ]);
        }

        $document = $this->service->createDocument($user, $title, $type);

        if ($type === 'docs') {
            $targetPath = "documents/{$document->id}/document.docx";
            Storage::disk('local')->put($targetPath, Storage::disk('local')->get($file->stored_path));
            $document->update([
                'docx_path' => $targetPath,
                'last_edited_by' => $user->id,
            ]);
        } elseif ($type === 'excel') {
            $targetPath = "documents/{$document->id}/sheet.xlsx";
            Storage::disk('local')->put($targetPath, Storage::disk('local')->get($file->stored_path));
            $document->update([
                'xlsx_path' => $targetPath,
                'last_edited_by' => $user->id,
            ]);
        } else {
            $targetPath = "documents/{$document->id}/presentation.pptx";
            Storage::disk('local')->put($targetPath, Storage::disk('local')->get($file->stored_path));
            $document->update([
                'pptx_path' => $targetPath,
                'last_edited_by' => $user->id,
            ]);
        }

        $file->update([
            'document_id' => $document->id,
        ]);

        return redirect()->route('online-docs.docs.show', [
            'document' => $document,
            'mode' => $mode,
        ]);
    }

    public function addDocumentLink(Request $request, Document $document)
    {
        $this->authorize('view', $document);

        $user = auth()->user();
        $data = $request->validate([
            'folder_id' => ['nullable', 'integer'],
        ]);

        $folderId = $data['folder_id'] ?? null;
        if ($folderId) {
            $folder = PersonalFolder::query()
                ->where('user_id', $user->id)
                ->whereKey($folderId)
                ->firstOrFail();
            $folderId = $folder->id;
        }

        PersonalDocumentLink::create([
            'user_id' => $user->id,
            'folder_id' => $folderId,
            'document_id' => $document->id,
            'name' => $document->title,
        ]);

        return $this->redirectToStorageFolder($request, $folderId)
            ->with('storage_success', __('online_docs.link_added'));
    }

    public function renameDocumentLink(Request $request, PersonalDocumentLink $link)
    {
        $user = auth()->user();
        if ($link->user_id !== $user->id) {
            abort(403);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $link->update(['name' => $data['name']]);

        return $this->redirectToStorageFolder($request, $link->folder_id)
            ->with('storage_success', __('online_docs.link_renamed'));
    }

    public function deleteDocumentLink(Request $request, PersonalDocumentLink $link)
    {
        $user = auth()->user();
        if ($link->user_id !== $user->id) {
            abort(403);
        }

        $folderId = $link->folder_id;
        $link->delete();

        return $this->redirectToStorageFolder($request, $folderId)
            ->with('storage_success', __('online_docs.link_deleted'));
    }

    public function renameFolder(Request $request, PersonalFolder $folder)
    {
        $user = auth()->user();
        if ($folder->user_id !== $user->id) {
            abort(403);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
        ]);

        try {
            $folder->update(['name' => $data['name']]);
        } catch (QueryException $error) {
            return $this->redirectToStorageFolder($request, $folder->parent_id)
                ->with('storage_error', __('online_docs.folder_rename_failed'));
        }

        return $this->redirectToStorageFolder($request, $folder->parent_id)
            ->with('storage_success', __('online_docs.folder_renamed'));
    }

    public function deleteFolder(Request $request, PersonalFolder $folder)
    {
        $user = auth()->user();
        if ($folder->user_id !== $user->id) {
            abort(403);
        }

        $this->deleteFolderContents($folder);
        $folder->delete();

        return $this->redirectToStorageFolder($request, $folder->parent_id)
            ->with('storage_success', __('online_docs.folder_deleted'));
    }

    public function renameFile(Request $request, PersonalFile $file)
    {
        $user = auth()->user();
        if ($file->user_id !== $user->id) {
            abort(403);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $file->update(['original_name' => $data['name']]);

        return $this->redirectToStorageFolder($request, $file->folder_id)
            ->with('storage_success', __('online_docs.file_renamed'));
    }

    public function deleteFile(Request $request, PersonalFile $file)
    {
        $user = auth()->user();
        if ($file->user_id !== $user->id) {
            abort(403);
        }

        if (Storage::disk('local')->exists($file->stored_path)) {
            Storage::disk('local')->delete($file->stored_path);
        }

        $folderId = $file->folder_id;
        $file->delete();

        return $this->redirectToStorageFolder($request, $folderId)
            ->with('storage_success', __('online_docs.file_deleted'));
    }

    public function moveStorageItem(Request $request)
    {
        $user = auth()->user();
        $data = $request->validate([
            'item_type' => ['required', 'string'],
            'item_id' => ['required', 'integer'],
            'target_folder_id' => ['nullable', 'integer'],
        ]);

        $targetFolderId = $this->resolveTargetFolderId($user->id, $data['target_folder_id'] ?? null);
        $this->moveItem($user->id, $data['item_type'], $data['item_id'], $targetFolderId);

        return response()->json(['status' => 'ok']);
    }

    public function bulkMoveStorageItems(Request $request)
    {
        $user = auth()->user();
        $data = $request->validate([
            'items' => ['required', 'array'],
            'items.*.type' => ['required', 'string'],
            'items.*.id' => ['required', 'integer'],
            'target_folder_id' => ['nullable', 'integer'],
        ]);

        $targetFolderId = $this->resolveTargetFolderId($user->id, $data['target_folder_id'] ?? null);
        foreach ($data['items'] as $item) {
            $this->moveItem($user->id, $item['type'], $item['id'], $targetFolderId);
        }

        return response()->json(['status' => 'ok']);
    }

    public function bulkDeleteStorageItems(Request $request)
    {
        $user = auth()->user();
        $data = $request->validate([
            'items' => ['required', 'array'],
            'items.*.type' => ['required', 'string'],
            'items.*.id' => ['required', 'integer'],
        ]);

        foreach ($data['items'] as $item) {
            $this->deleteStorageItem($user->id, $item['type'], $item['id']);
        }

        return response()->json(['status' => 'ok']);
    }

    public function docsIndex()
    {
        return $this->renderTypeIndex(
            'docs',
            __('online_docs.docs_page_title'),
            __('online_docs.docs_page_subtitle'),
            'online-docs.docs.store'
        );
    }

    public function excelIndex()
    {
        return $this->renderTypeIndex(
            'excel',
            __('online_docs.excel_page_title'),
            __('online_docs.excel_page_subtitle'),
            'online-docs.excel.create'
        );
    }

    public function powerpointIndex()
    {
        return $this->renderTypeIndex(
            'powerpoint',
            __('online_docs.powerpoint_page_title'),
            __('online_docs.powerpoint_page_subtitle'),
            'online-docs.powerpoint.create'
        );
    }

    public function store(StoreDocumentRequest $request)
    {
        $document = $this->service->createDocument(
            auth()->user(),
            $request->validated()['title'],
            'docs'
        );

        return redirect()->route('online-docs.docs.show', $document);
    }

    public function createExcel(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

        $title = trim($validated['title']);
        $document = $this->service->createDocument(auth()->user(), $title, 'excel');

        $this->service->updateDocument($document, auth()->user(), [
            'title' => $title,
            'content' => '',
        ]);

        $this->createEmptySpreadsheet($document);

        return redirect()->route('online-docs.docs.show', $document);
    }

    public function createPowerpoint(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

        $title = trim($validated['title']);
        $document = $this->service->createDocument(auth()->user(), $title, 'powerpoint');

        $this->service->updateDocument($document, auth()->user(), [
            'title' => $title,
            'content' => '',
        ]);

        $this->service->ensurePptxPath($document);

        return redirect()->route('online-docs.docs.show', $document);
    }

    public function show(Request $request, Document $document)
    {
        $this->authorize('view', $document);
        $canEdit = auth()->user()->can('update', $document);
        $forcedView = $request->query('mode') === 'view';
        $editorCanEdit = $canEdit && !$forcedView;

        $html = $this->service->getDocumentContent($document);
        $sharedUsers = $this->repository->getSharedUsers($document);
        $shareCandidates = $this->repository->getShareCandidates($document);

        $onlyofficeConfig = null;
        if (in_array($document->type, ['docs', 'powerpoint'], true)) {
            $fileType = $document->type === 'powerpoint' ? 'pptx' : 'docx';
            $documentType = $document->type === 'powerpoint' ? 'slide' : 'word';
            if ($document->type === 'powerpoint') {
                $this->service->ensurePptxPath($document);
            } else {
                $this->service->ensureDocxPath($document);
            }

            $fileUrl = $this->onlyofficeSignedRoute('onlyoffice.files', ['document' => $document]);
            $callbackUrl = $this->onlyofficeSignedRoute('onlyoffice.callback', ['document' => $document]);
            $key = $this->onlyofficeDocumentKey($document);

            $onlyofficeConfig = [
                'documentType' => $documentType,
                'type' => 'desktop',
                'height' => '720px',
                'width' => '100%',
                'document' => [
                    'fileType' => $fileType,
                    'key' => $key,
                    'title' => $document->title ?: 'Document',
                    'url' => $fileUrl,
                    'permissions' => [
                        'edit' => $editorCanEdit,
                    ],
                ],
                'editorConfig' => [
                    'callbackUrl' => $callbackUrl,
                    'mode' => $editorCanEdit ? 'edit' : 'view',
                    'lang' => app()->getLocale() === 'vi' ? 'vi' : 'en',
                    'region' => app()->getLocale() === 'vi' ? 'vi-VN' : 'en-US',
                    'customization' => [
                        'uiTheme' => 'theme-light',
                        'compactHeader' => false,
                        'compactToolbar' => false,
                        'toolbarNoTabs' => false,
                        'hideRightMenu' => false,
                        'hideRulers' => false,
                        'autosave' => true,
                        'forcesave' => true,
                        'help' => false,
                        'feedback' => false,
                    ],
                    'user' => [
                        'id' => (string) auth()->id(),
                        'name' => auth()->user()->name ?? auth()->user()->email,
                    ],
                ],
            ];

            $onlyofficeConfig['token'] = $this->onlyofficeJwt($onlyofficeConfig);
        }

        return view('online-docs.show', compact(
            'document',
            'html',
            'editorCanEdit',
            'forcedView',
            'sharedUsers',
            'shareCandidates',
            'onlyofficeConfig'
        ));
    }

    public function update(UpdateDocumentRequest $request, Document $document)
    {
        $this->authorize('update', $document);

        $this->service->updateDocument($document, auth()->user(), $request->validated());

        if ($request->expectsJson()) {
            return response()->json(['status' => 'ok']);
        }

        return redirect()->back();
    }

    public function rename(Request $request, Document $document)
    {
        $this->authorize('update', $document);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

        $this->service->updateDocument($document, auth()->user(), [
            'title' => $data['title'],
        ]);

        return redirect()->back();
    }

    public function destroy(Document $document)
    {
        $this->authorize('delete', $document);

        Storage::disk('local')->deleteDirectory('documents/' . $document->id);

        if ($document->docx_path && Storage::disk('local')->exists($document->docx_path)) {
            Storage::disk('local')->delete($document->docx_path);
        }
        if ($document->pptx_path && Storage::disk('local')->exists($document->pptx_path)) {
            Storage::disk('local')->delete($document->pptx_path);
        }

        $document->delete();

        return redirect()->back();
    }

    public function importDocx(ImportDocxRequest $request, Document $document)
    {
        $this->authorize('update', $document);

        try {
            $this->service->importDocx($document, auth()->user(), $request->file('docx'));
        } catch (\Throwable $error) {
            $message = $error->getMessage() ?: __('online_docs.import_failed');
            return redirect()->back()->with('docx_error', $message);
        }

        return redirect()->route('online-docs.docs.show', $document);
    }

    public function importXlsx(Request $request, Document $document)
    {
        $this->authorize('update', $document);
        abort_unless($document->type === 'excel', 404);

        $data = $request->validate([
            'xlsx' => ['required', 'file', 'mimes:xlsx,xls'],
        ]);

        try {
            $path = $this->ensureSpreadsheetPath($document);
            Storage::disk('local')->makeDirectory(dirname($path));
            Storage::disk('local')->put($path, file_get_contents($data['xlsx']->getRealPath()));

            $document->update([
                'xlsx_path' => $path,
                'last_edited_by' => auth()->id(),
            ]);
        } catch (\Throwable $error) {
            return redirect()->back()->with('xlsx_error', __('online_docs.import_failed'));
        }

        return redirect()->route('online-docs.docs.show', $document);
    }

    public function importPptx(Request $request, Document $document)
    {
        $this->authorize('update', $document);
        abort_unless($document->type === 'powerpoint', 404);

        $data = $request->validate([
            'pptx' => ['required', 'file', 'mimes:pptx,ppt'],
        ]);

        try {
            $path = $this->service->ensurePptxPath($document);
            Storage::disk('local')->makeDirectory(dirname($path));
            Storage::disk('local')->put($path, file_get_contents($data['pptx']->getRealPath()));

            $document->update([
                'pptx_path' => $path,
                'last_edited_by' => auth()->id(),
            ]);
        } catch (\Throwable $error) {
            return redirect()->back()->with('pptx_error', __('online_docs.import_failed'));
        }

        return redirect()->route('online-docs.docs.show', $document);
    }

    public function exportDocx(Document $document)
    {
        $this->authorize('view', $document);

        if (in_array($document->type, ['docs', 'powerpoint'], true)) {
            $this->forceSaveOnlyofficeDocument($document);
        }

        if ($document->type === 'powerpoint') {
            $path = $this->service->ensurePptxPath($document);
            $filename = Str::slug($document->title ?: 'presentation') . '.pptx';
            return Storage::disk('local')->download($path, $filename);
        }

        $docxPath = $this->service->exportDocx($document);
        $filename = Str::slug($document->title ?: 'document') . '.docx';

        return response()->download($docxPath, $filename);
    }

    public function downloadXlsx(Document $document)
    {
        $this->authorize('view', $document);

        $path = $this->ensureSpreadsheetPath($document);
        if (!Storage::disk('local')->exists($path)) {
            $this->createEmptySpreadsheet($document);
        }
        $filename = Str::slug($document->title ?: 'spreadsheet') . '.xlsx';

        return Storage::disk('local')->download($path, $filename);
    }

    public function onlyofficeFile(Document $document)
    {
        if ($document->type === 'powerpoint') {
            $path = $this->service->ensurePptxPath($document);
        } else {
            $path = $this->service->ensureDocxPath($document);
        }

        if (!Storage::disk('local')->exists($path)) {
            abort(404);
        }

        return Storage::disk('local')->response($path, basename($path));
    }

    public function onlyofficeCallback(Request $request, Document $document)
    {
        if (!$this->validateOnlyofficeJwt($request)) {
            return response()->json(['error' => 1, 'message' => 'Invalid token']);
        }

        $status = (int) $request->input('status', 0);
        // 2: document is ready for saving, 6: forcesave was requested
        if (!in_array($status, [2, 6], true)) {
            return response()->json(['error' => 0]);
        }

        $url = (string) $request->input('url');
        if ($url === '') {
            return response()->json(['error' => 1, 'message' => 'Missing file URL']);
        }

        $response = Http::get($url);
        if (!$response->successful()) {
            return response()->json(['error' => 1, 'message' => 'Download failed']);
        }

        if ($document->type === 'powerpoint') {
            $path = $this->service->ensurePptxPath($document);
        } else {
            $path = $this->service->ensureDocxPath($document);
        }
        Storage::disk('local')->put($path, $response->body());

        $document->update([
            'last_edited_by' => auth()->id(),
        ]);

        $this->syncLinkedPersonalFilesFromContent($document, $response->body());

        return response()->json(['error' => 0]);
    }

    public function saveXlsx(Request $request, Document $document)
    {
        $this->authorize('update', $document);

        $data = $request->validate([
            'sheets' => ['required', 'array'],
        ]);

        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        foreach ($data['sheets'] as $index => $sheet) {
            $name = is_array($sheet) && isset($sheet['name'])
                ? (string) $sheet['name']
                : 'Sheet ' . ($index + 1);
            $worksheet = new Worksheet($spreadsheet, mb_substr($name, 0, 31));
            $spreadsheet->addSheet($worksheet, $index);

            if (!is_array($sheet) || !isset($sheet['data']) || !is_array($sheet['data'])) {
                continue;
            }

            foreach ($sheet['data'] as $rowIndex => $row) {
                if (!is_array($row)) {
                    continue;
                }
                foreach ($row as $colIndex => $cell) {
                    if (!is_array($cell)) {
                        continue;
                    }
                    $value = $cell['v'] ?? null;
                    $formula = $cell['f'] ?? null;
                    if ($value === null && $formula === null) {
                        continue;
                    }

                    $cellRef = Coordinate::stringFromColumnIndex($colIndex + 1) . ($rowIndex + 1);
                    if ($formula) {
                        $worksheet->setCellValue($cellRef, str_starts_with($formula, '=') ? $formula : '=' . $formula);
                    } else {
                        $worksheet->setCellValue($cellRef, $value);
                    }
                }
            }
        }

        $path = $this->ensureSpreadsheetPath($document);
        $fullPath = Storage::disk('local')->path($path);
        Storage::disk('local')->makeDirectory(dirname($path));

        $writer = new Xlsx($spreadsheet);
        $writer->save($fullPath);

        $document->update([
            'last_edited_by' => auth()->id(),
        ]);

        $this->syncLinkedPersonalFilesFromPath($document, $path);

        return response()->json(['status' => 'ok']);
    }

    public function share(ShareDocumentRequest $request, Document $document)
    {
        $this->authorize('share', $document);

        $data = $request->validated();
        $this->service->shareDocument($document, $data['email'], $data['permission']);

        return redirect()->back();
    }

    public function updateShare(UpdateShareRequest $request, Document $document)
    {
        $this->authorize('share', $document);

        $data = $request->validated();
        $this->service->updateSharePermission($document, $data['user_id'], $data['permission']);

        return redirect()->back();
    }

    public function removeShare(RemoveShareRequest $request, Document $document)
    {
        $this->authorize('share', $document);

        $this->service->removeShare($document, $request->validated()['user_id']);

        return redirect()->back();
    }

    public function presence(Document $document)
    {
        $this->authorize('view', $document);

        return response()->json([
            'editors' => $this->activeEditors($document),
        ]);
    }

    public function touchPresence(Document $document)
    {
        $this->authorize('view', $document);

        $user = auth()->user();
        if ($user && $user->can('update', $document)) {
            $key = $this->presenceKey($document);
            $presence = Cache::get($key, []);
            if (!is_array($presence)) {
                $presence = [];
            }

            $presence[(string) $user->id] = [
                'id' => $user->id,
                'name' => $user->name ?: $user->email,
                'at' => now()->getTimestamp(),
            ];

            Cache::put($key, $presence, now()->addMinutes(2));
        }

        return response()->json([
            'editors' => $this->activeEditors($document),
        ]);
    }

    private function buildSpreadsheetHtml(int $rows = 12, int $cols = 8): string
    {
        $safeRows = max(1, min($rows, 40));
        $safeCols = max(1, min($cols, 20));
        $colLabels = [];

        for ($i = 0; $i < $safeCols; $i += 1) {
            $colLabels[] = chr(65 + $i);
        }

        $thead = '<thead><tr><th></th>';
        foreach ($colLabels as $label) {
            $thead .= '<th>' . $label . '</th>';
        }
        $thead .= '</tr></thead>';

        $tbody = '<tbody>';
        for ($row = 1; $row <= $safeRows; $row += 1) {
            $tbody .= '<tr><th>' . $row . '</th>';
            for ($col = 0; $col < $safeCols; $col += 1) {
                $tbody .= '<td></td>';
            }
            $tbody .= '</tr>';
        }
        $tbody .= '</tbody>';

        return '<table data-table-style="sheet" class="table-sheet">'
            . $thead
            . $tbody
            . '</table><p></p>';
    }

    private function presenceKey(Document $document): string
    {
        return 'online_docs_presence_' . $document->id;
    }

    private function activeEditors(Document $document): array
    {
        $presence = Cache::get($this->presenceKey($document), []);
        if (!is_array($presence)) {
            return [];
        }

        $nowTs = now()->getTimestamp();
        $ttlSeconds = 20;
        $nextPresence = [];
        $editors = [];

        foreach ($presence as $userId => $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $lastSeen = (int) ($entry['at'] ?? 0);
            if ($nowTs - $lastSeen > $ttlSeconds) {
                continue;
            }

            $name = (string) ($entry['name'] ?? '');
            $initials = collect(preg_split('/\s+/', trim($name)) ?: [])
                ->filter()
                ->take(2)
                ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
                ->implode('');
            if ($initials === '') {
                $initials = 'U';
            }

            $normalized = [
                'id' => (int) ($entry['id'] ?? $userId),
                'name' => $name,
                'initials' => $initials,
            ];

            $nextPresence[(string) $normalized['id']] = [
                ...$entry,
                'id' => $normalized['id'],
                'name' => $name,
                'at' => $lastSeen,
            ];
            $editors[] = $normalized;
        }

        Cache::put($this->presenceKey($document), $nextPresence, now()->addMinutes(2));

        usort($editors, fn ($a, $b) => strcmp($a['name'], $b['name']));
        return $editors;
    }

    private function ensureSpreadsheetPath(Document $document): string
    {
        if ($document->xlsx_path) {
            return $document->xlsx_path;
        }

        $path = "documents/{$document->id}/sheet.xlsx";
        Storage::disk('local')->makeDirectory("documents/{$document->id}");
        $document->update(['xlsx_path' => $path]);

        return $path;
    }

    private function createEmptySpreadsheet(Document $document): void
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->setTitle('Sheet 1');

        $path = $this->ensureSpreadsheetPath($document);
        $fullPath = Storage::disk('local')->path($path);
        Storage::disk('local')->makeDirectory(dirname($path));

        $writer = new Xlsx($spreadsheet);
        $writer->save($fullPath);
    }

    private function onlyofficeJwt(array $payload): string
    {
        $secret = (string) config('onlyoffice.jwt_secret');
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $encode = fn ($value) => rtrim(strtr(base64_encode(json_encode($value)), '+/', '-_'), '=');

        $segments = [
            $encode($header),
            $encode($payload),
        ];

        $signature = hash_hmac('sha256', implode('.', $segments), $secret, true);
        $segments[] = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

        return implode('.', $segments);
    }

    private function onlyofficeDocumentKey(Document $document): string
    {
        $filePath = $document->type === 'powerpoint'
            ? ($document->pptx_path ?: 'none')
            : ($document->docx_path ?: 'none');

        $version = $document->updated_at?->getTimestamp() ?: 0;

        return 'doc_' . $document->id . '_' . substr(sha1($document->type . '|' . $filePath . '|' . $version), 0, 20);
    }

    private function forceSaveOnlyofficeDocument(Document $document): void
    {
        $server = rtrim((string) config('onlyoffice.document_server_url'), '/');
        if ($server === '') {
            return;
        }

        $storagePath = $document->type === 'powerpoint'
            ? $this->service->ensurePptxPath($document)
            : $this->service->ensureDocxPath($document);
        $beforeModified = Storage::disk('local')->exists($storagePath)
            ? Storage::disk('local')->lastModified($storagePath)
            : 0;

        $payload = [
            'c' => 'forcesave',
            'key' => $this->onlyofficeDocumentKey($document),
            'userdata' => 'export',
        ];

        try {
            $token = $this->onlyofficeJwt($payload);
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->timeout(8)->post($server . '/coauthoring/CommandService.ashx', [
                ...$payload,
                'token' => $token,
            ]);

            $result = $response->json();
            $commandOk = $response->successful() && isset($result['error']) && (int) $result['error'] === 0;
            if (!$commandOk) {
                return;
            }

            // Wait for callback to persist updated DOCX before exporting.
            for ($i = 0; $i < 16; $i += 1) {
                usleep(250000);
                if (!Storage::disk('local')->exists($storagePath)) {
                    continue;
                }

                $currentModified = Storage::disk('local')->lastModified($storagePath);
                if ($currentModified > $beforeModified) {
                    break;
                }
            }
        } catch (\Throwable $error) {
            // Ignore command failures and fall back to the latest persisted file.
        }
    }

    private function validateOnlyofficeJwt(Request $request): bool
    {
        $secret = (string) config('onlyoffice.jwt_secret');
        $headerToken = (string) $request->header('Authorization', '');
        if ($headerToken !== '' && str_starts_with(strtolower($headerToken), 'bearer ')) {
            $headerToken = trim(substr($headerToken, 7));
        }

        $token = $request->bearerToken()
            ?: ($headerToken !== '' ? $headerToken : null)
            ?: (string) $request->input('token', '');
        if ($token === '' || $secret === '') {
            return false;
        }

        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }

        [$header, $payload, $signature] = $parts;
        $expected = rtrim(strtr(base64_encode(hash_hmac('sha256', $header . '.' . $payload, $secret, true)), '+/', '-_'), '=');

        return hash_equals($expected, $signature);
    }

    private function onlyofficeSignedRoute(string $name, array $parameters): string
    {
        $publicBase = rtrim((string) config('onlyoffice.public_url'), '/');
        if ($publicBase === '') {
            return URL::signedRoute($name, $parameters);
        }

        $originalRoot = rtrim(URL::to('/'), '/');
        $parsed = parse_url($publicBase);
        $scheme = $parsed['scheme'] ?? null;

        URL::forceRootUrl($publicBase);
        if ($scheme) {
            URL::forceScheme($scheme);
        }

        $signed = URL::signedRoute($name, $parameters);

        URL::forceRootUrl($originalRoot);
        if ($scheme) {
            URL::forceScheme(parse_url($originalRoot, PHP_URL_SCHEME) ?: null);
        }

        return $signed;
    }

    private function syncLinkedPersonalFilesFromPath(Document $document, string $sourcePath): void
    {
        if (!Storage::disk('local')->exists($sourcePath)) {
            return;
        }

        try {
            $content = Storage::disk('local')->get($sourcePath);
        } catch (\Throwable $error) {
            return;
        }

        $this->syncLinkedPersonalFilesFromContent($document, $content);
    }

    private function syncLinkedPersonalFilesFromContent(Document $document, string $content): void
    {
        $mimeType = $this->officeMimeTypeForDocument($document);
        $size = strlen($content);

        PersonalFile::query()
            ->where('document_id', $document->id)
            ->chunkById(100, function ($files) use ($content, $mimeType, $size): void {
                foreach ($files as $file) {
                    $storedPath = (string) $file->stored_path;
                    if ($storedPath === '') {
                        continue;
                    }

                    try {
                        Storage::disk('local')->makeDirectory(dirname($storedPath));
                        Storage::disk('local')->put($storedPath, $content);
                    } catch (\Throwable $error) {
                        continue;
                    }

                    $file->update([
                        'mime_type' => $mimeType,
                        'size' => $size,
                    ]);
                }
            });
    }

    private function officeMimeTypeForDocument(Document $document): string
    {
        return match ($document->type) {
            'excel' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'powerpoint' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            default => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        };
    }

    private function renderTypeIndex(
        string $type,
        string $pageTitle,
        string $pageSubtitle,
        string $createRouteName
    ) {
        $user = auth()->user();
        $ownedDocuments = $this->repository->getOwnedDocumentsByType($user, $type);
        $sharedDocuments = $this->repository->getSharedDocumentsByType($user, $type);

        return view('online-docs.type', [
            'type' => $type,
            'pageTitle' => $pageTitle,
            'pageSubtitle' => $pageSubtitle,
            'createRouteName' => $createRouteName,
            'ownedDocuments' => $ownedDocuments,
            'sharedDocuments' => $sharedDocuments,
        ]);
    }

    private function buildFolderBreadcrumbs(?PersonalFolder $folder): array
    {
        $breadcrumbs = [];
        $current = $folder;

        while ($current) {
            $breadcrumbs[] = [
                'id' => $current->id,
                'name' => $current->name,
            ];
            $current = $current->parent;
        }

        return array_reverse($breadcrumbs);
    }

    private function deleteFolderContents(PersonalFolder $folder): void
    {
        foreach ($folder->files as $file) {
            if (Storage::disk('local')->exists($file->stored_path)) {
                Storage::disk('local')->delete($file->stored_path);
            }
            $file->delete();
        }

        foreach ($folder->children as $child) {
            $this->deleteFolderContents($child);
            $child->delete();
        }
    }

    private function resolveTargetFolderId(int $userId, ?int $targetFolderId): ?int
    {
        if (!$targetFolderId) {
            return null;
        }

        return PersonalFolder::query()
            ->where('user_id', $userId)
            ->whereKey($targetFolderId)
            ->value('id');
    }

    private function moveItem(int $userId, string $type, int $id, ?int $targetFolderId): void
    {
        if ($type === 'folder') {
            $folder = PersonalFolder::query()
                ->where('user_id', $userId)
                ->whereKey($id)
                ->firstOrFail();

            if ($this->isDescendantFolder($folder, $targetFolderId)) {
                return;
            }

            $folder->update(['parent_id' => $targetFolderId]);
            return;
        }

        if ($type === 'file') {
            $file = PersonalFile::query()
                ->where('user_id', $userId)
                ->whereKey($id)
                ->firstOrFail();
            $file->update(['folder_id' => $targetFolderId]);
            return;
        }

        if ($type === 'link') {
            $link = PersonalDocumentLink::query()
                ->where('user_id', $userId)
                ->whereKey($id)
                ->firstOrFail();
            $link->update(['folder_id' => $targetFolderId]);
        }
    }

    private function deleteStorageItem(int $userId, string $type, int $id): void
    {
        if ($type === 'folder') {
            $folder = PersonalFolder::query()
                ->where('user_id', $userId)
                ->whereKey($id)
                ->firstOrFail();
            $this->deleteFolderContents($folder);
            $folder->delete();
            return;
        }

        if ($type === 'file') {
            $file = PersonalFile::query()
                ->where('user_id', $userId)
                ->whereKey($id)
                ->firstOrFail();
            if (Storage::disk('local')->exists($file->stored_path)) {
                Storage::disk('local')->delete($file->stored_path);
            }
            $file->delete();
            return;
        }

        if ($type === 'link') {
            $link = PersonalDocumentLink::query()
                ->where('user_id', $userId)
                ->whereKey($id)
                ->firstOrFail();
            $link->delete();
        }
    }

    private function isDescendantFolder(PersonalFolder $folder, ?int $targetFolderId): bool
    {
        if (!$targetFolderId) {
            return false;
        }

        $currentId = $targetFolderId;
        while ($currentId) {
            if ($currentId === $folder->id) {
                return true;
            }

            $currentId = PersonalFolder::query()
                ->where('user_id', $folder->user_id)
                ->whereKey($currentId)
                ->value('parent_id');
        }

        return false;
    }

    private function redirectToStorageFolder(Request $request, ?int $fallbackFolderId = null)
    {
        $redirectFolderId = $request->input('redirect_folder_id');
        $folderId = $redirectFolderId ?: $fallbackFolderId;

        return redirect()->route('online-docs.home', $folderId ? ['folder' => $folderId] : []);
    }
}
