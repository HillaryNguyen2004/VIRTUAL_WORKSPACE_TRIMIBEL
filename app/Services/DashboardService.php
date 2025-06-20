<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;

class DashboardService
{
    public function getUserDashboardData(User $user): array
    {
        $teamLeader = $user->teamLeader;
        $teamMembers = $user->teamMembers;

        $assignedTasks = $user->assignedTasks()->get();

        return compact('teamLeader', 'teamMembers', 'assignedTasks');
    }

    public function getStaffDashboardData(User $user): array
    {
        $tasks = $user->assignedTasks()->get();
        $teamLeader = $user->teamLeader;
        $teamMembers = $user->teamMembers;

        return compact('tasks', 'teamLeader', 'teamMembers');
    }
}
