<?php
namespace App\Repositories;

interface TeamRepositoryInterface
{
    public function assignTaskToUser(int $userId, int $taskId): bool;
    public function getTeamMembersByLeader(int $leaderId);
    public function getTasksAssignedToLeader(int $leaderId);
}
