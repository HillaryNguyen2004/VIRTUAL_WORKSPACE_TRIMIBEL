<?php
namespace App\Services;

use App\Repositories\CheckInRepositoryInterface;
use Illuminate\Support\Carbon;
use App\Models\DayOffRequest;
use App\Models\User;
use App\Models\CompanyHour;

class CheckInService
{
    protected $checkInRepository;

    public function __construct(CheckInRepositoryInterface $checkInRepository)
    {
        $this->checkInRepository = $checkInRepository;
    }

    public function processCheckOut(string $username): array
    {
        $user = User::where('username', $username)->first();

        if (!$user) {
            return [
                'status' => false,
                'message' => __('messages.invalid_user'),
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

    public function processCheckIn(string $username): array
    {
        $user = User::where('username', $username)->first();

        if (!$user) {
            return [
                'status' => false,
                'message' => __('messages.invalid_user'),
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

            if ($dayOff && $dayOff->leave_type === 'OFF_HALF') {
                $configuredStart->setTime(12, 0, 0);
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