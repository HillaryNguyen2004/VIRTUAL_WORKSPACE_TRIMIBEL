<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Task; // Make sure you have a Task model
use App\Models\User; // Assuming you have a User model for staff users

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
    public function store(Request $request)
{
    $validated = $request->validate([
        'title' => 'required|string|max:255',
        'assignee' => 'required|exists:users,id',
        'due_date' => 'required|date',
        'description' => 'nullable|string',
        'active' => 'nullable|boolean',
    ]);

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
}