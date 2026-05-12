<?php

namespace App\Services;

use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use App\Repositories\ProjectRepositoryInterface;

class ProjectService
{
    protected $projectRepo;

    public function __construct(ProjectRepositoryInterface $projectRepo)
    {
        $this->projectRepo = $projectRepo;
    }

    public function createProject(array $data): Project
    {
        // Ensure assigned user is staff
        $this->ensureStaff($data['staff_id']);

        return $this->projectRepo->create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'staff_id' => $data['staff_id'],
            'status' => $data['status'] ?? 'active',
            'percentage' => 0,
            'start_date' => $data['start_date'],
            'due_date' => $data['due_date']
        ]);
    }

    public function getAllProjects(Request $request)
    {
        $perPage = max(1, (int) $request->get('per_page', 5));

        $user = auth()->user();

        $query = Project::query()->with('staffUser');

        if ($user && !$user->hasRole('admin')) {
            // Staff can only see their own projects
            $query->where('staff_id', $user->id);
        }

        // sort by project title
        $applyProjectSort = function ($q) use ($request) {
            $dir = strtolower((string) $request->get('sort_dir', ''));
            if (in_array($dir, ['asc', 'desc'], true)) {
                $q->orderBy('title', $dir);
            } else {
                $q->orderByDesc('due_date');
            }
        };

        // filter projects
        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }
        if ($request->filled('start_date')) {
            $query->whereDate('start_date', '>=', $request->start_date);
        }
        if ($request->filled('due_date')) {
            $query->whereDate('due_date', '<=', $request->due_date);
        }

        $applyProjectSort($query);

        return $query->paginate($perPage)->appends($request->query());
    }

    public function updateProject(int $id, array $data): bool
    {
        $project = $this->getProject($id);

        if (isset($data['staff_id'])) {
            $this->ensureStaff($data['staff_id']);
        }

        return $this->projectRepo->update($project, $data);
    }

    public function deleteProject(int $id): bool
    {
        $project = $this->getProject($id);

        // Optional rule: prevent delete if tasks exist
        if ($project->tasks()->exists()) {
            abort(422, 'Cannot delete project with existing tasks');
        }

        return $this->projectRepo->delete($project);
    }

    public function getProject(int $id): Project
    {
        $project = $this->projectRepo->find($id);

        if (!$project) {
            abort(404, 'Project not found');
        }

        return $project;
    }

    public function getProjectTasks(int $projectId, int $perPage = 10)
    {
        return \App\Models\Task::where('project_id', $projectId)
            ->whereNull('parent_id') // Only root tasks
            ->with(['assignedUsers', 'subtasks.assignedUsers', 'subtasks.readStatuses', 'phase', 'readStatuses'])
            // Order by phase_id so tasks in the same phase are grouped together. 
            // Phase-less tasks (null) usually come first or last depending on DB, 
            // so we might need a raw order if specific placement is needed. 
            // For now, simple ordering is sufficient.
            ->orderBy('phase_id', 'asc')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    private function ensureStaff(int $userId): void
    {
        $isStaff = User::where('id', $userId)->role('staff')->exists();

        abort_if(!$isStaff, 422, 'Assigned user must be staff');
    }
}
