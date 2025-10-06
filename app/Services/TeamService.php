<?php
namespace App\Services;
use App\Models\User;
use App\Models\Task;
use App\Notifications\TaskAssignedNotification;
use App\Repositories\TeamRepositoryInterface;
use Illuminate\Support\Facades\Log;

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

    // public function assignTaskToUser(int $userId, int $taskId): bool
    // {
    //     $assigned = $this->teamRepo->assignTaskToUser($userId, $taskId);
    //     if ($assigned) {
    //         // $user = User::find($userId);
    //         // $task = Task::find($taskId);

    //         // if ($user && $task) {
    //         //     $user->notify(new TaskAssignedNotification($task));
    //         // }
    //         $task = Task::findOrFail($taskId);
    //         $user = User::findOrFail($userId);

    //         // Save assignment in pivot
    //         // $task->users()->attach($user->id);

    //         // Send notification
    //         $user->notify(new TaskAssignedNotification(
    //             $task->id,
    //             $task->name,
    //             auth()->user()->name
    //         ));
    //     }

    //     return $assigned;
    // }

    // public function assignTaskToUser(int $userId, int $taskId): bool
    // {
    //     // Assign in pivot table
    //     $assigned = $this->teamRepo->assignTaskToUser($userId, $taskId);
    //     $this->authorize('assign-tasks');
    //     // echo $assigned; exit;

    //     if ($assigned == false) {
    //         $task = Task::findOrFail($taskId);
    //         $user = User::findOrFail($userId);

    //         // Attach user to task if not already in pivot
    //         if (!$task->users()->where('user_id', $userId)->exists()) {
    //             $task->users()->attach($userId);
    //         }

    //         // Send DB + broadcast notification
    //         $user->notify(new TaskAssignedNotification(
    //             $task->id,
    //             $task->name,
    //             auth()->user()->name
    //         ));
    //     }

    //     return $assigned;
    // }

    public function assignTaskToUser(int $userId, int $taskId): bool
    {
        // Log entry so we can confirm this method is called
        Log::info("TeamService.assignTaskToUser called (user={$userId}, task={$taskId}, by=" . (auth()->id() ?: 'no-auth') . ")");

        // idempotent attach through repository
        $assigned = $this->teamRepo->assignTaskToUser($userId, $taskId);

        // Only send notification if this is a new assignment
        if ($assigned) {
            // defensive fetch
            $task = Task::findOrFail($taskId);
            $user = User::findOrFail($userId);

            $assignedBy = auth()->check() ? auth()->user()->name : 'System';

            try {
                // Send notification for new assignment
                $user->notify(new TaskAssignedNotification(
                    $task->id,
                    $task->name,
                    $assignedBy
                ));
                Log::info("Notification sent for new task assignment: task {$taskId} -> user {$userId}");
            } catch (\Throwable $e) {
                Log::error("Failed to notify user {$userId} about task {$taskId}: " . $e->getMessage());
                // Do not throw here — the assignment succeeded; we logged the error
            }
        } else {
            Log::info("User {$userId} already assigned to task {$taskId}, no notification sent");
        }

        return (bool) $assigned;
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
