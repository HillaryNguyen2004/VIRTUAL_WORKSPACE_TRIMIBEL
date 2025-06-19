<?php
namespace App\Services;

use App\Repositories\TaskRepositoryInterface;
use Illuminate\Http\Request;

class TaskService
{
    protected $taskRepo;

    public function __construct(TaskRepositoryInterface $taskRepo)
    {
        $this->taskRepo = $taskRepo;
    }

    public function getAllTasks()
    {
        return $this->taskRepo->all();
    }

    public function createTask(array $data)
    {
        return $this->taskRepo->create($data);
    }

    public function getTaskById($id)
    {
        return $this->taskRepo->find($id);
    }

    public function updateTask($id, array $data)
    {
        $task = $this->taskRepo->find($id);
        return $this->taskRepo->update($task, $data);
    }

    public function deleteTask($id)
    {
        $task = $this->taskRepo->find($id);
        return $this->taskRepo->delete($task);
    }

    // public function getTasksForStaff(Request $request, $userId)
    // {
    //     $query = $this->taskRepo->getTasksForUser($userId);

    //     if ($request->filled('search')) {
    //         $query->where('title', 'like', '%' . $request->search . '%');
    //     }

    //     if ($request->filled('status')) {
    //         $query->where('status', $request->status);
    //     }

    //     // return $query->get()->load('assignedUsers');
    //     return $query->with('assignedUsers')->get();
    // }

    public function getTasksForStaff(Request $request, $userId)
    {
        $query = $this->taskRepo->getTasksForUser($userId);

        if ($request->filled('search')) {
            $query = $query->where('title', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            $query = $query->where('status', $request->status);
        }

        return $query->with('assignedUsers')->get();
    }

    public function getUpcomingTasks($userId)
    {
        return $this->taskRepo->getUpcomingTasks($userId);
    }
}
