<?php
namespace App\Repositories;

use App\Models\User;

class TeamRepository implements TeamRepositoryInterface
{
    public function assignTaskToUser(int $userId, int $taskId): bool
    {
        $user = User::findOrFail($userId);

        if (!$user->assignedTasks()->where('task_user.task_id', $taskId)->exists()) {
            $user->assignedTasks()->attach($taskId);
            return true;
        }

        return false;
    }

    public function getTeamMembersByLeader(int $leaderId)
    {
        return \App\Models\User::where('team_leader_id', $leaderId)->get();
    }

    public function getTasksAssignedToLeader(int $leaderId)
    {
        return \App\Models\Task::where('assigned_user_id', $leaderId)->get();
    }
}
