<?php

namespace App\Http\Controllers;

use App\Services\TeamService;
use App\Models\Task;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TeamController extends Controller
{
    protected TeamService $teamService;

    public function __construct(TeamService $teamService)
    {
        $this->teamService = $teamService;
    }

    /**
     * =========================
     * TEAM KPI DASHBOARD (BY PROJECT)
     * =========================
     */
    public function index()
    {
        $userId = Auth::id();
        
        // Get all projects managed by this staff member
        $projects = Project::where('staff_id', $userId)
            ->with(['tasks' => function($query) {
                $query->where('active', 1)
                    ->with('users'); // Load the assigned users via task_user pivot
            }])
            ->get();

        $projectData = [];
        $allTeamMembers = collect(); // Track all members across all projects
        
        foreach ($projects as $project) {
            // Get all team members assigned to tasks in this project
            $teamMembers = collect();
            
            // Collect all unique users from all tasks in this project
            foreach ($project->tasks as $task) {
                foreach ($task->users as $user) {
                    $teamMembers->put($user->id, $user);
                    $allTeamMembers->put($user->id, $user);
                }
            }

            // Calculate completion % per team member FOR THIS PROJECT
            $memberCompletion = [];
            
            foreach ($teamMembers as $member) {
                // Get all tasks assigned to this user in THIS SPECIFIC PROJECT
                $memberTasks = $project->tasks->filter(function($task) use ($member) {
                    return $task->users->contains('id', $member->id);
                });
                
                if ($memberTasks->count() > 0) {
                    // Count tasks by status
                    $completedTasks = $memberTasks->where('status', 'completed')->count();
                    $inProgressTasks = $memberTasks->where('status', 'in_progress')->count();
                    $pendingTasks = $memberTasks->where('status', 'pending')->count();
                    $totalTasks = $memberTasks->count();
                    
                    // Calculate weighted average completion
                    $totalPercentage = 0;
                    $inProgressWeightedSum = 0;
                    
                    foreach ($memberTasks as $task) {
                        if ($task->status === 'completed') {
                            $totalPercentage += 100;
                        } elseif ($task->status === 'in_progress' && $task->percentage !== null) {
                            $totalPercentage += $task->percentage;
                            $inProgressWeightedSum += $task->percentage;
                        }
                        // pending tasks add 0%
                    }
                    
                    $weightedAverage = $totalTasks > 0 ? ($totalPercentage / $totalTasks) : 0;
                    $avgInProgressPercentage = $inProgressTasks > 0 ? 
                        ($inProgressWeightedSum / $inProgressTasks) : 0;
                    
                    $memberCompletion[$member->id] = [
                        'user' => [
                            'id' => $member->id,
                            'name' => $member->name,
                        ],
                        'completion_percentage' => round($weightedAverage, 1),
                        'completed_tasks' => $completedTasks,
                        'in_progress_tasks' => $inProgressTasks,
                        'pending_tasks' => $pendingTasks,
                        'total_tasks' => $totalTasks,
                        'avg_in_progress_percentage' => round($avgInProgressPercentage, 1),
                    ];
                }
            }

            // Calculate overall project completion
            $allProjectTasks = $project->tasks;
            $projectCompletedTasks = $allProjectTasks->where('status', 'completed')->count();
            $projectTotalTasks = $allProjectTasks->count();
            $projectCompletion = $project->progress ?? 0;
            
            if ($projectCompletion == 0 && $projectTotalTasks > 0) {
                $projectCompletion = ($projectCompletedTasks / $projectTotalTasks) * 100;
            }

            // Get tasks by status for this project
            $projectTaskStatus = $allProjectTasks
                ->groupBy('status')
                ->map->count();

            $projectData[] = [
                'project' => $project,
                'team_members' => $memberCompletion,
                'project_completion' => round($projectCompletion, 1),
                'task_status' => $projectTaskStatus,
                'total_tasks' => $projectTotalTasks,
                'completed_tasks' => $projectCompletedTasks,
            ];
        }

        // NEW: Calculate performance data for RADAR CHARTS (individual members across projects)
        $memberPerformanceData = $this->calculateMemberPerformanceData($projects, $allTeamMembers);

        return view('tasks.staff.team', [
            'projects' => $projectData,
            'member_performance' => $memberPerformanceData,
            'all_members' => $allTeamMembers->values(),
        ]);
    }

    /**
     * Calculate performance metrics for each member across all projects
     * For RADAR CHART
     */
    private function calculateMemberPerformanceData($projects, $allTeamMembers)
    {
        $performanceData = [];
        
        foreach ($allTeamMembers as $member) {
            $memberProjects = [];
            $memberTasks = [];
            
            foreach ($projects as $project) {
                // Get tasks for this member in this project
                $projectTasks = $project->tasks->filter(function($task) use ($member) {
                    return $task->users->contains('id', $member->id);
                });
                
                if ($projectTasks->count() > 0) {
                    // Calculate metrics for this project
                    $completedTasks = $projectTasks->where('status', 'completed')->count();
                    $totalTasks = $projectTasks->count();
                    $completionRate = $totalTasks > 0 ? ($completedTasks / $totalTasks) * 100 : 0;
                    
                    // Calculate average progress for in-progress tasks
                    $inProgressTasks = $projectTasks->where('status', 'in_progress')->whereNotNull('percentage');
                    $avgProgress = $inProgressTasks->count() > 0 ? $inProgressTasks->avg('percentage') : 0;
                    
                    // Calculate on-time completion rate
                    $completedOnTime = $projectTasks->where('status', 'completed')
                        ->filter(function($task) {
                            return $task->completed_at && $task->due_date && 
                                   $task->completed_at <= $task->due_date;
                        })->count();
                    
                    $onTimeRate = $completedTasks > 0 ? ($completedOnTime / $completedTasks) * 100 : 0;
                    
                    // Calculate task quality (placeholder - you can add actual quality metrics)
                    $qualityScore = $this->calculateQualityScore($projectTasks);
                    
                    // Collect data for radar chart
                    $memberProjects[$project->id] = [
                        'project_name' => $project->name,
                        'completion_rate' => round($completionRate, 1),
                        'avg_progress' => round($avgProgress, 1),
                        'on_time_rate' => round($onTimeRate, 1),
                        'quality_score' => round($qualityScore, 1),
                        'task_count' => $totalTasks,
                    ];
                    
                    // Add to total tasks array
                    $memberTasks = array_merge($memberTasks, $projectTasks->all());
                }
            }
            
            // Calculate overall averages for radar chart metrics
            if (!empty($memberProjects)) {
                $performanceData[$member->id] = [
                    'user' => [
                        'id' => $member->id,
                        'name' => $member->name,
                    ],
                    'projects' => $memberProjects,
                    'overall_completion' => round(collect($memberProjects)->avg('completion_rate'), 1),
                    'overall_progress' => round(collect($memberProjects)->avg('avg_progress'), 1),
                    'overall_on_time' => round(collect($memberProjects)->avg('on_time_rate'), 1),
                    'overall_quality' => round(collect($memberProjects)->avg('quality_score'), 1),
                    'total_task_count' => count($memberTasks),
                    'active_projects' => count($memberProjects),
                ];
            }
        }
        
        return $performanceData;
    }
    
    /**
     * Calculate quality score for tasks (simplified version)
     * You can customize this based on your actual quality metrics
     */
    private function calculateQualityScore($tasks)
    {
        // Placeholder: Implement based on your quality metrics
        // For now, using a combination of factors:
        $total = 0;
        $count = 0;
        
        foreach ($tasks as $task) {
            if ($task->status === 'completed') {
                // Simulate quality score (0-100)
                // You can replace with actual quality metrics
                $score = 80; // Base score
                
                // Adjust based on task characteristics
                if ($task->percentage === 100) $score += 10;
                if ($task->completed_at && $task->due_date && $task->completed_at <= $task->due_date) {
                    $score += 10;
                }
                
                $total += min($score, 100); // Cap at 100
                $count++;
            }
        }
        
        return $count > 0 ? $total / $count : 70; // Default 70 if no completed tasks
    }
}