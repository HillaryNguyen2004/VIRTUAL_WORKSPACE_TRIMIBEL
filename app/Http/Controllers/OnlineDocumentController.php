<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportDocxRequest;
use App\Http\Requests\RemoveShareRequest;
use App\Http\Requests\ShareDocumentRequest;
use App\Http\Requests\StoreDocumentRequest;
use App\Http\Requests\UpdateDocumentRequest;
use App\Http\Requests\UpdateShareRequest;
use App\Models\Document;
use App\Repositories\DocumentRepository;
use App\Services\DocumentService;
use Illuminate\Support\Str;

class OnlineDocumentController extends Controller
{
    public function __construct(
        private DocumentRepository $repository,
        private DocumentService $service
    ) {
    }

    public function landing()
    {
        return view('online-docs.docs');
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

    public function createExcel()
    {
        $title = __('online_docs.excel_default_title');
        $document = $this->service->createDocument(auth()->user(), $title, 'excel');

        $this->service->updateDocument($document, auth()->user(), [
            'title' => $title,
            'content' => $this->buildSpreadsheetHtml(),
        ]);

        return redirect()->route('online-docs.docs.show', $document);
    }

    public function createPowerpoint()
    {
        $title = __('online_docs.powerpoint_default_title');
        $document = $this->service->createDocument(auth()->user(), $title, 'powerpoint');

        $this->service->updateDocument($document, auth()->user(), [
            'title' => $title,
            'content' => $this->buildPowerpointHtml(),
        ]);

        return redirect()->route('online-docs.docs.show', $document);
    }

    public function show(Document $document)
    {
        $this->authorize('view', $document);

        $html = $this->service->getDocumentContent($document);
        $sharedUsers = $this->repository->getSharedUsers($document);
        $shareCandidates = $this->repository->getShareCandidates($document);

        return view('online-docs.show', compact('document', 'html', 'sharedUsers', 'shareCandidates'));
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

    public function importDocx(ImportDocxRequest $request, Document $document)
    {
        $this->authorize('update', $document);

        $this->service->importDocx($document, auth()->user(), $request->file('docx'));

        return redirect()->route('online-docs.docs.show', $document);
    }

    public function exportDocx(Document $document)
    {
        $this->authorize('view', $document);

        $docxPath = $this->service->exportDocx($document);
        $filename = Str::slug($document->title ?: 'document') . '.docx';

        return response()->download($docxPath, $filename);
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

    private function buildPowerpointHtml(): string
    {
        $title = __('online_docs.powerpoint_slide_title');
        $subtitle = __('online_docs.powerpoint_slide_subtitle');

        return '<h1>' . $title . '</h1>'
            . '<p>' . $subtitle . '</p>'
            . '<p></p>';
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
}
