<?php

namespace App\Services;

use App\Models\User;
use App\Models\EmailTemplate;
use App\Models\Campaign;
use App\Models\ActivityLog;
use App\Models\CompanyHour;
use App\Models\DayOffRequest;
use Illuminate\Support\Collection;

class DashboardService
{
    public function getUserDashboardData(User $user): array
    {
        $teamLeader = $user->teamLeader;
        $teamMembers = $teamLeader ? $teamLeader->teamMembers : collect();

        $workingHour = CompanyHour::first();

        // Scheduled
        $upcomingCampaigns = Campaign::where('scheduled_at', '>', now())
            ->orderBy('scheduled_at', 'asc')
            ->take(2)
            ->get();

        // Sent (Assuming 'sent' column is boolean or string 'sent')
        $sentCampaigns = Campaign::where('sent', true) // or where('status', 'sent')
            ->withCount('users as sent_count')
            ->orderBy('updated_at', 'desc')
            ->take(1)
            ->get();
        
        $emailTemplates = EmailTemplate::orderBy('id', 'desc')
            ->take(4)
            ->get();

        $recentLogs = ActivityLog::with('user')
            ->latest()
            ->take(5)
            ->get();

        $assignedTasks = $user->assignedTasks()->with('readStatuses')->get();

        return compact('teamLeader', 'teamMembers', 'assignedTasks', 'emailTemplates', 'upcomingCampaigns', 'sentCampaigns', 'recentLogs', 'workingHour');
    }

    public function getStaffDashboardData(User $user): array
    {
        $tasks = $user->assignedTasks()->with('readStatuses')->get();
        $teamLeader = $user->teamLeader;
        
        // FIX: Ensure this is always a Collection!
        $teamMembers = collect(); 

        $workingHour = CompanyHour::first();
        $emailTemplates = EmailTemplate::orderBy('id', 'desc')
            ->take(4)
            ->get();

        $upcomingCampaigns = Campaign::where('scheduled_at', '>', now())
            ->orderBy('scheduled_at', 'asc')
            ->take(2)
            ->get();

        $sentCampaigns = Campaign::where('sent', true)
            ->withCount('users as sent_count')
            ->orderBy('updated_at', 'desc')
            ->take(1)
            ->get();

        $recentDayOffRequests = DayOffRequest::with('user')
            ->where('status', 'PENDING')
            ->whereHas('user', function ($query) use ($user) {
                $query->where('team_leader_id', $user->id);
            })
            ->latest()
            ->take(4)
            ->get();

        return compact('tasks', 'teamLeader', 'teamMembers', 'workingHour', 'emailTemplates', 'upcomingCampaigns', 'sentCampaigns', 'recentDayOffRequests');
    }

    public function viewAllLogs(Request $request)
    {
        // Replicating the "Combined Logs" logic from your Service
        // 1. Activity Logs
        $logs = ActivityLog::with('user')->latest()->get();
        
        // 2. Approved Full Day Offs (mimicking DayOffRepo logic)
        $dayOffs = DayOffRequest::where('status', 'APPROVED')
            ->where('leave_type', 'OFF_FULL')
            ->with('user')
            ->get()
            ->map(function ($dayOff) {
                // Map DayOff to a structure similar to ActivityLog for display consistency
                $dayOff->action = 'Day Off';
                $dayOff->description = 'Approved full day off for ' . $dayOff->date->format('Y-m-d');
                $dayOff->created_at = $dayOff->created_at ?? $dayOff->date; // fallback
                return $dayOff;
            });

        // 3. Merge and Sort
        $combined = $logs->merge($dayOffs)->sortByDesc('created_at');

        // 4. Manual Pagination
        $page = $request->input('page', 1);
        $perPage = 10;
        $sliced = $combined->slice(($page - 1) * $perPage, $perPage)->values();

        $allLogs = new LengthAwarePaginator(
            $sliced,
            $combined->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $distinctActions = ActivityLog::distinct()->pluck('action');

        return view('activity_logs', compact('allLogs', 'distinctActions'));
    }
}
