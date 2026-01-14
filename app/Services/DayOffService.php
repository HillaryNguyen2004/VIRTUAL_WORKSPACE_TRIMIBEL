<?php

namespace App\Services;
use Carbon\Carbon;
use App\Models\User;
use App\Notifications\DayOffRequestStatusNotification;
use App\Repositories\DayOffRequestRepository;
use App\Repositories\CompanyHoursRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Models\CompanyHour;


class DayOffService
{
    protected DayOffRequestRepository $repo;
    protected CompanyHoursRepository $companyHoursRepo;

    public function __construct(
        DayOffRequestRepository $repo,
        CompanyHoursRepository $companyHoursRepo
    ) {
        $this->repo = $repo;
        $this->companyHoursRepo = $companyHoursRepo;
    }

    /* =====================================================
     | CREATE REQUEST
     ===================================================== */
    public function createRequest(array $data): array
    {
        $userId = Auth::id();
        $dates  = $data['dates'] ?? [];

        if (!is_array($dates) || empty($dates)) {
            return ['error' => 'Please select at least one date.'];
        }

        // Prevent overlapping requests
        foreach ($dates as $date) {
            if ($this->repo->findByUserAndDate($userId, $date)) {
                return [
                    'error' => "You already have a day-off request for {$date}."
                ];
            }
        }

        $companyHours = $this->companyHoursRepo->getCompanyHours();

        $halfDayPeriod = null;
        $timeRange     = null;

        if (
            $data['leave_type'] === 'OFF_HALF'
            && !empty($data['half_day_period'])
        ) {
            $halfDayPeriod = $data['half_day_period'];
            $timeRange = $this->calculateHalfDayTimeRange(
                $companyHours,
                $halfDayPeriod
            );
        }

        $created = [];

        foreach ($dates as $date) {
            $created[] = $this->repo->create([
                'user_id'          => $userId,
                'date'             => $date,
                'leave_type'       => $data['leave_type'],
                'half_day_period'  => $halfDayPeriod,
                'reason'           => $data['reason'] ?? null,
                'status'           => 'PENDING',
                'request_group_id' => $data['request_group_id'] ?? null,
                'start_time'       => $timeRange['start_time'] ?? null,
                'end_time'         => $timeRange['end_time'] ?? null,
            ]);
        }

        $this->notifyStaff($userId, $dates, $data, $timeRange);

        return [
            'success' => true,
            'count'   => count($created),
        ];
    }

    /* =====================================================
     | HALF DAY TIME LOGIC (CORE BUSINESS RULE)
     ===================================================== */
    private function calculateHalfDayTimeRange($companyHours, string $period): array
    {
        if (!$companyHours) {
            return $this->defaultHalfDayRange($period);
        }

        $startAt = Carbon::parse($companyHours->start_at);
        $endAt   = Carbon::parse($companyHours->end_at);

        // CASE 1: LUNCH BREAK EXISTS (mid_day MUST be null)
        if ($companyHours->lunch_start && $companyHours->lunch_end) {
            $lunchStart = Carbon::parse($companyHours->lunch_start);
            $lunchEnd   = Carbon::parse($companyHours->lunch_end);

            return $period === 'AM'
                ? [
                    'start_time' => $startAt->format('H:i:s'),
                    'end_time'   => $lunchStart->format('H:i:s'),
                ]
                : [
                    'start_time' => $lunchEnd->format('H:i:s'),
                    'end_time'   => $endAt->format('H:i:s'),
                ];
        }

        // CASE 2: ONLY MID DAY EXISTS
        if ($companyHours->mid_day) {
            $midDay = Carbon::parse($companyHours->mid_day);

            return $period === 'AM'
                ? [
                    'start_time' => $startAt->format('H:i:s'),
                    'end_time'   => $midDay->format('H:i:s'),
                ]
                : [
                    'start_time' => $midDay->format('H:i:s'),
                    'end_time'   => $endAt->format('H:i:s'),
                ];
        }

        // CASE 3: FALLBACK – split by total hours
        return $this->splitByTotalHours($startAt, $endAt, $period);
    }

    private function splitByTotalHours(
        Carbon $start,
        Carbon $end,
        string $period
    ): array {
        $halfMinutes = $start->diffInMinutes($end) / 2;
        $mid = $start->copy()->addMinutes($halfMinutes);

        return $period === 'AM'
            ? [
                'start_time' => $start->format('H:i:s'),
                'end_time'   => $mid->format('H:i:s'),
            ]
            : [
                'start_time' => $mid->format('H:i:s'),
                'end_time'   => $end->format('H:i:s'),
            ];
    }

    private function defaultHalfDayRange(string $period): array
    {
        return $period === 'AM'
            ? ['start_time' => '09:00:00', 'end_time' => '13:00:00']
            : ['start_time' => '13:00:00', 'end_time' => '17:00:00'];
    }

    /* =====================================================
     | NOTIFICATIONS
     ===================================================== */
    private function notifyStaff(
        int $userId,
        array $dates,
        array $data,
        ?array $timeRange
    ): void {
        try {
            $user = User::find($userId);
            if (!$user) {
                return;
            }

            $staffUsers = User::role('staff')->get();
            if ($staffUsers->isEmpty()) {
                return;
            }

            $dateText = count($dates) > 1
                ? "{$dates[0]} → {$dates[array_key_last($dates)]}"
                : $dates[0];

            $halfInfo = '';
            if ($data['leave_type'] === 'OFF_HALF' && $timeRange) {
                $halfInfo = " ({$data['half_day_period']}: {$timeRange['start_time']} - {$timeRange['end_time']})";
            }

            Notification::send(
                $staffUsers,
                new \App\Notifications\DayOffRequestCreatedNotification(
                    $user->id,
                    $user->name,
                    $dateText . $halfInfo
                )
            );
        } catch (\Throwable $e) {
            Log::error('DayOff notification error', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /* =====================================================
     | STAFF ACTIONS
     ===================================================== */
    public function getPendingRequests()
    {
        return $this->repo->getPendingWithUsers();
    }

    public function approveRequest($id)
    {
        $request = $this->repo->updateStatus($id, 'APPROVED', Auth::id());

        if ($request && $request->user) {
            $request->user->notify(
                new DayOffRequestStatusNotification('APPROVED', $request->date)
            );
        }

        return $request;
    }

    public function rejectRequest($id)
    {
        $request = $this->repo->updateStatus($id, 'REJECTED', Auth::id());

        if ($request && $request->user) {
            $request->user->notify(
                new DayOffRequestStatusNotification('REJECTED', $request->date)
            );
        }

        return $request;
    }
}
