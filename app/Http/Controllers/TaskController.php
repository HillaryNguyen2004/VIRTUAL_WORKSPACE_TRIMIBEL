<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Task; 
use App\Models\User; 
use App\Http\Requests\StoreTaskRequest;

class TaskController extends Controller
{
    // Show the create task form
    public function create()
    {
        $staffUsers = User::where('roles', 'staff')->get();
        return view('tasks.create', compact('staffUsers'));
        // return view('tasks.create');
    }

    // Handle form submission and save the task
    public function store(StoreTaskRequest $request)
{
    $validated = $request->validated();


    $task = new \App\Models\Task();
    $task->title = $validated['title'];
    $task->description = $validated['description'] ?? null;
    $task->assigned_user_id = $validated['assignee'];
    $task->status = 'pending';
    $task->due_date = $validated['due_date'];
    $task->active = $request->has('active') ? 1 : 0;
    $task->save();

    return redirect()->route('tasks.create')->with('success', 'Task created successfully!');
}

public function index()
{
    $tasks = \App\Models\Task::with('assigneeUser')->get();
    return view('tasks.index', compact('tasks'));
}

public function show($id)
{
    $task = \App\Models\Task::with('assigneeUser')->findOrFail($id);
    return view('tasks.show', compact('task'));
}

public function edit($id)
{
    $task = Task::findOrFail($id);
    $staffUsers = User::where('roles', 'staff')->get();
    return view('tasks.edit', compact('task', 'staffUsers'));
}

public function update(Request $request, $id)
{
    $validated = $request->validate([
        'title' => 'required|string|max:255',
        'assignee' => 'required|exists:users,id',
        'due_date' => 'required|date',
        'description' => 'nullable|string',
        'active' => 'nullable|boolean',
        'status' => 'required|in:pending,in_progress,completed',
    ]);

    $task = Task::findOrFail($id);
    $task->title = $validated['title'];
    $task->description = $validated['description'] ?? null;
    $task->assigned_user_id = $validated['assignee'];
    $task->due_date = $validated['due_date'];
    $task->active = $request->has('active') ? 1 : 0;
    $task->status = $validated['status'];
    $task->save();

    return redirect()->route('tasks.index')->with('success', 'Task updated successfully!');
}

public function destroy($id)
{
    $task = Task::findOrFail($id);
    $task->delete();

    return redirect()->route('tasks.index')->with('success', 'Task deleted successfully!');
}

public function staffTasks()
{
    $tasks = \App\Models\Task::where('assigned_user_id', auth()->id())->get();
    return view('tasks.staff.index', compact('tasks'));
}

public function upcomingTasks()
{
    $tasks = \App\Models\Task::where('assigned_user_id', auth()->id())
        ->whereIn('status', ['pending', 'in_progress'])
        ->get();

    return view('staffdashboard', compact('tasks'));
}
}