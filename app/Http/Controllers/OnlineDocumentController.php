<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\User;
use App\Services\DocumentConversionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OnlineDocumentController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $ownedDocuments = $user->ownedDocuments()->with('owner')->latest()->get();
        $sharedDocuments = $user->sharedDocuments()->with('owner')->latest()->get();

        return view('online-docs.docs', compact('ownedDocuments', 'sharedDocuments'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

        $document = Document::create([
            'owner_id' => auth()->id(),
            'title' => $data['title'],
            'html_path' => 'pending',
            'last_edited_by' => auth()->id(),
        ]);

        $contentPath = "documents/{$document->id}/content.html";
        Storage::disk('local')->put($contentPath, '');
        $document->update(['html_path' => $contentPath]);

        return redirect()->route('online-docs.docs.show', $document);
    }

    public function show(Document $document)
    {
        $this->authorize('view', $document);

        $html = '';
        if ($document->html_path && Storage::disk('local')->exists($document->html_path)) {
            $html = Storage::disk('local')->get($document->html_path);
        }

        $sharedUsers = $document->sharedUsers()->orderBy('email')->get();
        $shareCandidates = User::query()
            ->whereKeyNot($document->owner_id)
            ->orderBy('name')
            ->orderBy('email')
            ->get(['id', 'name', 'email']);

        return view('online-docs.show', compact('document', 'html', 'sharedUsers', 'shareCandidates'));
    }

    public function update(Request $request, Document $document)
    {
        $this->authorize('update', $document);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
        ]);

        if (!$document->html_path) {
            $contentPath = "documents/{$document->id}/content.html";
            Storage::disk('local')->makeDirectory("documents/{$document->id}");
            $document->update(['html_path' => $contentPath]);
        }

        Storage::disk('local')->put($document->html_path, $data['content'] ?? '');

        $document->update([
            'title' => $data['title'],
            'last_edited_by' => auth()->id(),
        ]);

        if ($request->expectsJson()) {
            return response()->json(['status' => 'ok']);
        }

        return redirect()->back();
    }

    public function importDocx(Request $request, Document $document, DocumentConversionService $converter)
    {
        $this->authorize('update', $document);

        $data = $request->validate([
            'docx' => ['required', 'file', 'mimes:doc,docx', 'max:51200'],
        ]);

        $importPath = $data['docx']->store("documents/{$document->id}/imports");

        $contentPath = $document->html_path ?: "documents/{$document->id}/content.html";
        Storage::disk('local')->makeDirectory("documents/{$document->id}");

        $converter->importDocxToHtml(
            Storage::disk('local')->path($importPath),
            Storage::disk('local')->path($contentPath)
        );

        $document->update([
            'html_path' => $contentPath,
            'docx_path' => $importPath,
            'last_edited_by' => auth()->id(),
        ]);

        return redirect()->route('online-docs.docs.show', $document);
    }

    public function exportDocx(Document $document, DocumentConversionService $converter)
    {
        $this->authorize('view', $document);

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

        $converter->exportHtmlToDocx($htmlPath, $docxPath);

        $filename = Str::slug($document->title ?: 'document') . '.docx';

        return response()->download($docxPath, $filename);
    }

    public function share(Request $request, Document $document)
    {
        $this->authorize('share', $document);

        $data = $request->validate([
            'email' => ['required', 'email'],
            'permission' => ['required', 'in:view,edit'],
        ]);

        $user = User::where('email', $data['email'])->first();
        if (!$user || $user->id === $document->owner_id) {
            return redirect()->back();
        }

        $document->sharedUsers()->syncWithoutDetaching([
            $user->id => ['permission' => $data['permission']],
        ]);

        return redirect()->back();
    }

    public function updateShare(Request $request, Document $document)
    {
        $this->authorize('share', $document);

        $data = $request->validate([
            'user_id' => ['required', 'integer'],
            'permission' => ['required', 'in:view,edit'],
        ]);

        if ((int) $data['user_id'] === (int) $document->owner_id) {
            return redirect()->back();
        }

        $document->sharedUsers()->updateExistingPivot($data['user_id'], [
            'permission' => $data['permission'],
        ]);

        return redirect()->back();
    }

    public function removeShare(Request $request, Document $document)
    {
        $this->authorize('share', $document);

        $data = $request->validate([
            'user_id' => ['required', 'integer'],
        ]);

        if ((int) $data['user_id'] === (int) $document->owner_id) {
            return redirect()->back();
        }

        $document->sharedUsers()->detach($data['user_id']);

        return redirect()->back();
    }
}
