<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AdminDashboardService;

class AdminDashboardController extends Controller
{
    protected $dashboardService;

    public function __construct(AdminDashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function index()
    {
        $recentLogs = $this->dashboardService->getRecentLogs();
        $recentCheckIns = $this->dashboardService->getRecentCheckIns();

        return view('admindashboard', compact('recentLogs', 'recentCheckIns'));
    }

    public function viewAllLogs(Request $request)
    {
        $filters = $request->all();
        $paginatedLogs = $this->dashboardService->getCombinedLogs($filters);
        $distinctActions = $this->dashboardService->getDistinctActions();

        return view('activity_logs', [
            'allLogs' => $paginatedLogs,
            'distinctActions' => $distinctActions,
        ]);
    }
}
