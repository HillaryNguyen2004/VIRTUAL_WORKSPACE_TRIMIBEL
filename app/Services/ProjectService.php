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
            'progress' => 0,
            'start_date' => $data['start_date'],
            'due_date' => $data['due_date']
        ]);
    }

    public function getAllProjects(Request $request)
    {
        $activeTab = $request->get('tab', 'projects');

        $perPage = max(1, (int) $request->get('per_page', 5));

        $query = Project::query()->with('staffUser');

        // sort by project title
        $applyProjectSort = function ($q) use ($request) {
            $dir = strtolower((string) $request->get('sort_dir', ''));
            if (in_array($dir, ['asc', 'desc'], true)) {
                $q->orderBy('title', $dir);
            } else {
                $q->orderByDesc('due_date');
            }
        };

        if ($activeTab === 'tasks') {
            // Filters apply to TASKS
            $taskFilters = function ($q) use ($request) {
                if ($request->filled('search')) {
                    $q->where('title', 'like', '%' . $request->search . '%');
                }

                if ($request->filled('status')) {
                    $q->where('status', $request->status);
                }

                if ($request->filled('start_date')) {
                    $q->whereDate('start_date', '>=', $request->start_date);
                }

                if ($request->filled('due_date')) {
                    $q->whereDate('due_date', '<=', $request->due_date);
                }

                $q->orderByDesc('due_date');
            };

            // Only include projects that have tasks matching filters
            if (
                $request->filled('search') ||
                $request->filled('status') ||
                $request->filled('start_date') ||
                $request->filled('due_date')
            ) {
                $query->whereHas('tasks', $taskFilters);
            }

            // Eager-load only matching tasks (and any relations you render)
            $query->with([
                'tasks' => function ($q) use ($taskFilters) {
                    $taskFilters($q);
                    $q->with('assignedUsers');
                }
            ]);

            // Sorting applies to PROJECTS (even on tasks tab)
            $applyProjectSort($query);

            return $query->paginate($perPage)->appends($request->query());
        }

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

    private function ensureStaff(int $userId): void
    {
        $isStaff = User::where('id', $userId)->role('staff')->exists();

        abort_if(!$isStaff, 422, 'Assigned user must be staff');
    }
}
