<?php

namespace App\Repositories;

use App\Models\Task;

class TaskRepository extends BaseRepository implements TaskRepositoryInterface
{
    public function __construct(Task $task)
    {
        parent::__construct($task);
    }
    public function find($id): ?\App\Models\Task
{
    return parent::find($id);
}

public function create(array $data): \App\Models\Task
{
    return parent::create($data); // uses BaseRepository logic
}

    // public function getTasksForUser(int $userId)
    // {
    //     return $this->model->where('assigned_user_id', $userId)->get();
    // }
    public function getTasksForUser(int $userId)
    {
        return $this->model->where('assigned_user_id', $userId); // Remove ->get()
    }

    public function getUpcomingTasks(int $userId)
    {
        return $this->model->where('assigned_user_id', $userId)
                           ->whereIn('status', ['pending', 'in_progress'])
                           ->get();
    }
}
