<?php
namespace App\Services;

use App\Repositories\TeamRepositoryInterface;

class TeamService
{
    protected $teamRepo;

    public function __construct(TeamRepositoryInterface $teamRepo)
    {
        $this->teamRepo = $teamRepo;
    }

    public function getTeamMembers(int $staffId)
    {
        return $this->teamRepo->getTeamMembersByLeader($staffId);
    }

    public function getStaffTasks(int $staffId)
    {
        return $this->teamRepo->getTasksAssignedToUser($staffId);
    }

    public function assignTaskToUser(int $userId, int $taskId): bool
    {
        return $this->teamRepo->assignTaskToUser($userId, $taskId);
    }

    public function getTeamOverview(int $leaderId): array
    {
        $teamMembers = $this->teamRepo->getTeamMembersByLeader($leaderId);
        $staffTasks  = $this->teamRepo->getTasksAssignedToLeader($leaderId);

        return [
            'teamMembers' => $teamMembers,
            'staffTasks'  => $staffTasks,
        ];
    }
}
