<?php
namespace App\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use App\Repositories\ActivityLogRepository;
use App\Repositories\DayOffRequestRepository;
use App\Repositories\CheckInRepository;

class AdminDashboardService
{
    protected $activityLogRepo;
    protected $dayOffRepo;
    protected $checkInRepo;

    public function __construct(
        ActivityLogRepository $activityLogRepo,
        DayOffRequestRepository $dayOffRepo,
        CheckInRepository $checkInRepo
    ) {
        $this->activityLogRepo = $activityLogRepo;
        $this->dayOffRepo = $dayOffRepo;
        $this->checkInRepo = $checkInRepo;
    }

    public function getRecentLogs()
    {
        return $this->activityLogRepo->getRecentLogs();
    }

    public function getRecentCheckIns()
    {
        return $this->checkInRepo->getRecentCheckIns();
    }

    public function getCombinedLogs(array $filters): LengthAwarePaginator
    {
        $logs = $this->activityLogRepo->getFilteredLogs($filters);
        $dayOffs = $this->dayOffRepo->getApprovedFullDayOffs($filters);

        $combined = collect($logs)
            ->merge($dayOffs)
            ->sortBy($filters['sort_by'] ?? 'created_at', SORT_REGULAR, ($filters['sort_dir'] ?? 'desc') === 'desc');

        $perPage = 3;
        $page = $filters['page'] ?? 1;

        return new LengthAwarePaginator(
            $combined->forPage($page, $perPage),
            $combined->count(),
            $perPage,
            $page,
            ['path' => url()->current(), 'query' => $filters]
        );
    }

    public function getDistinctActions()
    {
        return $this->activityLogRepo->getDistinctActions();
    }
}
