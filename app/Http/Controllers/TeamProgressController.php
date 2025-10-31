<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Task;
use Illuminate\Http\Request;

class TeamProgressController extends Controller
{
    public function index()
    {
        $currentUser = auth()->user();

        // 1️⃣ Determine team leader ID (if user has one)
        $teamLeaderId = $currentUser->team_leader_id ?? $currentUser->id;

        // 2️⃣ Get all users in the same team (including the current user)
        $teamUsers = User::where('team_leader_id', $teamLeaderId)
            ->orWhere('id', $teamLeaderId)
            ->with(['assignedTasks' => function ($q) {
                $q->where('active', 1);
            }])
            ->get();

        // 3️⃣ Calculate task progress for each user
        $teamUsers->each(function ($user) {
            $tasks = $user->assignedTasks;

            $user->total_tasks_count = $tasks->count();
            $user->completed_tasks_count = $tasks->where('status', 'completed')->count();
            $user->completion_rate = $user->total_tasks_count > 0
                ? round(($user->completed_tasks_count / $user->total_tasks_count) * 100, 1)
                : 0;

            $user->status = $this->determineStatus($tasks, $user->completion_rate);
        });

        // 4️⃣ Team-level statistics
        $teamUserIds = $teamUsers->pluck('id');

        $teamTasks = Task::whereHas('assignedUsers', function ($q) use ($teamUserIds) {
            $q->whereIn('users.id', $teamUserIds);
        })->where('active', 1)->get();

        $teamStats = [
            'total_tasks'       => $teamTasks->count(),
            'completed_tasks'   => $teamTasks->where('status', 'completed')->count(),
            'in_progress_tasks' => $teamTasks->where('status', 'in_progress')->count(),
            'pending_tasks'     => $teamTasks->where('status', 'pending')->count(),
            'average_progress'  => $teamTasks->avg('percentage') ? round($teamTasks->avg('percentage'), 1) : 0,
        ];

        return view('team_progress', compact('teamUsers', 'teamStats', 'currentUser'));
    }

    private function determineStatus($tasks, $completionRate)
    {
        if ($tasks->isEmpty()) {
            return 'inactive';
        }

        $overdue = $tasks->filter(function ($task) {
            return $task->due_date && $task->due_date < now() && $task->status !== 'completed';
        })->count();

        if ($overdue > 0) {
            return 'overdue';
        }

        if ($completionRate >= 80) return 'active';
        if ($completionRate >= 50) return 'busy';
        return 'needs_help';
    }
}
