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

    public function index()
    {
        $user = auth()->user();
        $ownedDocuments = $this->repository->getOwnedDocuments($user);
        $sharedDocuments = $this->repository->getSharedDocuments($user);

        return view('online-docs.docs', compact('ownedDocuments', 'sharedDocuments'));
    }

    public function store(StoreDocumentRequest $request)
    {
        $document = $this->service->createDocument(auth()->user(), $request->validated()['title']);

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
}
