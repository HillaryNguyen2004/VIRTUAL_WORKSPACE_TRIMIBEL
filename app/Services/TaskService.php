<?php

namespace App\Services;

use App\Models\Task;
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
     * GET ALL TASKS (ADMIN)
     * ==============================
     */
    public function getAllTasks()
    {
        return $this->taskRepo->all();
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
                'title'       => $data['title'],
                'description' => $data['description'] ?? null,
                'project_id'  => $data['project_id'],
                'due_date'    => $data['due_date'],
                'status'      => 'pending',
                'active'      => $data['active'] ?? 0,
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
                'title'       => $data['title'],
                'description' => $data['description'] ?? null,
                'project_id'  => $data['project_id'],
                'due_date'    => $data['due_date'],
                'active'      => $data['active'] ?? 0,
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
     * STAFF TASK LIST
     * ==============================
     */
    public function getTasksForStaff(Request $request, int $userId)
    {
        $query = $this->taskRepo->getTasksForUser($userId);

        // Search
        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Sorting
        switch ($request->sort) {
            case 'name_asc':
                $query->orderBy('title', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('title', 'desc');
                break;
            case 'due_asc':
                $query->orderBy('due_date', 'asc');
                break;
            case 'due_desc':
            default:
                $query->orderBy('due_date', 'desc');
                break;
        }

        return $query
            ->with(['assignedUsers', 'project'])
            ->paginate(3)
            ->appends($request->query());
    }

    /**
     * ==============================
     * UPCOMING TASKS
     * ==============================
     */
    public function getUpcomingTasks(int $userId)
    {
        return $this->taskRepo->getUpcomingTasks($userId);
    }

    /**
     * ==============================
     * ADMIN FILTERED TASKS
     * ==============================
     */
    public function getFilteredTasks(Request $request)
    {
        $query = Task::with(['assignedUsers', 'project']);

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('due_date')) {
            $query->whereDate('due_date', $request->due_date);
        }

        if ($request->filled('assigned_user_id')) {
            $query->whereHas('assignedUsers', function ($q) use ($request) {
                $q->where('users.id', $request->assigned_user_id);
            });
        }

        return $query->orderBy('due_date', 'desc')->paginate(3);
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

    
}
