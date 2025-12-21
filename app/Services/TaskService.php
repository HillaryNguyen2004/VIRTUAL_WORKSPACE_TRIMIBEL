<?php

namespace App\Services;

use App\Models\Task;
use App\Models\Project;
use App\Repositories\TaskRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaskService
{
    protected TaskRepositoryInterface $taskRepo;

    public function __construct(TaskRepositoryInterface $taskRepo)
    {
        $this->taskRepo = $taskRepo;
    }

    /**
     * ==============================
     * CREATE TASK
     * ==============================
     * Handles:
     * - project_id
     * - task creation
     * - attach assignees
     */
    public function createTask(array $data): Task
    {
        return DB::transaction(function () use ($data) {

            // 1. Create task
            $task = $this->taskRepo->create([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'project_id' => $data['project_id'],
                'start_date' => $data['start_date'],
                'due_date' => $data['due_date'],
                'status' => 'pending',
                'active' => $data['active'] ?? 0,
            ]);

            // 2. Attach users (pivot table)
            if (!empty($data['assignees'])) {
                $task->assignedUsers()->sync($data['assignees']);
            }

            return $task;
        });
    }

    /**
     * ==============================
     * GET TASK BY ID
     * ==============================
     */
    public function getTaskById($id): Task
    {
        return $this->taskRepo->find($id);
    }

    /**
     * ==============================
     * UPDATE TASK
     * ==============================
     * Handles:
     * - project change
     * - assignee sync
     * - task fields update
     */
    public function updateTask($id, array $data): Task
    {
        return DB::transaction(function () use ($id, $data) {

            $task = $this->taskRepo->find($id);

            // 1. Update task fields
            $this->taskRepo->update($task, [
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'project_id' => $data['project_id'],
                'start_date' => $data['start_date'],
                'due_date' => $data['due_date'],
                'active' => $data['active'] ?? 0,
            ]);

            // 2. Sync assignees
            if (isset($data['assignees'])) {
                $task->assignedUsers()->sync($data['assignees']);
            }

            if ($task->project) {
                $task->project->recalculateCompletion();
            }
            return $task->refresh();
        });
    }

    /**
     * ==============================
     * DELETE TASK
     * ==============================
     */
    public function deleteTask($id): bool
    {
        $task = $this->taskRepo->find($id);

        // Detach users first (safe)
        $task->assignedUsers()->detach();

        return $this->taskRepo->delete($task);
    }

    /**
     * ==============================
     * UPDATE STATUS (AJAX)
     * ==============================
     */
    // public function updateStatus(int $taskId, string $status, ?int $percentage = null): ?Task
    // {
    //     $task = $this->taskRepo->updateStatus($taskId, $status, $percentage);

    //     if ($task && $task->project) {
    //         $task->project->recalculateCompletion();
    //     }

    //     return $task;
    // }

    public function updateStatus(int $taskId, string $status, ?int $percentage = null): ?Task
    {
        // 1. Update task FIRST
        $this->taskRepo->updateStatus($taskId, $status, $percentage);

        // 2. Reload task with fresh data
        $task = Task::with('project')->find($taskId);

        if (!$task) {
            return null;
        }

        // 3. Recalculate project progress
        if ($task->project) {
            $task->project->recalculateCompletion();
        }

        return $task;
    }

    public function getFilteredProjectsWithTasks(Request $request, $user)
    {
        $sortDir = strtolower($request->get('sort_dir', ''));
        $sortDir = in_array($sortDir, ['asc', 'desc'], true) ? $sortDir : null;

        $projectsQuery = Project::query()
            ->with([
                'staff',
                'tasks' => function ($taskQuery) use ($request, $sortDir) {

                    // Search by task title
                    if ($request->filled('search')) {
                        $taskQuery->where('title', 'like', '%' . $request->search . '%');
                    }

                    // Filter by task start_date (>=)
                    if ($request->filled('start_date')) {
                        $taskQuery->whereDate('start_date', '>=', $request->start_date);
                    }

                    // Filter by task due_date (<=)
                    if ($request->filled('due_date')) {
                        $taskQuery->whereDate('due_date', '<=', $request->due_date);
                    }

                    // Sort by task name (title)
                    if ($sortDir) {
                        $taskQuery->orderBy('title', $sortDir);
                    } else {
                        $taskQuery->orderByDesc('id'); // default
                    }

                    $taskQuery->with('assignedUsers');
                }
            ])
            ->orderByDesc('id'); // projects ordering (no created_at)

        // Role restriction
        if (!$user->hasRole('admin')) {
            $projectsQuery->where('staff_id', $user->id);
        }

        if ($request->filled('project_id')) {
            $projectsQuery->where('id', $request->project_id);
        }

        // If any task filter is set, only keep projects that have matching tasks
        $hasTaskFilters = $request->filled('search') || $request->filled('start_date') || $request->filled('due_date');

        if ($hasTaskFilters) {
            $projectsQuery->whereHas('tasks', function ($taskQuery) use ($request) {
                if ($request->filled('search')) {
                    $taskQuery->where('title', 'like', '%' . $request->search . '%');
                }
                if ($request->filled('start_date')) {
                    $taskQuery->whereDate('start_date', '>=', $request->start_date);
                }
                if ($request->filled('due_date')) {
                    $taskQuery->whereDate('due_date', '<=', $request->due_date);
                }
            });
        }

        // Paginate projects (optional but recommended)
        $perPage = max(1, (int) $request->get('per_page', 10));

        return $projectsQuery->paginate($perPage)->appends($request->query());
    }
}
