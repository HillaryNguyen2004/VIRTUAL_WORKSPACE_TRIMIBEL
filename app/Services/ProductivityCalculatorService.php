<?php

namespace App\Services;

use App\Models\CheckIn;
use App\Models\Task;
use App\Models\DayOffRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * ProductivityCalculatorService
 *
 * Calculates real-time productivity scores for employees using the same algorithm
 * as the ETL pipeline, but working directly with Laravel models for current data.
 */
class ProductivityCalculatorService
{
    /**
     * Calculate current productivity score for an employee over a given period
     *
     * @param int $employeeId User ID
     * @param int $days Number of days to look back (default 7)
     * @return float Productivity score (0-100)
     */
    public function calculateCurrentProductivityScore(int $employeeId, int $days = 7): float
    {
        $endDate = Carbon::today();
        $startDate = $endDate->copy()->subDays($days - 1);

        $dailyScores = [];

        // Calculate productivity for each day in the range
        for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {
            $dailyScore = $this->calculateDailyProductivityScore($employeeId, $date);
            if ($dailyScore !== null) {
                $dailyScores[] = $dailyScore;
            }
        }

        if (empty($dailyScores)) {
            Log::warning("No daily scores found for employee {$employeeId} in last {$days} days");
            return 0.0;
        }

        $avgScore = array_sum($dailyScores) / count($dailyScores);
        return round($avgScore, 1);
    }

    /**
     * Calculate productivity score for a single day
     *
     * @param int $employeeId User ID
     * @param Carbon $date Date to calculate for
     * @return float|null Productivity score or null if no data
     */
    private function calculateDailyProductivityScore(int $employeeId, Carbon $date): ?float
    {
        // Get attendance data
        $checkInData = $this->getCheckInData($employeeId, $date);

        // Get day off data
        $dayOffData = $this->getDayOffData($employeeId, $date);

        // Get task data
        $taskData = $this->getTaskData($employeeId, $date);

        // Apply the same productivity calculation logic as ETL pipeline
        return $this->computeProductivity(
            $checkInData['hours_worked'] ?? 0,
            $checkInData['is_late'] ?? false,
            $checkInData['checked_in'] ?? false,
            $dayOffData['had_day_off'] ?? false,
            $taskData['tasks_completed'] ?? 0,
            $taskData['avg_task_score'] ?? 0,
            $taskData['avg_task_percentage'] ?? 0
        );
    }

    /**
     * Get check-in data for an employee on a specific date
     *
     * @param int $employeeId User ID
     * @param Carbon $date Date to check
     * @return array Check-in metrics
     */
    private function getCheckInData(int $employeeId, Carbon $date): array
    {
        // Get user name for check-in lookup
        $user = User::find($employeeId);
        if (!$user) {
            return ['hours_worked' => 0, 'is_late' => false, 'checked_in' => false];
        }

        $checkIn = CheckIn::where('user_name', $user->name)
            ->where('date', $date->format('Y-m-d'))
            ->first();

        if (!$checkIn) {
            return ['hours_worked' => 0, 'is_late' => false, 'checked_in' => false];
        }

        $checkedIn = !is_null($checkIn->check_in_time);
        $hoursWorked = 0;
        $isLate = false;

        if ($checkedIn) {
            // Calculate hours worked
            if ($checkIn->check_out_time) {
                $checkInTime = Carbon::parse($checkIn->check_in_time);
                $checkOutTime = Carbon::parse($checkIn->check_out_time);
                $hoursWorked = $checkOutTime->diffInHours($checkInTime);
            } else {
                // If no check-out time, assume still working (partial day)
                $checkInTime = Carbon::parse($checkIn->check_in_time);
                $now = Carbon::now();
                if ($now->isToday() && $date->isToday()) {
                    $hoursWorked = min($now->diffInHours($checkInTime), 8); // Cap at 8 hours
                }
            }

            // Check if late (assuming 9:00 AM is standard start time)
            $standardStart = Carbon::parse($date->format('Y-m-d') . ' 09:00:00');
            $actualStart = Carbon::parse($checkIn->check_in_time);
            $isLate = $actualStart->gt($standardStart);
        }

        return [
            'hours_worked' => $hoursWorked,
            'is_late' => $isLate,
            'checked_in' => $checkedIn
        ];
    }

