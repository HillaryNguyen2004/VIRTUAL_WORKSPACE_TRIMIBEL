<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Services\TaskService;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Project;
use Illuminate\Validation\ValidationException;

class TaskController extends Controller
{
    protected TaskService $taskService;

    public function __construct(TaskService $taskService)
    {
        $this->taskService = $taskService;
    }

    /**
     * ==============================
     * CREATE TASK
     * ==============================
     */
    public function create()
    {
        $user = auth()->user();

        if ($user->hasRole('admin')) {

            $assignees = User::whereDoesntHave('roles', fn($q) => $q->where('name', 'admin'))
                ->get(['id', 'name', 'team_leader_id']);

            $projects = Project::get(['id', 'title', 'staff_id']);

        } elseif ($user->hasRole('staff')) {

            $assignees = User::where('team_leader_id', $user->id)
                ->get(['id', 'name', 'team_leader_id']);

            $projects = Project::where('staff_id', $user->id)
                ->get(['id', 'title', 'staff_id']);

        } else {
            // role: user
            $leaderId = $user->team_leader_id; // staffId of this user

            $projects = Project::query()
                ->when($leaderId, fn($q) => $q->where('staff_id', $leaderId))
                ->get(['id', 'title', 'staff_id']);

            // Assignee is only this user
            $assignees = User::whereKey($user->id)
                ->get(['id', 'name', 'team_leader_id']);
        }

        // for selects
        $projectOptions = $projects->mapWithKeys(fn($p) => [$p->id => $p->title])->toArray();

        // maps for JS
        $projectLeaderMap = $projects->pluck('staff_id', 'id'); // [projectId => staffId]

        $assigneesByLeader = $assignees
            ->groupBy('team_leader_id')
            ->map(fn($group) => $group->map(fn($u) => ['id' => $u->id, 'name' => $u->name])->values())
            ->toArray();

        return view('tasks.create', compact(
            'assignees',
            'projects',
            'projectOptions',
            'projectLeaderMap',
            'assigneesByLeader'
        ));
    }

    /**
     * ==============================
     * STORE TASK
     * ==============================
     */
    public function store(StoreTaskRequest $request)
    {
        $validated = $request->validated();

        if (isset($validated['tasks'])) {
            foreach ($validated['tasks'] as $i => $taskData) {
                $this->taskService->createTask($taskData, $i); // pass index
            }
        } else {
            $this->taskService->createTask($validated, null);
        }

        return redirect()
            ->route('tasks.create')
            ->with('success', __('messages.task_created'));
    }

    /**
     * ==============================
     * SHOW TASK
     * ==============================
     */
    public function show($id)
    {
        $task = $this->taskService->getTaskById($id);

        return view('tasks.show', compact('task'));
    }

    /**
     * ==============================
     * EDIT TASK
     * ==============================
     */
    public function edit($id)
    {
        $task = $this->taskService->getTaskById($id);
        $user = auth()->user();

        if ($user->hasRole('admin')) {

            $assignees = User::whereDoesntHave('roles', fn($q) => $q->where('name', 'admin'))
                ->get(['id', 'name', 'team_leader_id']);

            $projects = Project::get(['id', 'title', 'staff_id']);

        } elseif ($user->hasRole('staff')) {

            $assignees = User::where('team_leader_id', $user->id)
                ->get(['id', 'name', 'team_leader_id']);

            $projects = Project::where('staff_id', $user->id)
                ->get(['id', 'title', 'staff_id']);

        } else {
            // role: user
            $leaderId = $user->team_leader_id; // staffId of this user

            // If user has no leader, return empty options (or handle as you prefer)
            $projects = Project::query()
                ->when($leaderId, fn($q) => $q->where('staff_id', $leaderId))
                ->get(['id', 'title', 'staff_id']);

            // Assignee is only this user
            $assignees = User::whereKey($user->id)
                ->get(['id', 'name', 'team_leader_id']);
        }

        $projectOptions = $projects->mapWithKeys(fn($p) => [$p->id => $p->title])->toArray();

        $projectLeaderMap = $projects->pluck('staff_id', 'id'); // [projectId => staffId]

        $assigneesByLeader = $assignees
            ->groupBy('team_leader_id')
            ->map(fn($group) => $group->map(fn($u) => ['id' => $u->id, 'name' => $u->name])->values())
            ->toArray();

        return view('tasks.edit', compact(
            'task',
            'projects',
            'assignees',
            'projectOptions',
            'projectLeaderMap',
            'assigneesByLeader'
        ));
    }

