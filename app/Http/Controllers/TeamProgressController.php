<?php

namespace App\Http\Controllers;

use App\Services\TeamProgressService;
use Illuminate\Http\Request;

class TeamProgressController extends Controller
{
    protected $teamProgressService;

    public function __construct(TeamProgressService $teamProgressService)
    {
        $this->teamProgressService = $teamProgressService;
    }

    public function index()
    {
        $currentUser = auth()->user();
        [$teamUsers, $teamStats] = $this->teamProgressService->getTeamProgress($currentUser);

        return view('team_progress', compact('teamUsers', 'teamStats', 'currentUser'));
    }
}
