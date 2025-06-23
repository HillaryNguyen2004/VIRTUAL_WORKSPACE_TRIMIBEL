<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Services\TaskService;
use Illuminate\Http\Request;
use App\Models\User;

class TaskController extends Controller
{
    protected $taskService;

    public function __construct(TaskService $taskService)
    {
        $this->taskService = $taskService;
    }

    // THIS PART IS FOR ADMIN
    // public function index()
    // {
    //     $tasks = $this->taskService->getAllTasks();
    //     return view('tasks.index', compact('tasks'));
    // }
    



    public function create()
    {
        $staffUsers = User::role('staff')->get();
        return view('tasks.create', compact('staffUsers'));
    }

    public function store(StoreTaskRequest $request)
    {
        $this->taskService->createTask($request->formatted());
        return redirect()->route('tasks.create')->with('success', 'Task created successfully!');
    }

    public function show($id)
    {
        $task = $this->taskService->getTaskById($id);
        return view('tasks.create', compact('task'));
    }

    public function edit($id)
    {
        $task = $this->taskService->getTaskById($id);
        $staffUsers = User::role('staff')->get();
        return view('tasks.edit', compact('task', 'staffUsers'));
    }

    public function update(UpdateTaskRequest $request, $id)
    {
        $this->taskService->updateTask($id, $request->formatted());
        return redirect()->route('tasks.index')->with('success', 'Task updated successfully!');
    }

    public function destroy($id)
    {
        $this->taskService->deleteTask($id);
        return redirect()->route('tasks.index')->with('success', 'Task deleted successfully!');
    }

    public function index(Request $request)
{
    $query = $this->taskService->getAllTasksQuery(); // returns Task::query()->with('assigneeUser');

    if ($request->filled('search')) {
        $query->where('title', 'like', '%' . $request->search . '%');
    }

    if ($request->filled('due_date')) {
        $query->whereDate('due_date', $request->due_date);
    }

    if ($request->filled('assigned_user_id')) {
        $query->where('assigned_user_id', $request->assigned_user_id);
    }

    if ($request->filled('sort_by')) {
        $query->orderBy($request->sort_by);
    }

    $tasks = $query->get();
    $allUsers = User::role('staff')->get();

    return view('tasks.index', compact('tasks', 'allUsers'));
}



    // THIS PART IS FOR STAFF 
    public function staffTasks(Request $request)
    {
        $tasks = $this->taskService->getTasksForStaff($request, auth()->id());
        return view('tasks.staff.index', compact('tasks'));
    }

    public function upcomingTasks()
    {
        $tasks = $this->taskService->getUpcomingTasks(auth()->id());
        return view('staffdashboard', compact('tasks'));
    }
}