    /**
     * Get day off data for an employee on a specific date
     *
     * @param int $employeeId User ID
     * @param Carbon $date Date to check
     * @return array Day off metrics
     */
    private function getDayOffData(int $employeeId, Carbon $date): array
    {
        $dayOff = DayOffRequest::where('user_id', $employeeId)
            ->where('date', $date->format('Y-m-d'))
            ->where('status', 'approved') // Only count approved day off requests
            ->first();

        return [
            'had_day_off' => !is_null($dayOff)
        ];
    }

    /**
     * Get task completion data for an employee on a specific date
     *
     * @param int $employeeId User ID
     * @param Carbon $date Date to check
     * @return array Task metrics
     */
    private function getTaskData(int $employeeId, Carbon $date): array
    {
        // Use same fallback dates as ETL pipeline (etl_pipeline.py lines 269-270)
        $fallbackStart = Carbon::parse('2018-01-01');
        $fallbackEnd = Carbon::parse('2030-12-31');

        // Get tasks that are "active" on this date using ETL range logic (etl_pipeline.py line 405)
        // ETL logic: if t['start_date'] <= r_date <= t['due_date']
        $tasks = Task::where('assigned_user_id', $employeeId)
            ->where('active', true)
            ->whereRaw('COALESCE(start_date, ?) <= ?', [$fallbackStart->format('Y-m-d'), $date->format('Y-m-d')])
            ->whereRaw('COALESCE(due_date, ?) >= ?', [$fallbackEnd->format('Y-m-d'), $date->format('Y-m-d')])
            ->get();

        // Enhanced fallback for minimal data scenarios
        if ($tasks->isEmpty()) {
            Log::warning("No tasks found with date range logic for employee {$employeeId}, trying recent activity fallback");

            // Look for tasks with recent activity in last 30 days
            $tasks = Task::where('assigned_user_id', $employeeId)
                ->where('active', true)
                ->where('percentage', '>', 0)
                ->where('updated_at', '>=', $date->copy()->subDays(30))
                ->get();
        }

        if ($tasks->isEmpty()) {
            Log::info("No tasks found for employee {$employeeId} on {$date->format('Y-m-d')} - will use attendance-only scoring");
            return [
                'tasks_completed' => 0,
                'avg_task_score' => 0,
                'avg_task_percentage' => 0
            ];
        }

        $completedTasks = $tasks->where('percentage', 100)->count();
        $totalTasks = $tasks->count();

        // Handle tasks with percentage but no score (common issue in database)
        $tasksWithScore = $tasks->where('score', '>', 0);
        $tasksWithoutScore = $tasks->where('score', '<=', 0)->where('percentage', '>', 0);

        $avgScore = 0;
        if ($tasksWithScore->count() > 0) {
            $avgScore = $tasksWithScore->avg('score');

            // Estimate score for tasks with percentage but no score
            // UPDATED: Database uses 0-100 scale, so map percentage directly to score scale
            // 100% completion = 100 score (instead of 10 score)
            if ($tasksWithoutScore->count() > 0) {
                $estimatedScore = $tasksWithoutScore->avg('percentage'); // 1:1 mapping for 0-100 scale

                // Weighted average of actual scores and estimated scores
                $avgScore = (
                    ($avgScore * $tasksWithScore->count()) +
                    ($estimatedScore * $tasksWithoutScore->count())
                ) / $tasks->count();
            }
        } elseif ($tasksWithoutScore->count() > 0) {
            // Only tasks with percentage but no score - estimate all scores
            // Map percentage directly to score (both are 0-100 scale)
            $avgScore = $tasksWithoutScore->avg('percentage');
        }

        $avgPercentage = $tasks->avg('percentage') ?? 0;

        // Diagnostic logging to track task query effectiveness
        Log::info("Task query results for employee {$employeeId} on {$date->format('Y-m-d')}", [
            'total_tasks' => $tasks->count(),
            'completed_tasks' => $completedTasks,
            'tasks_with_score' => $tasksWithScore->count(),
            'tasks_without_score' => $tasksWithoutScore->count(),
            'avg_percentage' => round($avgPercentage, 1),
            'avg_score' => round($avgScore, 2),
            'null_start_dates' => $tasks->whereNull('start_date')->count(),
            'null_due_dates' => $tasks->whereNull('due_date')->count()
        ]);

        return [
            'tasks_completed' => $completedTasks,
            'avg_task_score' => round($avgScore, 2),
            'avg_task_percentage' => round($avgPercentage, 2)
        ];
    }

