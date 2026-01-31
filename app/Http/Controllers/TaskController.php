<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Repositories\TaskRepository;
use App\Services\TaskService;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Gate;


class TaskController extends Controller
{
    protected TaskService $taskService;
    private TaskRepository $taskRepository;

    public function __construct(TaskService $taskService, TaskRepository $taskRepository)
    {
        $this->taskService = $taskService;
        $this->taskRepository = $taskRepository;
    }

    /**
     * ==============================
     * CREATE TASK
     * ==============================
     */
    public function create(Request $request)
    {
        $user = auth()->user();

        if ($user->hasRole('admin')) {

            $assignees = User::whereDoesntHave('roles', fn($q) => $q->where('name', 'admin'))
                ->get(['id', 'name', 'team_leader_id']);

            $projects = Project::with('phases:id,project_id,title')->get(['id', 'title', 'staff_id']);

        } elseif ($user->hasRole('staff')) {

            $assignees = User::query()
                ->where('id', $user->id)
                ->orWhere('team_leader_id', $user->id)
                ->get(['id', 'name', 'team_leader_id']);

            $projects = Project::with('phases:id,project_id,title')->where('staff_id', $user->id)
                ->get(['id', 'title', 'staff_id']);

        } else {
            // role: user
            $leaderId = $user->team_leader_id; // staffId of this user

            $projects = Project::with('phases:id,project_id,title')
                ->when($leaderId, fn($q) => $q->where('staff_id', $leaderId))
                ->get(['id', 'title', 'staff_id']);

            // Assignee is only this user
            $assignees = User::query()
                ->where('id', $leaderId)
                ->orWhere('team_leader_id', $leaderId)
                ->get(['id', 'name', 'team_leader_id']);
        }

        // for selects
        $projectOptions = $projects->mapWithKeys(fn($p) => [$p->id => $p->title])->toArray();

        // maps for JS
        $projectLeaderMap = $projects->pluck('staff_id', 'id'); // [projectId => staffId]

        // Create assigneesByLeader map: leader_id => [leader, ...members]
        $assigneesByLeader = [];
        $uniqueLeaderIds = $projects->pluck('staff_id')->unique();

        foreach ($uniqueLeaderIds as $lid) {
            $group = collect();

            // 1. Add the Team Leader (if in assignees list)
            $leader = $assignees->firstWhere('id', $lid);
            if ($leader) {
                $group->push(['id' => $leader->id, 'name' => $leader->name]);
            }

            // 2. Add Team Members (users whose team_leader_id == $lid)
            $members = $assignees->where('team_leader_id', $lid);
            foreach ($members as $m) {
                // Avoid adding the leader again if their team_leader_id happens to be themselves (rare but possible)
                if ($m->id != $lid) {
                    $group->push(['id' => $m->id, 'name' => $m->name]);
                }
            }

            if ($group->isNotEmpty()) {
                $assigneesByLeader[$lid] = $group->values()->toArray();
            }
        }

        $parentId = $request->query('parent_id');

        $parentTask = null;
        $defaultAssigneeId = null;
        $defaultLeaderId = null;

        if ($parentId) {
            // Chỉ cần assignee_id
            $parentTask = Task::find($parentId);

            if ($parentTask) {
                foreach ($parentTask->assignedUsers as $user) {
                    // Đảm bảo user được lấy nằm trong danh sách assignees hiện tại
                    if ($assignees->contains('id', $user->id)) {
                        $defaultAssigneeId = $user->id;
                        break; // Lấy user đầu tiên tìm thấy
                    }
                }

                if ($defaultAssigneeId) {
                    $parentAssignee = User::select('id', 'team_leader_id')
                        ->find($defaultAssigneeId);

                    // leader của assignee (để filter team members)
                    $defaultLeaderId = $parentAssignee->teamLeader->id ?? null;
                }
            }
        }

        return view('tasks.create', compact(
            'assignees',
            'projects',
            'projectOptions',
            'projectLeaderMap',
            'assigneesByLeader',
            'parentTask',
            'defaultAssigneeId',
            'defaultLeaderId'
        ));
    }

