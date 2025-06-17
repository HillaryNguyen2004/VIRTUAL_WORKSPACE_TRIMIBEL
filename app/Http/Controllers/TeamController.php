<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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
            'task_id' => 'required|exists:tasks,id',
            'user_id' => 'required|exists:users,id'
        ]);

        $task = Task::find($request->task_id);
        $task->assigned_to = $request->user_id;
        $task->save();

        return redirect()->route('team.overview')->with('success', 'Task assigned successfully!');
    }
}
