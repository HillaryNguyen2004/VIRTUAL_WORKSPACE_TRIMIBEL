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

    private function normalizeName(string $s): string {
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

        $token = $user->createToken('check-in-token')->plainTextToken;

        $now = Carbon::now('Asia/Ho_Chi_Minh');
        $today = $now->toDateString();

        // Check if already checked in today (by username)
        if ($this->checkInRepository->hasCheckedInToday($user->username, $today)) {
            return [
                'status' => false,
                'message' => __('messages.already_checked_in'),
            ];
        }

        $isLate = false;
        $workingHour = CompanyHour::first();

        if ($workingHour) {
            $dayOff = DayOffRequest::where('user_id', $user->id)
                ->where('date', $now->toDateString())
                ->whereIn('status', ['APPROVED'])
                ->first();

            $configuredStart = Carbon::createFromFormat(
                'H:i:s',
                $workingHour->start_at,
                'Asia/Ho_Chi_Minh'
            )->setDate($now->year, $now->month, $now->day);

            // If half day off in the morning, set start time to afternoon start time
            if ($dayOff && $dayOff->leave_type === 'OFF_HALF' && $dayOff->half_day_period === 'AM') {
                // If mid_day is set, use it as the start time, otherwise use lunch_end
                if ($workingHour->mid_day) {
                    $configuredStart = Carbon::createFromFormat(
                        'H:i:s',
                        $workingHour->mid_day,
                        'Asia/Ho_Chi_Minh'
                    )->setDate($now->year, $now->month, $now->day);
                } else {
                    $configuredStart = Carbon::createFromFormat(
                        'H:i:s',
                        $workingHour->lunch_end,
                        'Asia/Ho_Chi_Minh'
                    )->setDate($now->year, $now->month, $now->day);
                }
            }

            $isLate = $now->greaterThan($configuredStart);
        }

        // Save NAME into DB (not username)
        $this->checkInRepository->insertCheckIn([
            'user_name' => $user->name, // store full name
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