    /**
     * ==============================
     * UPDATE TASK
     * ==============================
     */
    // public function update(UpdateTaskRequest $request, $id)
    // {
    //     $this->taskService->updateTask($id, $request->validated());

    //     return auth()->user()->hasRole('admin')
    //         ? redirect()->route('tasks.index')->with('success', __('messages.task_updated'))
    //         : redirect()->route('tasks.staff.index')->with('success', __('messages.task_updated'));
    // }
    // public function update(UpdateTaskRequest $request, $id)
    // {
    //     $this->taskService->updateTask($id, $request->validated());

    //     // Determine correct edit route based on role
    //     $editRoute = auth()->user()->hasRole('admin')
    //         ? route('tasks.index', $id)
    //         : route('tasks.staff.index', $id);

    //     return redirect($editRoute)
    //         ->with('success', __('messages.task_updated'));
    // }

    public function update(UpdateTaskRequest $request, $id)
    {
        $this->taskService->updateTask($id, $request->validated());

        return redirect()
            ->route('tasks.edit', $id)
            ->with('success', __('messages.task_updated'));
    }

    /**
     * ==============================
     * DELETE TASK
     * ==============================
     */
    public function destroy(Request $request, $id)
    {
        $this->taskService->deleteTask($id);

        if (auth()->user()->hasRole('staff')) {
            return back()->with('success', __('messages.task_deleted'));
        }

        return redirect()
            ->route($request->input('redirect_to', 'tasks.index'))
            ->with('success', __('messages.task_deleted'));
    }

    /**
     * ==============================
     * ADMIN TASK LIST
     * ==============================
     */
    // public function index(Request $request)
    // {
    //     $tasks = $this->taskService->getFilteredTasks($request);
    //     $allUsers = User::role('staff')->get();

    //     return view('tasks.index', compact('tasks', 'allUsers'));
    // }

    // public function index(Request $request)
    // {
    //     $projects = Project::with([
    //             'tasks.assignedUsers'
    //         ])
    //         ->where('staff_id', auth()->id())
    //         ->get();

    //     return view('tasks.staff.index', compact('projects'));
    // }

    public function index(Request $request)
    {
        $user = auth()->user();

        $projects = $this->taskService->getFilteredProjectsWithTasks($request, $user);

        $projectOptions = Project::query()
            ->when(fn($q) => $q->where('staff_id', $user->id))
            ->orderBy('title')
            ->get(['id', 'title']);

        // return $user->hasRole('admin')
        //     ? view('tasks.index', compact('projects', 'projectOptions'))
        //     : view('tasks.staff.index', compact('projects', 'projectOptions'));
        return view('tasks.index', compact('projects', 'projectOptions'));
    }


    /**
     * ==============================
     * STAFF TASK LIST
     * ==============================
     */
    // public function staffTasks(Request $request)
    // {
    //     $tasks = $this->taskService->getTasksForStaff($request, auth()->id());

    //     return view('tasks.staff.index', compact('tasks'));
    // }

    /**
     * ==============================
     * STAFF DASHBOARD – UPCOMING TASKS
     * ==============================
     */
    // public function upcomingTasks(Request $request)
    // {
    //     $tasks = $this->taskService->getTasksForStaff($request, auth()->id());

    //     return view('staffdashboard', compact('tasks'));
    // }

    public function upcomingTasks()
    {
        $projects = Project::with(['tasks'])
            ->where('staff_id', auth()->id())
            ->latest()
            ->take(3)
            ->get();

        // Calculate completion percentage for each project
        // foreach ($projects as $project) {
        //     $totalTasks = $project->tasks->count();
        //     $completedTasks = $project->tasks->where('status', 'completed')->count();
        //     $project->completion_percentage = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;
        // }

        return view('staffdashboard', compact('projects'));
    }


    /**
     * ==============================
     * UPDATE TASK STATUS (AJAX)
     * ==============================
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $request->validate([
                'status' => 'required|in:pending,in_progress,completed',
                'percentage' => 'nullable|integer|min:0|max:100',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
            ], 422);
        }

        $task = $this->taskService->updateStatus(
            $id,
            $request->status,
            $request->percentage
        );

        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'Task not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'task' => $task
        ]);
    }

    /**
     * ==============================
     * GET TASKS BY USER (AJAX)
     * ==============================
     */
    public function getUserTasks($userId)
    {
        $tasks = \DB::table('task_user')
            ->join('tasks', 'task_user.task_id', '=', 'tasks.id')
            ->where('task_user.user_id', $userId)
            ->select('tasks.id', 'tasks.title', 'tasks.status', 'tasks.start_date', 'tasks.due_date')
            ->get();

        return response()->json($tasks);
    }
}
