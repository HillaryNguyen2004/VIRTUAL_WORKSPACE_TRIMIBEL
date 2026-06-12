<?php

namespace App\Repositories;

use App\Models\User;
use App\Models\Task;

class TeamProgressRepository
{
    public function getTeamUsersWithTasks($teamLeaderId)
    {
        return User::where('team_leader_id', $teamLeaderId)
            ->orWhere('id', $teamLeaderId)
            ->with(['assignedTasks' => fn($q) => $q->where('active', 1)])
            ->get();
    }

    public function getTeamStats($teamUserIds)
    {
        $teamTasks = Task::whereHas('assignedUsers', fn($q) => $q->whereIn('users.id', $teamUserIds))
            ->where('active', 1)
            ->get();

        return [
            'total_tasks'       => $teamTasks->count(),
            'completed_tasks'   => $teamTasks->where('status', 'completed')->count(),
            'in_progress_tasks' => $teamTasks->where('status', 'in_progress')->count(),
            'pending_tasks'     => $teamTasks->where('status', 'pending')->count(),
            'average_progress'  => $teamTasks->avg('percentage') ? round($teamTasks->avg('percentage'), 1) : 0,
        ];
    }
}
