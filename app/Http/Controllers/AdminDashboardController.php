<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AdminDashboardService;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;

// Models
use App\Models\User;
use App\Models\Project;
use App\Models\Campaign;
use App\Models\EmailTemplate;
use App\Models\CompanyHour;
use App\Models\CheckIn;
use App\Models\ActivityLog;
use App\Models\DayOffRequest;
use App\Models\Holiday;
use App\Models\Department;

class AdminDashboardController extends Controller
{
    public function index()
    {
        // --- 1. RECENT LOGS ---
        $recentLogs = ActivityLog::with('user')
            ->latest()
            ->take(5)
            ->get();

        // --- 2. RECENT ATTENDANCE ---
        // Your CheckIn model links to User via 'user_name', 
        // but we can also just display the user_name string directly.
        $recentCheckIns = CheckIn::orderBy('date', 'desc')
            ->orderBy('check_in_time', 'desc')
            ->take(5)
            ->get();

        // --- 3. USER STATS ---
        $totalUsersCount = User::count();
        
        // Growth (users created in last 30 days)
        $lastMonthUsers = User::where('created_at', '>=', Carbon::now()->subDays(30))->count();
        $userGrowthPercentage = $totalUsersCount > 0 ? round(($lastMonthUsers / $totalUsersCount) * 100, 1) : 0;

        // Role Counts (Using Spatie's 'role' scope from User model trait)
        $roleCounts = [
            'admin'   => User::role('admin')->count(),
            'staff'   => User::role('staff')->count(),
            'user'    => User::role('user')->count(),

        ];

        // --- 4. COMPANY HOURS ---
        $companyHour = CompanyHour::first();

        // Helper: Convert "12:30:00" to 12.5 (Float) for accurate CSS percentages
        // We use a closure to keep the code clean
        $toDecimal = function($timeStr) {
            if (!$timeStr) return null;
            $c = \Carbon\Carbon::parse($timeStr);
            return $c->hour + ($c->minute / 60);
        };

        if ($companyHour) {
            // CASE A: Record exists - Trust the DB values (even if they are NULL)
            $companyStartHour      = $toDecimal($companyHour->start_at);
            $companyEndHour        = $toDecimal($companyHour->end_at);
            $companyMidDayHour     = $toDecimal($companyHour->mid_day);     // Can be null
            $companyLunchStartHour = $toDecimal($companyHour->lunch_start); // Can be null
            $companyLunchEndHour   = $toDecimal($companyHour->lunch_end);   // Can be null
        } else {
            // CASE B: No Record (Fresh Install) - Force Standard Defaults
            $companyStartHour      = 8;
            $companyEndHour        = 17;
            $companyMidDayHour     = null;
            $companyLunchStartHour = 12;
            $companyLunchEndHour   = 13;
        }

        // --- 5. PROJECTS HEALTH ---
        // Your Project model uses 'title' and has 'tasks' relation
        $projectsHealth = Project::withCount([
            'tasks as total_tasks',
            'tasks as completed' => function ($query) {
                $query->where('status', 'completed');
            },
            'tasks as in_progress' => function ($query) {
                $query->where('status', 'in_progress');
            },
            'tasks as todo' => function ($query) {
                // Adjust 'pending' if your default status is different (e.g., 'open')
                $query->where('status', 'pending');
            }
        ])
        ->take(3)
        ->get();

        // Calculate Overdue tasks manually
        foreach ($projectsHealth as $project) {
            $project->overdue_count = $project->tasks()
                ->where('due_date', '<', now())
                ->where('status', '!=', 'completed')
                ->count();
        }

        // --- 6. CAMPAIGNS ---
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

        // --- 7. EMAIL TEMPLATES ---
        $emailTemplates = EmailTemplate::orderBy('id', 'desc')
            ->take(4)
            ->get();

        // -- 8. HOLIDAYS --
        $upcomingHolidays = Holiday::where('start_date', '>=', now())
            ->orderBy('start_date', 'asc')
            ->get();

        // -- 9. DEPARTMENTS --
        $departmentCount = Department::count();
        $staffInDepartmentsCount = User::whereNotNull('department_id')
            ->where('department_id', '!=', 0)
            ->count();

        return view('admindashboard', compact(
            'recentLogs', 
            'recentCheckIns',
            'totalUsersCount',
            'userGrowthPercentage',
            'roleCounts',
            'companyStartHour',
            'companyEndHour',
            'companyLunchStartHour',
            'companyLunchEndHour',
            'companyMidDayHour',
            'projectsHealth',
            'upcomingCampaigns',
            'sentCampaigns',
            'emailTemplates',
            'upcomingHolidays',
            'departmentCount',
            'staffInDepartmentsCount',
        ));
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
