<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ActivityLog;

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


        return view('admindashboard', compact('recentLogs','recentCheckIns'));
    }

    public function viewAllLogs(Request $request)
    {
        // $allLogs = ActivityLog::with('user')->latest()->paginate(3); // Use pagination
        // return view('activity_logs', compact('allLogs'));

        $query = ActivityLog::with('user');

    // Search
    if ($request->filled('search')) {
        $search = $request->input('search');
        $query->where('action', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%")
              ->orWhereHas('user', function ($q) use ($search) {
                  $q->where('name', 'like', "%{$search}%");
              });
    }

    // Filter by action
    if ($request->filled('action')) {
        $query->where('action', $request->input('action'));
    }

    // Sorting
    $sort = $request->input('sort_by', 'created_at');
    $dir = $request->input('sort_dir', 'desc');
    $query->orderBy($sort, $dir);

    $allLogs = $query->paginate(3)->withQueryString(); // Preserve query on pagination

    // For dropdown filter
    $distinctActions = ActivityLog::select('action')->distinct()->pluck('action');

    return view('activity_logs', compact('allLogs', 'distinctActions'));
    }
}
