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
        return \App\Models\Project::with('staff')
            ->orderBy('created_at', 'desc')
            ->paginate(3);
    }


    public function updateProject(int $id, array $data): Project
    {
        $project = $this->getProject($id);

        if (isset($data['staff_id'])) {
            $this->ensureStaff($data['staff_id']);
        }

        return $this->projectRepo->updateProject($project, $data);
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
