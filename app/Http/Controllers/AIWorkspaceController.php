<?php

namespace App\Http\Controllers;

use App\Models\AIWorkspace;
use App\Models\AIWorkspaceFile;
use App\Services\WorkspaceService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AIWorkspaceController extends Controller
{
    protected WorkspaceService $workspaceService;

    public function __construct(WorkspaceService $workspaceService)
    {
        $this->workspaceService = $workspaceService;
        $this->middleware('auth');
    }

    /**
     * Display a listing of workspaces
     */
    public function index(Request $request): View
    {
        $workspaces = $this->workspaceService->getAllWorkspaces($request);

        return view('ai.workspaces.index', compact('workspaces'));
    }

    /**
     * Show the form for creating a new workspace
     */
    public function create(): View
    {
        return view('ai.workspaces.create');
    }

    /**
     * Store a newly created workspace
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'visibility' => 'required|in:private,team,public',
        ]);

        $validated['user_id'] = auth()->id();

        $workspace = $this->workspaceService->createWorkspace($validated);

        return redirect()
            ->route('ai-workspaces.show', $workspace)
            ->with('success', __('ai.workspace_created_success'));
    }

    /**
     * Display the specified workspace
     */
    public function show(AIWorkspace $workspace): View
    {
        $this->authorize('view', $workspace);

        $files = $workspace->files()
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $stats = $this->workspaceService->getWorkspaceStats($workspace);

        return view('ai.workspaces.show', compact('workspace', 'files', 'stats'));
    }

    /**
     * Show the form for editing the workspace
     */
    public function edit(AIWorkspace $workspace): View
    {
        $this->authorize('update', $workspace);

        return view('ai.workspaces.edit', compact('workspace'));
    }

    /**
     * Update the specified workspace
     */
    public function update(Request $request, AIWorkspace $workspace)
    {
        $this->authorize('update', $workspace);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'visibility' => 'required|in:private,team,public',
        ]);

        $this->workspaceService->updateWorkspace($workspace, $validated);

        return redirect()
            ->route('ai-workspaces.show', $workspace)
            ->with('success', __('ai.workspace_updated_success'));
    }

    /**
     * Delete the specified workspace
     */
    public function destroy(AIWorkspace $workspace)
    {
        $this->authorize('delete', $workspace);

        $this->workspaceService->deleteWorkspace($workspace);

        return redirect()
            ->route('ai-workspaces.index')
            ->with('success', __('ai.workspace_deleted_success'));
    }

    /**
     * Upload files to workspace
     */
    public function uploadFiles(Request $request, AIWorkspace $workspace)
    {
        $this->authorize('update', $workspace);

        $allowedMimes = implode(',', AIWorkspaceFile::supportedFormats());

        $validated = $request->validate([
            'files.*' => 'required|file|mimes:' . $allowedMimes,
        ]);

        $files = $request->file('files', []);

        // Ensure files is an array
        if (!is_array($files)) {
            $files = [$files];
        }

        $result = $this->workspaceService->uploadFiles($workspace, $files);

        $message = __(
            'ai.files_uploaded',
            ['count' => count($result['uploaded'])]
        );

        // Always show errors if they exist
        if (!empty($result['errors'])) {
            $errorMessages = [];
            foreach ($result['errors'] as $error) {
                if (is_array($error)) {
                    $errorMessages[] = "{$error['file']}: {$error['error']}";
                } else {
                    $errorMessages[] = (string) $error;
                }
            }

            return back()
                ->with('warning', $message ?: __('ai.upload_failed'))
                ->with('upload_errors', $errorMessages);
        }

        // Show success only if files were actually uploaded
        if (count($result['uploaded']) > 0) {
            return back()->with('success', $message);
        }

        return back()->with('warning', __('ai.no_files_uploaded'));
    }

    /**
     * Ingest files in workspace
     */
    public function ingestFiles(Request $request, AIWorkspace $workspace)
    {
        $this->authorize('update', $workspace);

        $results = $this->workspaceService->ingestWorkspace($workspace);

        $successCount = collect($results)
            ->where('status', 'completed')
            ->count();

        $message = __('ai.ingest_completed', ['count' => $successCount]);

        return back()
            ->with('success', $message)
            ->with('ingest_results', $results);
    }

    /**
     * Delete a file from workspace
     */
    public function deleteFile(AIWorkspaceFile $file)
    {
        $workspace = $file->workspace;
        $this->authorize('update', $workspace);

        $this->workspaceService->deleteFile($file);

        return back()->with('success', __('ai.file_deleted_success'));
    }

    /**
     * Export workspace data
     */
    public function export(AIWorkspace $workspace)
    {
        $this->authorize('view', $workspace);

        $files = $workspace->files()->get();
        $stats = $this->workspaceService->getWorkspaceStats($workspace);

        return response()->json([
            'workspace' => $workspace,
            'files' => $files,
            'statistics' => $stats,
        ]);
    }
}
