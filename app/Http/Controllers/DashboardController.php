<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Support\Facades\Auth;
use App\Models\CompanyHour;
use App\Models\User;

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

    // public function upcomingTasks()
    // {
    //     $user = Auth::user();
    //     $data = $this->dashboardService->getStaffDashboardData($user);
    //     return view('staffdashboard', $data);
    // }

    public function upcomingTasks()
    {
        $staff = Auth::user();
        $data = $this->dashboardService->getStaffDashboardData($staff);

        $teamMembers = User::query()
            ->where('team_leader_id', $staff->id)
            ->whereDoesntHave('roles', fn($q) => $q->whereIn('name', ['admin', 'staff'])) // optional
            ->get();

        return view('staffdashboard', array_merge($data, [
            'staff' => $staff,
            'teamMembers' => $teamMembers,
        ]));
    }

    public function substaffDashboard()
{
    // permission middleware already checks, but keep safe:
    abort_unless(auth()->user()->can('staff.dashboard.view'), 403);
    $staff = Auth::user();
    // Load same data as staff dashboard (or adjust if needed)
    // $projects = auth()->user()->projects()->with('tasks')->latest()->get(); // adjust to your app
    $projects = Project::with('tasks')
        ->where('staff_id', $staff->id)
        ->latest()
        ->take(3)
        ->get();
    // $teamMembers = collect(); // optional, or load if you want substaff to manage members

    // Use SAME blade, but pass a flag to change label + routes
    return view('staffdashboard', [
        'projects' => $projects,
        'dashboardMode' => 'substaff', // 👈 important
    ]);
}

}
