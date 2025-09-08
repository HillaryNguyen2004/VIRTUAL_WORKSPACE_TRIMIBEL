<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Support\Facades\Auth;
use App\Models\CompanyHour;

class DashboardController extends Controller
{
    protected $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function index()
    {
        // $workingHour = CompanyHour::first();
        // return view('dashboard', compact('workingHour', 'teamLeader', 'teamMembers', 'assignedTasks'));
        return view('dashboard');
    }

    public function user()
    {
        $user = Auth::user();
        $workingHour = CompanyHour::first();
        $data = $this->dashboardService->getUserDashboardData($user);
        // return view('dashboard', array_merge(['user' => $user], $data));
        return view('userdashboard', array_merge(['user' => $user, 'workingHour' => $workingHour], $data));
    }

    public function upcomingTasks()
    {
        $user = Auth::user();
        $data = $this->dashboardService->getStaffDashboardData($user);
        return view('staffdashboard', $data);
    }
}
