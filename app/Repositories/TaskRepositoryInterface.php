<?php

namespace App\Repositories;

use App\Models\Task;

interface TaskRepositoryInterface
{
    public function all();
    // public function find($id): ?Task;
    public function find($id): ?\App\Models\Task;
    // public function create(array $data): Task;
    public function create(array $data): \App\Models\Task;
    public function update(Task $task, array $data): bool;
    public function delete(Task $task): bool;
    public function getTasksForUser(int $userId);
    public function getUpcomingTasks(int $userId);
}
