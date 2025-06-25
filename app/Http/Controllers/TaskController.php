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
        if (auth()->user()->hasRole('admin')) {
            return redirect()->route('tasks.index')->with('success', 'Task updated successfully!');
        }

            return redirect()->route('tasks.staff.index')->with('success', 'Task updated successfully!');
        // return redirect()->route('tasks.index')->with('success', 'Task updated successfully!');
    }

    public function destroy($id)
    {
        $this->taskService->deleteTask($id);
        return redirect()->route('tasks.index')->with('success', 'Task deleted successfully!');
    }


    public function index(Request $request)
    {
        $tasks = $this->taskService->getFilteredTasks($request);
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
