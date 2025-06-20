<?php

namespace App\Repositories;

use App\Models\Task;

class TaskRepository extends BaseRepository implements TaskRepositoryInterface
{
    public function __construct(Task $model)
    {
        parent::__construct($model);
    }
    public function find($id): ?\App\Models\Task
    {
        /** @var \App\Models\Task|null $task */
        $task = parent::find($id);
        return $task;
    }

    public function create(array $data): Task
    {
        /** @var Task $task */
        $task = parent::create($data);
        return $task;
    }

    
    public function getTasksForUser(int $userId)
    {
        // return $this->model->whereHas('assignedUsers', function ($query) use ($userId) {
        //     $query->where('user_id', $userId);
        // });
        return $this->model->where('assigned_user_id', $userId);
    }

    public function getUpcomingTasks(int $userId)
    {
        return $this->model->whereHas('assignedUsers', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })->whereIn('status', ['pending', 'in_progress'])->get();
    }
}

