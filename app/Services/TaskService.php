<?php

namespace App\Services;

use App\Models\Task;
use App\Models\Project;
use App\Models\User;
use App\Repositories\TaskRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;


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
    // public function createTask(array $data): Task
    // {
    //     return DB::transaction(function () use ($data) {

    //         // 1. Create task
    //         $task = $this->taskRepo->create([
    //             'title' => $data['title'],
    //             'description' => $data['description'] ?? null,
    //             'project_id' => $data['project_id'],
    //             'start_date' => $data['start_date'],
    //             'due_date' => $data['due_date'],
    //             'status' => 'pending',
    //             'active' => $data['active'] ?? 1,
    //         ]);

    //         // 2. Attach assignee (single)
    //         if (!empty($data['assignee'])) {
    //             $task->assignedUsers()->attach($data['assignee']);
    //         }

    //         return $task;
    //     });
    // }

    public function createTask(array $data, ?int $index = null): Task
    {
        return DB::transaction(function () use ($data, $index) {

            $today = now()->startOfDay();
            $startDate = \Carbon\Carbon::parse($data['start_date'])->startOfDay();
            $dueDate = \Carbon\Carbon::parse($data['due_date'])->startOfDay();

            $startKey = $index !== null ? "tasks.$index.start_date" : "start_date";
            $dueKey = $index !== null ? "tasks.$index.due_date" : "due_date";

            // if ($startDate->lt($today)) {
            //     throw ValidationException::withMessages([
            //         $startKey => 'Start date cannot be in the past.',
            //     ]);
            // }

            if ($dueDate->lt($startDate)) {
                throw ValidationException::withMessages([
                    $dueKey => 'Due date must be after the start date.',
                ]);
            }

            $task = $this->taskRepo->create([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'project_id' => $data['project_id'] ?? null,
                'parent_id' => $data['parent_id'] ?? null,
                'phase_id' => $data['phase_id'] ?? null,
                'start_date' => $data['start_date'],
                'due_date' => $data['due_date'],
                'status' => 'pending',
                'priority' => $data['priority'] ?? 'normal',
                'estimated_time' => $data['estimated_time'] ?? 1,
                'active' => $data['active'] ?? 1,
            ]);

            if (!empty($data['assignee'])) {
                $task->assignedUsers()->attach($data['assignee']);
            }

            // ---- Recalculate parentTask completion ----
            if ($task->parent_id) {
                $parent = $task->parentTask;

                if ($parent) {
                    // recalc parent percentage from subtasks
                    $parent->recalculateCompletion();

                    // if subtask started but parent still pending => parent becomes in_progress
                    if ($task->status !== 'pending' && $parent->status === 'pending') {
                        $parent->update(['status' => 'in_progress']);
                    }

                    // if all active subtasks completed => parent completed
                    $activeCount = $parent->subTasks()->where('active', 1)->count();
                    $completedCount = $parent->subTasks()
                        ->where('active', 1)
                        ->where('status', 'completed')
                        ->count();

                    if ($activeCount > 0 && $activeCount === $completedCount) {
                        $parent->update([
                            'status' => 'completed',
                            'percentage' => 100,
                        ]);
                    }
                }
            }

            // ---- Recalculate project completion ----
            // Recalculate project whether it's a top-level task or a subtask
            // If it's a subtask, it might have updated its parent, which affects project percentage
            if ($task->project) {
                $task->project->recalculateCompletion();
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
    // public function updateTask($id, array $data): Task
    // {
    //     return DB::transaction(function () use ($id, $data) {

    //         $task = $this->taskRepo->find($id);

    //         // 1. Update task fields (only those provided)
    //         $updateData = [];
    //         if (isset($data['title'])) {
    //             $updateData['title'] = $data['title'];
    //         }
    //         if (isset($data['description'])) {
    //             $updateData['description'] = $data['description'];
    //         }
    //         if (isset($data['project_id'])) {
    //             $updateData['project_id'] = $data['project_id'];
    //         }
    //         if (isset($data['start_date'])) {
    //             $updateData['start_date'] = $data['start_date'];
    //         }
    //         if (isset($data['due_date'])) {
    //             $updateData['due_date'] = $data['due_date'];
    //         }
    //         if (isset($data['active'])) {
    //             $updateData['active'] = $data['active'];
    //         }
    //         if (isset($data['status'])) {
    //             $updateData['status'] = $data['status'];
    //         }

    //         $this->taskRepo->update($task, $updateData);

    //         // 2. Sync assignee
    //         if (isset($data['assignee'])) {
    //             $task->assignedUsers()->sync([$data['assignee']]);
    //         }

    //         if ($task->project) {
    //             $task->project->recalculateCompletion();
    //         }
    //         return $task->refresh();
    //     });
    // }

    public function updateTask($id, array $data): Task
    {
        return DB::transaction(function () use ($id, $data) {

            $task = $this->taskRepo->find($id);

            // ---- Determine effective dates ----
            $startDate = array_key_exists('start_date', $data)
                ? Carbon::parse($data['start_date'])->startOfDay()
                : ($task->start_date ? Carbon::parse($task->start_date)->startOfDay() : null);

            $dueDate = array_key_exists('due_date', $data)
                ? Carbon::parse($data['due_date'])->startOfDay()
                : ($task->due_date ? Carbon::parse($task->due_date)->startOfDay() : null);

            // ---- Date validation ----
            // Always enforce due_date >= start_date (if both exist)
            if ($startDate && $dueDate && $dueDate->lt($startDate)) {
                throw ValidationException::withMessages([
                    'due_date' => 'Due date must be after start date.',
                ]);
            }

            // ---- If project_id / assignee changes, enforce team rule ----
            $projectId = $data['project_id'] ?? $task->project_id;

            if (array_key_exists('assignee', $data) || array_key_exists('project_id', $data)) {
                $assigneeId = $data['assignee'] ?? null;

                if ($assigneeId) {
                    $projectStaffId = Project::whereKey($projectId)->value('staff_id');
                    $assigneeLeaderId = User::whereKey($assigneeId)->value('team_leader_id');

                    if ($projectStaffId && (int) $assigneeLeaderId !== (int) $projectStaffId) {
                        throw ValidationException::withMessages([
                            'assignee' => "Assignee is not in the selected project's team.",
                        ]);
                    }
                }
            }

            // ---- Update fields (only provided) ----
            $updateData = [];

            foreach (['title', 'description', 'project_id', 'parent_id', 'phase_id', 'start_date', 'due_date', 'active', 'status', 'priority', 'percentage', 'estimated_time', 'score'] as $field) {
                if (array_key_exists($field, $data)) {
                    $updateData[$field] = $data[$field];
                }
            }

            // if percentage > 0 and < 100 and status is not in progress then change status to in progress
            if ($updateData['percentage'] > 0 && $updateData['percentage'] < 100 && $updateData['status'] !== 'in_progress') {
                $updateData['status'] = 'in_progress';
            }

            // if status is complated then change percentage to 100
            else if (($updateData['status'] ?? null) === 'completed') {
                $updateData['percentage'] = 100;
            }

            $this->taskRepo->update($task, $updateData);

            // ---- Sync assignee ----
            // If assignee is present in payload:
            // - if null/empty => clear
            // - else sync that one
            if (array_key_exists('assignee', $data)) {
                $assigneeId = $data['assignee'];

                if ($assigneeId) {
                    $task->assignedUsers()->sync([(int) $assigneeId]);
                } else {
                    $task->assignedUsers()->sync([]); // clear assignment
                }
            }

            // ---- Recalculate parentTask completion ----
            if ($task->parent_id) {
                $parent = $task->parentTask;

                if ($parent) {
                    // recalc parent percentage from subtasks
                    $parent->recalculateCompletion();

                    // get effective status after update
                    $newStatus = $updateData['status'] ?? $task->status;

                    // if subtask started but parent still pending => parent becomes in_progress
                    if ($newStatus !== 'pending' && $parent->status === 'pending') {
                        $parent->update(['status' => 'in_progress']);
                    }

                    // if all active subtasks completed => parent completed
                    $activeCount = $parent->subTasks()->where('active', 1)->count();
                    $completedCount = $parent->subTasks()
                        ->where('active', 1)
                        ->where('status', 'completed')
                        ->count();

                    if ($activeCount > 0 && $activeCount === $completedCount) {
                        $parent->update([
                            'status' => 'completed',
                            'percentage' => 100,
                        ]);
                    }
                }
            }

            // ---- Recalculate project completion ----
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
        if (!$task) {
            return false;
        }

        $parent = $task->parent_id ? $task->parentTask : null;
        $project = $task->project;

        // Detach users first (safe)
        $task->assignedUsers()->detach();
        $deleted = $this->taskRepo->delete($task);

        if ($deleted) {
            if ($parent) {
                $parent->recalculateCompletion();
            }
            if ($project) {
                $project->recalculateCompletion();
            }
        }

        return $deleted;
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

        if ($task->parent_id) {
            $parent = $task->parentTask;
            if ($parent) {
                $parent->recalculateCompletion();

                // if subtask started but parent still pending => parent becomes in_progress
                if ($status !== 'pending' && $parent->status === 'pending') {
                    $parent->update(['status' => 'in_progress']);
                }

                // if all active subtasks completed => parent completed
                $activeCount = $parent->subTasks()->where('active', 1)->count();
                $completedCount = $parent->subTasks()
                    ->where('active', 1)
                    ->where('status', 'completed')
                    ->count();

                if ($activeCount > 0 && $activeCount === $completedCount) {
                    $parent->update([
                        'status' => 'completed',
                        'percentage' => 100,
                    ]);
                }
            }
        }

        if ($task->project) {
            $task->project->recalculateCompletion();
        }

        return $task;
    }

    public function getFilteredProjectsWithTasks(Request $request, $user)
    {
        $sortDir = strtolower((string) $request->get('sort_dir', ''));
        $sortDir = in_array($sortDir, ['asc', 'desc'], true) ? $sortDir : null;

        $applyTaskFilters = function ($taskQuery) use ($request) {
            if ($request->filled('search')) {
                $taskQuery->where('title', 'like', '%' . $request->search . '%');
            }
            if ($request->filled('status')) {
                $taskQuery->where('status', $request->status);
            }
            if ($request->filled('start_date')) {
                $taskQuery->whereDate('start_date', '>=', $request->start_date);
            }
            if ($request->filled('due_date')) {
                $taskQuery->whereDate('due_date', '<=', $request->due_date);
            }

            $taskQuery->with(['assignedUsers', 'readStatuses', 'subtasks.assignedUsers', 'subtasks.readStatuses']);
        };

        $projectsQuery = Project::query()
            ->with([
                'staffUser',
                'tasks' => function ($taskQuery) use ($applyTaskFilters, $user) {
                    $applyTaskFilters($taskQuery);

                    if (!$user->hasRole('admin')) {
                        $taskQuery->where(function ($t) use ($user) {
                            $t->whereHas('project', fn($p) => $p->where('staff_id', $user->id))
                                ->orWhereHas('assignedUsers', fn($u) => $u->whereKey($user->id));
                        });
                    }

                    $taskQuery->orderByDesc('due_date');
                }
            ]);

        if (!$user->hasRole('admin')) {
            $projectsQuery->where(function ($q) use ($user) {
                $q->where('staff_id', $user->id)
                    ->orWhereHas(
                        'tasks',
                        fn($t) =>
                        $t->whereHas('assignedUsers', fn($u) => $u->whereKey($user->id))
                    );
            });
        }

        if ($request->filled('project_id')) {
            $projectsQuery->where('id', $request->project_id);
        }

        $hasTaskFilters =
            $request->filled('search') ||
            $request->filled('status') ||
            $request->filled('start_date') ||
            $request->filled('due_date');

        if ($hasTaskFilters) {
            $projectsQuery->whereHas('tasks', function ($q) use ($applyTaskFilters, $user) {
                $applyTaskFilters($q);

                if (!$user->hasRole('admin')) {
                    $q->where(function ($t) use ($user) {
                        $t->whereHas('project', fn($p) => $p->where('staff_id', $user->id))
                            ->orWhereHas('assignedUsers', fn($u) => $u->whereKey($user->id));
                    });
                }
            });
        }

        $projectsQuery->orderBy('due_date', $sortDir ?? 'desc');

        $perPage = max(1, (int) $request->get('per_page', 5));
        return $projectsQuery->paginate($perPage)->appends($request->query());
    }
    public function getFilteredTasks(Request $request, $user)
    {
        $sortDir = strtolower((string) $request->get('sort_dir', ''));
        $sortDir = in_array($sortDir, ['asc', 'desc'], true) ? $sortDir : 'desc';

        $query = Task::query()
            ->whereNull('parent_id') // Only top-level tasks for the main list
            ->with(['project.staffUser', 'assignedUsers', 'readStatuses', 'subtasks.assignedUsers', 'subtasks.readStatuses']);

        // Role-based filtering
        if (!$user->hasRole('admin')) {
            $query->where(function ($q) use ($user) {
                // User can see tasks if they are the staff (project leader) or assigned to it
                $q->whereHas('project', fn($p) => $p->where('staff_id', $user->id))
                    ->orWhereHas('assignedUsers', fn($u) => $u->whereKey($user->id));
            });
        }

        // Filters
        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('start_date')) {
            $query->whereDate('start_date', '>=', $request->start_date);
        }
        if ($request->filled('due_date')) {
            $query->whereDate('due_date', '<=', $request->due_date);
        }
        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        // Sorting
        $query->orderBy('title', $sortDir);

        $perPage = max(1, (int) $request->get('per_page', 5));
        return $query->paginate($perPage)->appends($request->query());
    }
}