    /**
     * Compute productivity score using the same algorithm as ETL pipeline
     * Ported from etl/etl_pipeline.py lines 229-253
     *
     * @param float $hoursWorked Hours worked that day
     * @param bool $isLate Whether employee was late
     * @param bool $checkedIn Whether employee checked in
     * @param bool $hadDayOff Whether employee had approved day off
     * @param int $tasksCompleted Number of completed tasks
     * @param float $avgTaskScore Average task score (0-100 based on actual database data)
     * @param float $avgTaskPercentage Average task completion percentage
     * @return float Productivity score (0-100)
     */
    private function computeProductivity(
        float $hoursWorked,
        bool $isLate,
        bool $checkedIn,
        bool $hadDayOff,
        int $tasksCompleted,
        float $avgTaskScore,
        float $avgTaskPercentage
    ): float {
        // If had day off and didn't check in, productivity is 0
        if ($hadDayOff && !$checkedIn) {
            return 0.0;
        }

        // Calculate component scores (normalized to 0-1)
        $hoursScore = min($hoursWorked / 8.0, 1.0); // Normalize to 8-hour day
        $attendance = ($checkedIn && !$isLate) ? 1.0 : ($checkedIn ? 0.5 : 0.0);

        // FIXED: Database uses 0-100 scale for task scores, not 0-10 as ETL pipeline expects
        // Based on log analysis: avg scores of 26-38 indicate 0-100 scale
        $taskScoreNorm = min($avgTaskScore / 100.0, 1.0); // Changed from 10.0 to 100.0
        $taskPercentageNorm = min($avgTaskPercentage / 100.0, 1.0); // Normalize to 0-100 scale

        // Check if employee has task activity
        $hasTasks = $tasksCompleted > 0 || $avgTaskScore > 0 || $avgTaskPercentage > 0;

        // Apply weighted formula based on whether there are tasks
        if ($hasTasks) {
            // With task signal: weight tasks more heavily
            $score = (
                0.25 * $attendance +
                0.25 * $hoursScore +
                0.30 * $taskPercentageNorm +
                0.20 * $taskScoreNorm
            ) * 100;
        } else {
            // Without task signal: focus on attendance and hours
            $score = (
                0.60 * $attendance +
                0.40 * $hoursScore
            ) * 100;
        }

        // Log the calculation for debugging
        Log::debug("Productivity calculation for task score {$avgTaskScore}", [
            'hours_worked' => $hoursWorked,
            'hours_score' => $hoursScore,
            'attendance' => $attendance,
            'avg_task_score' => $avgTaskScore,
            'task_score_norm' => $taskScoreNorm,
            'avg_task_percentage' => $avgTaskPercentage,
            'task_percentage_norm' => $taskPercentageNorm,
            'has_tasks' => $hasTasks,
            'final_score' => round($score, 2)
        ]);

        return round($score, 2);
    }
}