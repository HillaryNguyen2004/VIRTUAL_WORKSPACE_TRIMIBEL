<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    protected $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function index()
    {
        return view('dashboard');
    }

    public function user()
    {
        $user = Auth::user();
        $data = $this->dashboardService->getUserDashboardData($user);
        return view('dashboard', array_merge(['user' => $user], $data));
    }

    public function upcomingTasks()
    {
        $user = Auth::user();
        $data = $this->dashboardService->getStaffDashboardData($user);
        return view('staffdashboard', $data);
    }
}
