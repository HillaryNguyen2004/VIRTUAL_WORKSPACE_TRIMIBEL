<?php

namespace App\Http\Controllers;

use App\Services\ProjectService;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;

class ProjectController extends Controller
{
    protected $projectService;

    public function __construct(ProjectService $projectService)
    {
        $this->projectService = $projectService;
    }

    public function index(Request $request)
    {
        $projects = $this->projectService->getAllProjects($request);

        return view('projects.index', compact('projects'));
    }

    /**
     * Show project details
     */
    public function details(int $id)
    {
        $project = $this->projectService->getProject($id);
        // Load relationships needed for the project info itself
        $project->load(['staffUser', 'phases']);

        // Fetch paginated tasks separately
        $tasks = $this->projectService->getProjectTasks($id, 5);

        return view('projects.details', compact('project', 'tasks'));
    }

    /**
     * Show project in Kanban board view
     */
    public function kanban(int $id)
    {
        $project = $this->projectService->getProject($id);
        $project->load(['staffUser', 'phases.tasks.assignedUsers']);
        
        $phases = $project->phases;

        return view('projects.kanban', compact('project', 'phases'));
    }
    

    /**
     * Show create form
     */
    public function create()
    {
        $staffUsers = User::role('staff')->get();
        return view('projects.create', compact('staffUsers'));
    }

    /**
     * Store new project (Admin)
     */
    public function store(StoreProjectRequest $request)
    {
        $this->projectService->createProject($request->validated());

        return redirect()
            ->route('projects.create')
            ->with('success', __('messages.project_created'));
    }

    /**
     * Show edit form
     */
    public function edit(int $id)
    {
        $project = $this->projectService->getProject($id);
        $staffUsers = User::role('staff')->get();

        return view('projects.edit', compact('project', 'staffUsers'));
    }

    /**
     * Update project
     */
    public function update(UpdateProjectRequest $request, int $id)
    {
        $this->projectService->updateProject($id, $request->validated());

        return redirect()
            ->route('projects.edit', $id)
            ->with('success', __('messages.project_updated'));
    }

    /**
     * Delete project
     */
    public function destroy(int $id)
    {
        $this->projectService->deleteProject($id);

        return redirect()
            ->route('projects.index')
            ->with('success', __('messages.project_deleted'));
    }
}
