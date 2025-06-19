<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Task;
use App\Http\Requests\AssignTaskRequest;
use App\Repositories\TeamRepositoryInterface;

class TeamController extends Controller
{
    protected $teamRepo;

    public function __construct(TeamRepositoryInterface $teamRepo)
    {
        $this->teamRepo = $teamRepo;
    }
        
    public function index()
    {
        $teamMembers = User::where('team_leader_id', auth()->id())->get();
        $staffTasks = Task::where('assigned_user_id', auth()->id())->get();

        return view('tasks.staff.team', compact('teamMembers', 'staffTasks'));
    }

    public function assignTask(AssignTaskRequest $request)
    {
        $data = $request->validatedData();

        $assigned = $this->teamRepo->assignTaskToUser($data['user_id'], $data['task_id']);

        return redirect()->route('team.overview')
            ->with('success', $assigned ? 'Task assigned successfully!' : 'Task already assigned.');
    }
}
