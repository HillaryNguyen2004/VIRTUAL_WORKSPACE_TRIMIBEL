<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;

class DashboardService
{
    public function getUserDashboardData(User $user): array
    {
        $teamLeader = $user->teamLeader;
        $teamMembers = $teamLeader ? $teamLeader->teamMembers : collect();

        $assignedTasks = $user->assignedTasks()->with('readStatuses')->get();

        return compact('teamLeader', 'teamMembers', 'assignedTasks');
    }

    public function getStaffDashboardData(User $user): array
    {
        $tasks = $user->assignedTasks()->with('readStatuses')->get();
        $teamLeader = $user->teamLeader;
        $teamMembers = $teamLeader ? $teamLeader->teamMembers : collect();

        return compact('tasks', 'teamLeader', 'teamMembers');
    }
}
