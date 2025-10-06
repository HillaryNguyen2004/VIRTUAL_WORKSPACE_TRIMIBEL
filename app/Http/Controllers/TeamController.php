<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Task;
use App\Http\Requests\AssignTaskRequest;
use App\Repositories\TeamRepositoryInterface;
use App\Services\TeamService;

class TeamController extends Controller
{
    protected $teamRepo;
    protected TeamService $teamService;

    public function __construct(TeamService $teamService, TeamRepositoryInterface $teamRepo)
    {
        $this->teamService = $teamService;
        $this->teamRepo = $teamRepo;
    }
        
    public function index()
    {
        $data = $this->teamService->getTeamOverview(auth()->id());

        return view('tasks.staff.team', $data);
    }

    public function assignTask(AssignTaskRequest $request)
    {
        $data = $request->validatedData();

        $assigned = $this->teamService->assignTaskToUser($data['user_id'], $data['task_id']);

        return redirect()->route('team.overview')
            ->with('success', $assigned ? __('messages.task_assigned') : __('messages.task_already_assigned'));
    }
}
