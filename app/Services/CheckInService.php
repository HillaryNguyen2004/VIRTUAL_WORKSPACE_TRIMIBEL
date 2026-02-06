<?php
namespace App\Services;

use App\Repositories\CheckInRepositoryInterface;
use Illuminate\Support\Carbon;
use App\Models\DayOffRequest;
use App\Models\User;
use App\Models\CompanyHour;
use Illuminate\Support\Facades\Log;

class CheckInService
{
    protected $checkInRepository;

    public function __construct(CheckInRepositoryInterface $checkInRepository)
    {
        $this->checkInRepository = $checkInRepository;
    }

    private function normalizeName(string $s): string
    {
        $s = mb_strtolower(trim($s), 'UTF-8');
        $s = preg_replace('/\s+/', '', $s); // remove all spaces
        return $s;
    }

    public function processCheckOut(string $username, string $currentUserId): array
    {
        $user = User::where('username', $username)->first();

        if (!$user) {
            return [
                'status' => false,
                'message' => __('messages.invalid_user'),
            ];
        }

        $currentUser = User::find($currentUserId);

        if (!$currentUser) {
            return [
                'status' => false,
                'message' => __('messages.invalid_user'),
            ];
        }

        $currentUsername = $currentUser->username;

        // compare 2 username to check is it checkin with its own username or not, if not then throw error
        if ($this->normalizeName($user->username) !== $this->normalizeName($currentUsername)) {
            return [
                'status' => false,
                'message' => __('messages.wrong_username'),
            ];
        }

        // Get today's check-in by username
        $checkIn = $this->checkInRepository->getTodayCheckIn($user->username);

        if (!$checkIn) {
            return [
                'status' => false,
                'message' => __('messages.not_checked_in'),
            ];
        }

        if ($checkIn->check_out_time !== null) {
            return [
                'status' => false,
                'message' => __('messages.already_checked_out'),
            ];
        }

        $this->checkInRepository->updateCheckOut(
            $checkIn->id,
            Carbon::now('Asia/Ho_Chi_Minh')->toTimeString()
        );

        return [
            'status' => true,
            'message' => __('messages.check_out_success'),
        ];
    }

    public function processCheckIn(string $username, string $currentUserId): array
    {
        $tz = 'Asia/Ho_Chi_Minh';
        $now = Carbon::now($tz);
        $today = $now->toDateString();

        $user = User::where('username', $username)->first();
        if (!$user) {
            return ['status' => false, 'message' => __('messages.invalid_user')];
        }

        $currentUser = User::find($currentUserId);
        if (!$currentUser) {
            return ['status' => false, 'message' => __('messages.invalid_user')];
        }

        // Must check-in with own username
        if ($this->normalizeName($user->username) !== $this->normalizeName($currentUser->username)) {
            return ['status' => false, 'message' => __('messages.wrong_username')];
        }

        // Already checked in?
        if ($this->checkInRepository->hasCheckedInToday($user->username, $today)) {
            return ['status' => false, 'message' => __('messages.already_checked_in')];
        }

        $workingHour = CompanyHour::first();
        if (!$workingHour) {
            return ['status' => false, 'message' => __('messages.working_hour_not_configured')];
        }

        $dayOff = DayOffRequest::where('user_id', $user->id)
            ->where('date', $today)
            ->where('status', 'APPROVED')
            ->first();

        // Build today's base window
        $start = Carbon::createFromFormat('H:i:s', $workingHour->start_at, $tz)
            ->setDate($now->year, $now->month, $now->day);

        $end = Carbon::createFromFormat('H:i:s', $workingHour->end_at, $tz)
            ->setDate($now->year, $now->month, $now->day);

        // Overnight shift support (e.g., 22:00 -> 06:00)
        if ($end->lessThanOrEqualTo($start)) {
            $end->addDay();
            // also if now is after midnight (00:xx) it belongs to "next day" window
            // You can adjust logic here if your shifts span dates in a specific way.
        }

        // Adjust window for half-day off
        if ($dayOff && $dayOff->leave_type === 'OFF_HALF') {
            if ($dayOff->half_day_period === 'AM') {
                // Start becomes afternoon start
                $afternoonStartTime = $workingHour->mid_day ?: $workingHour->lunch_end;
                if ($afternoonStartTime) {
                    $start = Carbon::createFromFormat('H:i:s', $afternoonStartTime, $tz)
                        ->setDate($now->year, $now->month, $now->day);
                }
            } elseif ($dayOff->half_day_period === 'PM') {
                // End becomes before afternoon
                $beforeAfternoonEndTime = $workingHour->mid_day ?: $workingHour->lunch_start;
                if ($beforeAfternoonEndTime) {
                    $end = Carbon::createFromFormat('H:i:s', $beforeAfternoonEndTime, $tz)
                        ->setDate($now->year, $now->month, $now->day);
                }
            }
        }

        // Block if now not in range
        if (!$now->betweenIncluded($start, $end)) {
            return [
                'status' => false,
                'message' => __('messages.not_in_working_hours', [
                    'start' => $start->format('H:i'),
                    'end' => $end->format('H:i'),
                ]),
            ];
        }

        // Late check (based on effective start time)
        $isLate = $now->greaterThan($start);

        // Token (create only when allowed)
        $token = $user->createToken('check-in-token')->plainTextToken;

        // Save NAME into DB (not username)
        $this->checkInRepository->insertCheckIn([
            'user_name' => $user->name,
            'date' => $today,
            'check_in_time' => $now->toTimeString(),
            'created_at' => $now,
            'updated_at' => $now,
            'is_late' => $isLate,
        ]);

        return [
            'status' => true,
            'message' => __('messages.check_in_success'),
            'token' => $token,
        ];
    }
}