    /**
     * ==============================
     * STORE TASK
     * ==============================
     */
    public function store(StoreTaskRequest $storeTaskRequest)
    {
        $validated = $storeTaskRequest->validated();
        // $parentId = $storeTaskRequest->input('parent_id');

        if (isset($validated['tasks'])) {
            foreach ($validated['tasks'] as $i => $taskData) {
                $this->taskService->createTask($taskData, $i); // pass index
            }
        } else {
            $this->taskService->createTask($validated, null);
        }

        return back()->with('success', __('messages.task_created'));
    }

    /**
     * ==============================
     * SHOW TASK
     * ==============================
     */
    public function show($id)
    {
        $task = $this->taskService->getTaskById($id);

        $task->markAsRead();

        return view('tasks.show', compact('task'));
    }

    /**
     * ==============================
     * TASK DETAILS PAGE
     * ==============================
     */
    public function details($id)
    {
        $task = $this->taskService->getTaskById($id);

        // Load relationships
        $task->load(['project', 'assignedUsers']);

        // Mark as read
        $task->markAsRead();

        return view('tasks.details', compact('task'));
    }

    public function markRead($id)
    {
        $task = $this->taskService->getTaskById($id);
        $task->markAsRead();

        return response()->json(['success' => true]);
    }


    /**
     * ==============================
     * EDIT TASK
     * ==============================
     */
    public function edit($id, Request $request)
    {
        $task = $this->taskService->getTaskById($id);
        $user = auth()->user();

        if ($user->hasRole('admin')) {

            $assignees = User::whereDoesntHave('roles', fn($q) => $q->where('name', 'admin'))
                ->get(['id', 'name', 'team_leader_id']);

            $projects = Project::with('phases:id,project_id,title')->get(['id', 'title', 'staff_id']);

        } elseif ($user->hasRole('staff')) {

            $assignees = User::query()
                ->where('id', $user->id)
                ->orWhere('team_leader_id', $user->id)
                ->get(['id', 'name', 'team_leader_id']);

            $projects = Project::with('phases:id,project_id,title')->where('staff_id', $user->id)
                ->get(['id', 'title', 'staff_id']);

        } else {
            // role: user
            $leaderId = $user->team_leader_id; // staffId of this user

            // If user has no leader, return empty options (or handle as you prefer)
            $projects = Project::with('phases:id,project_id,title')
                ->when($leaderId, fn($q) => $q->where('staff_id', $leaderId))
                ->get(['id', 'title', 'staff_id']);

            // Assignee is only this user
            $assignees = User::query()
                ->where('id', $leaderId)
                ->orWhere('team_leader_id', $leaderId)
                ->get(['id', 'name', 'team_leader_id']);
        }

        $projectOptions = $projects->mapWithKeys(fn($p) => [$p->id => $p->title])->toArray();

        $projectLeaderMap = $projects->pluck('staff_id', 'id'); // [projectId => staffId]

        // Create assigneesByLeader map: leader_id => [leader, ...members]
        $assigneesByLeader = [];
        $uniqueLeaderIds = $projects->pluck('staff_id')->unique();

        foreach ($uniqueLeaderIds as $lid) {
            $group = collect();

            // 1. Add the Team Leader (if in assignees list)
            $leader = $assignees->firstWhere('id', $lid);
            if ($leader) {
                $group->push(['id' => $leader->id, 'name' => $leader->name]);
            }

            // 2. Add Team Members (users whose team_leader_id == $lid)
            $members = $assignees->where('team_leader_id', $lid);
            foreach ($members as $m) {
                if ($m->id != $lid) {
                    $group->push(['id' => $m->id, 'name' => $m->name]);
                }
            }

            if ($group->isNotEmpty()) {
                $assigneesByLeader[$lid] = $group->values()->toArray();
            }
        }

        $parentId = $request->query('parent_id');

        $parentTask = null;
        $defaultAssigneeId = null;
        $defaultLeaderId = null;

        if ($parentId) {
            // Chỉ cần assignee_id
            $parentTask = Task::find($parentId);

            if ($parentTask) {
                foreach ($parentTask->assignedUsers as $user) {
                    // Đảm bảo user được lấy nằm trong danh sách assignees hiện tại
                    if ($assignees->contains('id', $user->id)) {
                        $defaultAssigneeId = $user->id;
                        break; // Lấy user đầu tiên tìm thấy
                    }
                }

                if ($defaultAssigneeId) {
                    $parentAssignee = User::select('id', 'team_leader_id')
                        ->find($defaultAssigneeId);

                    // leader của assignee (để filter team members)
                    $defaultLeaderId = $parentAssignee->teamLeader->id ?? null;
                }
            }
        }

        return view('tasks.edit', compact(
            'task',
            'projects',
            'assignees',
            'projectOptions',
            'projectLeaderMap',
            'assigneesByLeader',
            'parentTask',
            'defaultAssigneeId',
            'defaultLeaderId'
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

        return back()->with('success', __('messages.task_updated'));
    }

    /**
     * ==============================
     * DELETE TASK
     * ==============================
     */
    public function destroy(Request $request, $id)
    {
        $task = $this->taskService->getTaskById($id);
        $parentId = $task->parent_id;
        $projectId = $task->project_id;

        $this->taskService->deleteTask($id);

        if ($parentId) {
            return redirect()
                ->route('tasks.details', $parentId)
                ->with('success', __('messages.task_deleted'));
        }

        if ($projectId) {
            return redirect()
                ->route('projects.details', $projectId)
                ->with('success', __('messages.task_deleted'));
        }

        return redirect()
            ->route('tasks.index')
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

        $tasks = $this->taskService->getFilteredTasks($request, $user);

        $projectOptions = Project::query()
            ->when(!$user->hasRole('admin'), function ($q) use ($user) {
                $q->where('staff_id', $user->id)
                    ->orWhereHas('tasks', function ($t) use ($user) {
                        $t->whereHas('assignedUsers', fn($u) => $u->whereKey($user->id));
                    });
            })
            ->orderBy('title')
            ->get(['id', 'title']);

        return view('tasks.index', compact('tasks', 'projectOptions'));
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

    // public function upcomingTasks()
    // {
    //     $projects = Project::with(['tasks'])
    //         ->where('staff_id', auth()->id())
    //         ->latest()
    //         ->take(3)
    //         ->get();

    //     // Calculate completion percentage for each project
    //     // foreach ($projects as $project) {
    //     //     $totalTasks = $project->tasks->count();
    //     //     $completedTasks = $project->tasks->where('status', 'completed')->count();
    //     //     $project->completion_percentage = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;
    //     // }

    //     return view('staffdashboard', compact('projects'));
    // }

    

public function upcomingTasks()
{
    $staff = Auth::user();
    // 1) Staff projects (same logic as you)
    $projects = Project::with('tasks')
        ->where('staff_id', $staff->id)
        ->latest()
        ->take(3)
        ->get();

    // 2) Team members: only users in staff's team
    // team_leader_id = staff_id
    $teamMembers = User::query()
        ->where('team_leader_id', $staff->id)
        // optional: don't allow picking admins/staff as substaff targets
        ->whereDoesntHave('roles', fn ($q) => $q->whereIn('name', ['admin', 'staff']))
        ->orderBy('name')
        ->get();

    // 3) Return view with all required vars
    return view('staffdashboard', [
        'staff' => $staff,
        'projects' => $projects,
        'teamMembers' => $teamMembers,
    ]);
}


public function substaffDashboard()
{
    // permission middleware already checks, but keep safe:
    abort_unless(auth()->user()->can('staff.dashboard.view'), 403);
    $staff = Auth::user();
    // Load same data as staff dashboard (or adjust if needed)
    // $projects = auth()->user()->projects()->with('tasks')->latest()->get(); // adjust to your app
    $projects = Project::with('tasks')
        ->where('staff_id', $staff->id)
        ->latest()
        ->take(3)
        ->get();
    // $teamMembers = collect(); // optional, or load if you want substaff to manage members

    // Use SAME blade, but pass a flag to change label + routes
    return view('staffdashboard', [
        'projects' => $projects,
        'dashboardMode' => 'substaff', // 👈 important
    ]);
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
