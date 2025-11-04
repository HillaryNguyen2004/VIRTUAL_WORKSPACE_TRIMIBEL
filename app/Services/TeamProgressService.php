<?php

namespace App\Services;

use App\Repositories\TeamProgressRepository;

class TeamProgressService
{
    protected $repository;

    public function __construct(TeamProgressRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getTeamProgress($currentUser)
    {
        $teamLeaderId = $currentUser->team_leader_id ?? $currentUser->id;

        $teamUsers = $this->repository->getTeamUsersWithTasks($teamLeaderId);

        $teamUsers->each(function ($user) {
            $tasks = $user->assignedTasks;
            $user->total_tasks_count = $tasks->count();
            $user->completed_tasks_count = $tasks->where('status', 'completed')->count();
            $user->completion_rate = $user->total_tasks_count > 0
                ? round(($user->completed_tasks_count / $user->total_tasks_count) * 100, 1)
                : 0;
            $user->status = $this->determineStatus($tasks, $user->completion_rate);
        });

        $teamStats = $this->repository->getTeamStats($teamUsers->pluck('id'));

        return [$teamUsers, $teamStats];
    }

    private function determineStatus($tasks, $completionRate)
    {
        if ($tasks->isEmpty()) return 'inactive';

        $overdue = $tasks->filter(fn($t) => $t->due_date && $t->due_date < now() && $t->status !== 'completed')->count();
        if ($overdue > 0) return 'overdue';
        if ($completionRate >= 80) return 'active';
        if ($completionRate >= 50) return 'busy';
        return 'needs_help';
    }
}
