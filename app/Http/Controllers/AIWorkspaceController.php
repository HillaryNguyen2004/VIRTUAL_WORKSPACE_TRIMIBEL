<?php

namespace App\Http\Controllers;

use App\Models\AIWorkspace;
use App\Models\AIWorkspaceFile;
use App\Services\AIWorkspaceService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AIWorkspaceController extends Controller
{
    private const INGEST_MAX_EXECUTION_TIME = 600;

    protected AIWorkspaceService $workspaceService;

    public function __construct(AIWorkspaceService $workspaceService)
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
        $this->authorize('upload', $workspace);

        $allowedMimes = implode(',', AIWorkspaceFile::supportedFormats());

        $validated = $request->validate([
            'files.*' => 'required|file|mimes:' . $allowedMimes . '|max:524288', // 512MB max file size
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
        $this->authorize('ingest', $workspace);

        // Ingest can run longer for large files/workspaces.
        @set_time_limit(self::INGEST_MAX_EXECUTION_TIME);

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
     * Preview a workspace file from S3.
     */
    public function previewFile(AIWorkspaceFile $file)
    {
        $workspace = $file->workspace;
        $this->authorize('view', $workspace);

        $s3Path = $file->getAttribute('file_path');
        if (!$s3Path) {
            abort(404, 'File path not found');
        }

        // Check if file exists in S3
        if (!Storage::disk('s3')->exists($s3Path)) {
            abort(404, 'File not found in storage');
        }

        $displayName = $file->getAttribute('original_name') ?: $file->getAttribute('file_name');
        $fileName = addslashes((string) $displayName);
        $mimeType = $file->getAttribute('mime_type') ?: 'application/octet-stream';

        // Stream file from S3 for preview
        $content = Storage::disk('s3')->get($s3Path);

        return response($content)
            ->header('Content-Type', $mimeType)
            ->header('Content-Disposition', "inline; filename=\"$fileName\"")
            ->header('Cache-Control', 'no-cache, must-revalidate');
    }

    /**
     * Download a workspace file from S3.
     */
    public function downloadFile(AIWorkspaceFile $file)
    {
        $workspace = $file->workspace;
        $this->authorize('view', $workspace);

        $s3Path = $file->getAttribute('file_path');
        if (!$s3Path) {
            abort(404, 'File path not found');
        }

        // Check if file exists in S3
        if (!Storage::disk('s3')->exists($s3Path)) {
            abort(404, 'File not found in storage');
        }

        $downloadName = (string) ($file->getAttribute('original_name') ?: $file->getAttribute('file_name'));
        $mimeType = $file->getAttribute('mime_type') ?: 'application/octet-stream';

        // Stream file from S3 for download
        $content = Storage::disk('s3')->get($s3Path);

        return response($content)
            ->header('Content-Type', $mimeType)
            ->header('Content-Disposition', "attachment; filename=\"$downloadName\"")
            ->header('Content-Length', Storage::disk('s3')->size($s3Path));
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
