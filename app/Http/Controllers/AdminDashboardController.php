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
        $recentCheckIns = CheckIn::orderBy('date', 'desc')
            ->orderBy('check_in_time', 'desc')
            ->take(5)
            ->get();

        $totalUsersForStats = User::count();

        // --- ATTENDANCE STATISTIC: WEEKLY (ROLLING 7 DAYS) ---
        $startDate = Carbon::today()->subDays(6);
        $endDate   = Carbon::today();
        $weeklyLabels = [];
        $weeklyPresent = [];
        $weeklyAbsent = [];
        $weeklyLeave = [];

        // Two queries for the whole 7-day window instead of 7+7 queries in a loop
        $weeklyCheckIns = CheckIn::whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->selectRaw('date, COUNT(DISTINCT user_name) as cnt')
            ->groupBy('date')
            ->pluck('cnt', 'date');

        $weeklyLeaves = DayOffRequest::whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->where('status', 'APPROVED')
            ->selectRaw('date, COUNT(*) as cnt')
            ->groupBy('date')
            ->pluck('cnt', 'date');

        for ($i = 0; $i < 7; $i++) {
            $currentDate = $startDate->copy()->addDays($i);
            $dateStr = $currentDate->format('Y-m-d');

            $weeklyLabels[] = $currentDate->format('D');

            $presentCount = (int) ($weeklyCheckIns[$dateStr] ?? 0);
            $leaveCount   = (int) ($weeklyLeaves[$dateStr] ?? 0);
            $absentCount  = max(0, $totalUsersForStats - $presentCount - $leaveCount);

            $weeklyPresent[] = $presentCount;
            $weeklyLeave[]   = $leaveCount;
            $weeklyAbsent[]  = $absentCount;
        }

        // --- ATTENDANCE STATISTIC: YEARLY (PER MONTH) ---
        $currentYear = Carbon::now()->year;
        $yearlyLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $yearlyPresent = [];
        $yearlyAbsent = [];
        $yearlyLeave = [];

        // Fetch all check-ins for the year to process in PHP (avoids SQL dialect issues with COUNT DISTINCT)
        $checkInsThisYear = CheckIn::whereYear('date', $currentYear)->get(['user_name', 'date']);
        $presentByMonth = [];
        foreach ($checkInsThisYear as $checkIn) {
            $month = Carbon::parse($checkIn->date)->month;
            // Create a unique key for user+date to act as a distinct tracker
            $key = $checkIn->user_name . '_' . $checkIn->date;
            $presentByMonth[$month][$key] = true;
        }

        // Fetch all leaves for the year
        $leavesThisYear = DayOffRequest::whereYear('date', $currentYear)
            ->where('status', 'APPROVED')
            ->get(['date']);
        $leavesByMonth = [];
        foreach ($leavesThisYear as $leave) {
            $month = Carbon::parse($leave->date)->month;
            $leavesByMonth[$month] = ($leavesByMonth[$month] ?? 0) + 1;
        }

        for ($month = 1; $month <= 12; $month++) {
            $presentCount = isset($presentByMonth[$month]) ? count($presentByMonth[$month]) : 0;
            $leaveCount = $leavesByMonth[$month] ?? 0;

            // Calculate active working days in that month up to the current day
            $calcDays = 0;
            if ($month < Carbon::now()->month) {
                $calcDays = Carbon::create($currentYear, $month)->daysInMonth;
            } elseif ($month == Carbon::now()->month) {
                $calcDays = Carbon::now()->day;
            }

            $possibleAttendances = $totalUsersForStats * $calcDays;
            $absentCount = $possibleAttendances - $presentCount - $leaveCount;
            if ($absentCount < 0) $absentCount = 0;

            $yearlyPresent[] = $presentCount;
            $yearlyLeave[]   = $leaveCount;
            $yearlyAbsent[]  = $absentCount;
        }

        // Combine both datasets
        $attendanceChartData = [
            'weekly' => [
                'labels'  => $weeklyLabels,
                'present' => $weeklyPresent,
                'absent'  => $weeklyAbsent,
                'leave'   => $weeklyLeave,
            ],
            'yearly' => [
                'labels'  => $yearlyLabels,
                'present' => $yearlyPresent,
                'absent'  => $yearlyAbsent,
                'leave'   => $yearlyLeave,
            ],
        ];

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
        $workingHour = CompanyHour::first();

        // Helper: Convert "12:30:00" to 12.5 (Float) for accurate CSS percentages
        // We use a closure to keep the code clean
        $toDecimal = function($timeStr) {
            if (!$timeStr) return null;
            $c = \Carbon\Carbon::parse($timeStr);
            return $c->hour + ($c->minute / 60);
        };

        if ($workingHour) {
            // CASE A: Record exists - Trust the DB values (even if they are NULL)
            $companyStartHour      = $toDecimal($workingHour->start_at);
            $companyEndHour        = $toDecimal($workingHour->end_at);
            $companyMidDayHour     = $toDecimal($workingHour->mid_day);     // Can be null
            $companyLunchStartHour = $toDecimal($workingHour->lunch_start); // Can be null
            $companyLunchEndHour   = $toDecimal($workingHour->lunch_end);   // Can be null
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
                $query->where('status', 'pending');
            },
            'tasks as overdue_count' => function ($query) {
                $query->where('due_date', '<', now())
                      ->where('status', '!=', 'completed');
            },
        ])
        ->take(3)
        ->get();

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

        // --- NEW: FETCH DEPARTMENT DISTRIBUTION ---
        // 1. Get all department names to avoid N+1 query loops
        $departmentsMap = Department::pluck('name', 'id');
        
        // 2. Group users by department_id
        $departmentStats = User::select('department_id', \DB::raw('count(*) as count'))
            ->groupBy('department_id')
            ->get()
            ->map(function ($stat) use ($departmentsMap) {
                // Map the ID to the name, fallback to 'Unassigned'
                $deptName = $stat->department_id && isset($departmentsMap[$stat->department_id])
                    ? $departmentsMap[$stat->department_id] 
                    : 'Unassigned';
                    
                return [
                    'name' => $deptName,
                    'count' => $stat->count
                ];
            })
            ->sortByDesc('count')
            ->values();

        return view('admindashboard', compact(
            'recentLogs', 
            'recentCheckIns',
            'totalUsersCount',
            'userGrowthPercentage',
            'roleCounts',
            'workingHour',
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
            'departmentStats',
            'attendanceChartData',
        ));
    }

    public function viewAllLogs(Request $request)
    {
        // Replicating the "Combined Logs" logic from your Service
        // 1. Activity Logs
        $logs = ActivityLog::with('user')->latest()->limit(500)->get();
        
        // 2. Approved Full Day Offs (mimicking DayOffRepo logic)
        $dayOffs = DayOffRequest::where('status', 'APPROVED')
            ->where('leave_type', 'OFF_FULL')
            ->with('user')
            ->limit(500)
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
