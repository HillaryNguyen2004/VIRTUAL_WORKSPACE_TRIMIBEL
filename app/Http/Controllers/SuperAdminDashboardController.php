<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class SuperAdminDashboardController extends Controller
{
    public function index()
    {
        return view('super_admin.dashboard');
    }

    public function logs(Request $request)
    {
        Gate::authorize('view-logs');

        $level = $request->query('level', 'all');

        $logs = DB::table('logs')
            ->when($level !== 'all', fn($q) => $q->where('level', $level))
            ->orderByDesc('created_at')
            ->take(100)
            ->get()
            ->map(fn($log) => [
                'time'    => $log->created_at,
                'level'   => $log->level,
                'message' => $log->message,
            ]);

        return view('super_admin.logs', compact('logs', 'level'));
    }

    public function clearLogs()
    {
        Gate::authorize('view-logs');
        DB::table('logs')->truncate();

        return back()->with('success', 'Logs cleared.');
    }
}
