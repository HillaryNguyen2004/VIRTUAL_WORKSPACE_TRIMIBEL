<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Task; 
use App\Models\User; 
use App\Repositories\TaskRepositoryInterface;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;


class TaskController extends Controller
{
    protected $taskRepo;

    public function __construct(TaskRepositoryInterface $taskRepo)
    {
        $this->taskRepo = $taskRepo;
    }

    // THIS PART IS FOR ADMINS ONLY

    public function create()
    {
        // $staffUsers = User::where('roles', 'staff')->get();
        // return view('tasks.create', compact('staffUsers'));
        $staffUsers = \App\Models\User::role('staff')->get(); // This uses Spatie

    return view('tasks.create', compact('staffUsers'));
    }

    public function store(StoreTaskRequest $request)
    {
        $this->taskRepo->create($request->formatted());

        return redirect()->route('tasks.create')->with('success', 'Task created successfully!');
    }

    public function index()
    {
        $tasks = $this->taskRepo->all();
        return view('tasks.index', compact('tasks'));
    }

    public function show($id)
    {
        $task = $this->taskRepo->find($id);
        // $staffUsers = User::where('roles', 'staff')->get();
        return view('tasks.create', compact('task'));
    }

    public function edit($id)
    {
        // $task = $this->taskRepo->find($id);
        // $staffUsers = User::where('roles', 'staff')->get();
        // return view('tasks.edit', compact('task', 'staffUsers'));
        $task = $this->taskRepo->find($id);

    
        $staffUsers = User::role('staff')->get();

        return view('tasks.edit', compact('task', 'staffUsers'));
    }


    public function update(UpdateTaskRequest $request, Task $task)
    {
        $this->taskRepo->update($task, $request->formatted());

        return redirect()->route('tasks.index')->with('success', 'Task updated successfully!');
    }


    public function destroy($id)
    {
        $task = $this->taskRepo->find($id);
        $this->taskRepo->delete($task);

        return redirect()->route('tasks.index')->with('success', 'Task deleted successfully!');
    }


    // THIS PART IS FOR STAFF

    // public function staffTasks(Request $request)
    // {
    //     $query = $this->taskRepo->getTasksForUser(auth()->id());

    //     if ($request->filled('search')) {
    //         $query = $query->where('title', 'like', '%' . $request->search . '%');
    //     }

    //     if ($request->filled('status')) {
    //         $query = $query->where('status', $request->status);
    //     }
    //     // $tasks = $this->taskRepo->getTasksForUser(auth()->id());
    //     $tasks = $this->taskRepo->getTasksForUser(auth()->id())->load('assignedUsers');
    //     // $tasks = $query->with('assignedUsers')->get();
    //     return view('tasks.staff.index', compact('tasks'));
    // }

    public function staffTasks(Request $request)
{
    $query = $this->taskRepo->getTasksForUser(auth()->id());

    if ($request->filled('search')) {
        $query = $query->where('title', 'like', '%' . $request->search . '%');
    }

    if ($request->filled('status')) {
        $query = $query->where('status', $request->status);
    }

    $tasks = $query->get()->load('assignedUsers');
    return view('tasks.staff.index', compact('tasks'));
}

 
    public function upcomingTasks()
    {
        $tasks = $this->taskRepo->getUpcomingTasks(auth()->id());
        return view('staffdashboard', compact('tasks'));
    }

    
}
