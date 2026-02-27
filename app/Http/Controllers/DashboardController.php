<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Project;

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
        return view('userdashboard', array_merge(['user' => $user], $data));
    }

    public function upcomingTasks()
    {
        $staff = Auth::user();
        $data = $this->dashboardService->getStaffDashboardData($staff);

        $teamMembers = User::query()
            ->where('team_leader_id', $staff->id)
            ->whereDoesntHave('roles', fn($q) => $q->whereIn('name', ['admin', 'staff']))
            ->get();

        $projects = Project::with('tasks')
            ->where('staff_id', $staff->id) 
            ->latest()
            ->take(3)
            ->get();

        return view('staffdashboard', array_merge($data, [
            'staff'         => $staff,
            'teamMembers'   => $teamMembers,
            'projects'      => $projects,
            'dashboardMode' => 'staff',
        ]));
    }

    public function substaffDashboard()
    {
        abort_unless(auth()->user()->can('staff.dashboard.view'), 403);

        $staff = Auth::user();
        $data = $this->dashboardService->getStaffDashboardData($staff);

        $projects = Project::with('tasks')
            ->where('staff_id', $staff->id)
            ->latest()
            ->take(3)
            ->get();

        $teamMembers = collect();

        return view('staffdashboard', array_merge($data, [
            'projects'      => $projects,
            'teamMembers'   => $teamMembers,
            'dashboardMode' => 'substaff',
        ]));
    }
}