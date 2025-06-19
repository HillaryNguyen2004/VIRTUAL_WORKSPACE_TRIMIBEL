<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Task;
use App\Http\Requests\AssignTaskRequest;

class TeamController extends Controller
{
    public function index()
    {
        $teamMembers = User::where('team_leader_id', auth()->id())->get();
        $staffTasks = Task::where('assigned_user_id', auth()->id())->get();

        return view('tasks.staff.team', compact('teamMembers', 'staffTasks'));
    }

    public function assignTask(AssignTaskRequest $request)
    {
        $data = $request->validatedData();

        $user = User::findOrFail($data['user_id']);

        // Avoid duplicate task assignment
        if (!$user->assignedTasks()->where('task_user.task_id', $data['task_id'])->exists()) {
            $user->assignedTasks()->attach($data['task_id']);
        }

        return redirect()->route('team.overview')->with('success', 'Task assigned successfully!');
    }
}
