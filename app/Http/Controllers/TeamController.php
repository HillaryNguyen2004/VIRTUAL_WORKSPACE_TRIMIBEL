<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Task;

class TeamController extends Controller
{
    public function index()
    {
        $teamMembers = \App\Models\User::where('team_leader_id', auth()->id())->get();
        $staffTasks = \App\Models\Task::where('assigned_user_id', auth()->id())->get();

    return view('tasks.staff.team', compact('teamMembers', 'staffTasks'));
    }

    public function assignTask(Request $request)
{
    $request->validate([
        'task_id' => 'required|exists:tasks,task_id',
        'user_id' => 'required|exists:users,id'
    ]);

    $user = User::findOrFail($request->user_id);
    $user->assignedTasks()->attach($request->task_id);
    // Avoid duplicate task assignment
    // if (!$user->assignedTasks()->where('task_id', $request->task_id)->exists()) {
    //     $user->assignedTasks()->attach($request->task_id);
    // }

    return redirect()->route('team.overview')->with('success', 'Task assigned successfully!');
}
}
