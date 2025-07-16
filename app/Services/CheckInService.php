<?php
namespace App\Services;

use App\Repositories\CheckInRepositoryInterface;
use Illuminate\Support\Carbon;

use App\Models\User;
use App\Models\CompanyHour;

class CheckInService
{
    protected $checkInRepository;

    public function __construct(CheckInRepositoryInterface $checkInRepository)
    {
        $this->checkInRepository = $checkInRepository;
    }

    public function processCheckOut(string $userName): array
    {
        $checkIn = $this->checkInRepository->getTodayCheckIn($userName);

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

        $this->checkInRepository->updateCheckOut($checkIn->id, Carbon::now('Asia/Ho_Chi_Minh')->toTimeString());

        return [
            'status' => true,
            'message' => __('messages.check_out_success'),
        ];
    }

    public function processCheckIn(string $username): array
    {
        $user = User::where('name', $username)->first();

        if (!$user) {
            return [
                'status' => false,
                'message' => __('messages.invalid_user'),
            ];
        }

        $token = $user->createToken('check-in-token')->plainTextToken;

        $now = Carbon::now('Asia/Ho_Chi_Minh');
        $today = $now->toDateString();

        // Check if already checked in today
        if ($this->checkInRepository->hasCheckedInToday($user->name, $today)) {
            return [
                'status' => false,
                'message' => __('messages.already_checked_in'),
            ];
        }

        $isLate = false;
        $workingHour = CompanyHour::first();

        if ($workingHour) {
            $configuredStart = Carbon::createFromFormat('H:i:s', $workingHour->start_at, 'Asia/Ho_Chi_Minh')
                ->setDate($now->year, $now->month, $now->day);

            $isLate = $now->greaterThan($configuredStart);
        }

        // Insert check-in
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
