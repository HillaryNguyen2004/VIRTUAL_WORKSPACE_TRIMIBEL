<?php

namespace App\Services;

use App\Models\User;
use App\Models\CompanyHour;
use App\Models\CheckIn;
use App\Models\DayOffRequest;
use App\Models\Holiday;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class AttendanceReportService
{
    /**
     * Calculate expected hours and actual hours for a user in a given month
     */
    public function getMonthlyAttendanceReport(User $user, int $year, int $month): array
    {
        $startDate = Carbon::createFromDate($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();

        $companyHour = CompanyHour::first();
        if (!$companyHour) {
            return [
                'expected_hours' => 0,
                'actual_hours' => 0,
                'variance' => 0,
                'month' => $startDate->format('F Y'),
            ];
        }

        // Calculate expected hours
        $expectedHours = $this->calculateExpectedHours($startDate, $endDate, $user, $companyHour);

        // Calculate actual hours
        $actualHours = $this->calculateActualHours($user, $startDate, $endDate);

        // Calculate variance
        $variance = $actualHours - $expectedHours;

        return [
            'expected_hours' => round($expectedHours, 2),
            'actual_hours' => round($actualHours, 2),
            'variance' => round($variance, 2),
            'month' => $startDate->format('F Y'),
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
        ];
    }

    /**
     * Calculate base expected hours for the month
     */
    private function calculateExpectedHours(Carbon $startDate, Carbon $endDate, User $user, CompanyHour $companyHour): float
    {
        $workingDays = $companyHour->getWorkingDaysArray();
        $dailyHours = $this->getDailyWorkingHours($companyHour);
        
        $totalExpectedHours = 0;

        // Iterate through each day in the month
        foreach (CarbonPeriod::create($startDate, $endDate) as $date) {
            $dayName = $date->format('l'); // e.g., "Monday"
            
            // Check if this day is a working day
            if (!in_array($dayName, $workingDays)) {
                continue;
            }

            // Check if day is a holiday
            $isHoliday = Holiday::where('start_date', '<=', $date->toDateString())
                ->where(function ($query) use ($date) {
                    $query->whereNull('end_date')
                        ->orWhere('end_date', '>=', $date->toDateString());
                })
                ->exists();

            if ($isHoliday) {
                continue;
            }

            // Get day-off requests for this date
            $dayOff = DayOffRequest::where('user_id', $user->id)
                ->where('date', $date->toDateString())
                ->where('status', 'APPROVED')
                ->first();

            if ($dayOff) {
                // Full day off - subtract full day hours
                if ($dayOff->leave_type === 'OFF_FULL') {
                    continue;
                }

                // Half day off - subtract half hours
                if ($dayOff->leave_type === 'OFF_HALF') {
                    $totalExpectedHours += $dailyHours / 2;
                    continue;
                }
            }

            // Add full day hours
            $totalExpectedHours += $dailyHours;
        }

        return $totalExpectedHours;
    }

    /**
     * Calculate actual hours worked from check-in/check-out
     */
    private function calculateActualHours(User $user, Carbon $startDate, Carbon $endDate): float
    {
        $checkIns = CheckIn::query()
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->where(function ($query) use ($user) {
                $query->where('user_name', $user->name)
                      ->orWhere('user_name', $user->username);
            })
            ->get();

        $totalMinutes = 0;

        foreach ($checkIns as $checkIn) {
            if ($checkIn->check_in_time && $checkIn->check_out_time) {
                $checkInTime = Carbon::createFromFormat('H:i:s', $checkIn->check_in_time);
                $checkOutTime = Carbon::createFromFormat('H:i:s', $checkIn->check_out_time);

                // Handle overnight shifts
                if ($checkOutTime->lessThan($checkInTime)) {
                    $checkOutTime->addDay();
                }

                $minutes = $checkInTime->diffInMinutes($checkOutTime);
                $totalMinutes += $minutes;
            }
        }

        // Convert minutes to hours
        return $totalMinutes / 60;
    }

    /**
     * Get daily working hours from company hour configuration
     */
    private function getDailyWorkingHours(CompanyHour $companyHour): float
    {
        $start = Carbon::createFromFormat('H:i:s', $companyHour->start_at);
        $end = Carbon::createFromFormat('H:i:s', $companyHour->end_at);

        $totalMinutes = $start->diffInMinutes($end);

        // Subtract lunch/midday break
        if ($companyHour->lunch_start && $companyHour->lunch_end) {
            $lunchStart = Carbon::createFromFormat('H:i:s', $companyHour->lunch_start);
            $lunchEnd = Carbon::createFromFormat('H:i:s', $companyHour->lunch_end);
            $breakMinutes = $lunchStart->diffInMinutes($lunchEnd);
            $totalMinutes -= $breakMinutes;
        } elseif ($companyHour->mid_day) {
            // If mid_day exists, assume it's a point in time, not a break duration
            // So don't subtract anything for mid_day
        }

        return $totalMinutes / 60;
    }

    /**
     * Get all months available (last 12 months)
     */
    public function getAvailableMonths(): array
    {
        $months = [];
        for ($i = 0; $i < 12; $i++) {
            $date = Carbon::now()->subMonths($i);
            $key = $date->format('Y-m');
            $label = $date->format('F Y');
            $months[$key] = $label;
        }
        return $months;
    }
}
