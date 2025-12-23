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
        $query = Project::with('staff');

        // SEARCH (by project title)
        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        // FILTER: start_date (>=)
        if ($request->filled('start_date')) {
            $query->whereDate('start_date', '>=', $request->start_date);
        }

        // FILTER: due_date (<=)
        if ($request->filled('due_date')) {
            $query->whereDate('due_date', '<=', $request->due_date);
        }

        // SORT: title A->Z / Z->A
        $dir = strtolower((string) $request->get('sort_dir', ''));
        if (in_array($dir, ['asc', 'desc'], true)) {
            $query->orderBy('title', $dir);
        } else {
            // default sort (avoid created_at if your table doesn't have it)
            $query->orderByDesc('id');
        }

        $perPage = max(1, (int) $request->get('per_page', 3));

        return $query
            ->paginate($perPage)
            ->appends($request->query());
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
