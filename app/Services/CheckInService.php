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
                'message' => 'You have not checked in today.',
            ];
        }

        if ($checkIn->check_out_time !== null) {
            return [
                'status' => false,
                'message' => 'You have already checked out today.',
            ];
        }

        $this->checkInRepository->updateCheckOut($checkIn->id, Carbon::now('Asia/Ho_Chi_Minh')->toTimeString());

        return [
            'status' => true,
            'message' => 'Checked out successfully.',
        ];
    }

    public function processCheckIn(string $username): array
    {
        $user = User::where('name', $username)->first();

        if (!$user) {
            return [
                'status' => false,
                'message' => 'Invalid user',
            ];
        }

        $token = $user->createToken('check-in-token')->plainTextToken;

        $now = Carbon::now('Asia/Ho_Chi_Minh');
        $today = $now->toDateString();

        // Check if already checked in today
        if ($this->checkInRepository->hasCheckedInToday($user->name, $today)) {
            return [
                'status' => false,
                'message' => 'You have already checked in today.',
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
            'message' => 'Checked in successfully',
            'token' => $token,
        ];
    }

}
