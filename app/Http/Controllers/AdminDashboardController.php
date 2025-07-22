<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ActivityLog;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $recentLogs = ActivityLog::latest()
            ->with('user')
            ->take(3)
            ->get();

        $recentCheckIns = DB::table('check_ins')
            ->orderBy('date', 'desc')
            ->orderBy('check_in_time', 'desc')
            ->limit(3)
            ->get();

        return view('admindashboard', compact('recentLogs', 'recentCheckIns'));
    }

    public function viewAllLogs(Request $request)
    {
        $sort = $request->input('sort_by', 'created_at');
        $dir = $request->input('sort_dir', 'desc');

        // Get activity logs
        $activityLogs = ActivityLog::with('user')
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('action', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhereHas('user', function ($q) use ($search) {
                          $q->where('name', 'like', "%{$search}%");
                      });
                });
            })
            ->when($request->filled('action'), function ($query) use ($request) {
                $query->where('action', $request->action);
            })
            ->get()
            ->map(function ($log) {
                return (object)[
                    'id' => $log->id,
                    'user_name' => $log->user->name ?? 'N/A',
                    'action' => $log->action,
                    'created_at' => $log->created_at,
                    'description' => $log->description,
                ];
            });

        // Approved full day off requests (not half-day)
        $dayOffs = DB::table('day_off_requests')
            ->join('users', 'day_off_requests.user_id', '=', 'users.id')
            ->where('day_off_requests.status', 'APPROVED')
            ->where('day_off_requests.leave_type', '!=', 'OFF_HALF') // Exclude half-day requests
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('users.name', 'like', "%{$search}%")
                      ->orWhere('day_off_requests.date', 'like', "%{$search}%");
                });
            })
            ->select([
                DB::raw('CONCAT("D-", day_off_requests.id) as id'),
                DB::raw('users.name as user_name'),
                DB::raw('"Day Off Approved" as action'),
                DB::raw('day_off_requests.updated_at as created_at'),
                DB::raw('CONCAT("Full day off approved for ", DATE_FORMAT(day_off_requests.date, "%Y-%m-%d")) as description'),
            ])
            ->get();

        // Merge and sort both logs and day offs
        $combined = collect($activityLogs)
            ->merge($dayOffs)
            ->sortBy($sort, SORT_REGULAR, $dir === 'desc');

        // Paginate manually
        $perPage = 3;
        $page = $request->input('page', 1);
        $paginatedLogs = new LengthAwarePaginator(
            $combined->forPage($page, $perPage),
            $combined->count(),
            $perPage,
            $page,
            ['path' => url()->current(), 'query' => $request->query()]
        );

        $distinctActions = ActivityLog::select('action')->distinct()->pluck('action');

        return view('activity_logs', [
            'allLogs' => $paginatedLogs,
            'distinctActions' => $distinctActions,
        ]);
    }
}