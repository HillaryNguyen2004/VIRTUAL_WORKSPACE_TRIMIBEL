<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ActivityLog;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $recentLogs = ActivityLog::latest()
            ->with('user')
            ->take(3)
            ->get();

        return view('admindashboard', compact('recentLogs'));
    }
}
