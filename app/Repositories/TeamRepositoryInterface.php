<?php
namespace App\Repositories;

interface TeamRepositoryInterface
{
    public function assignTaskToUser(int $userId, int $taskId): bool;
}
