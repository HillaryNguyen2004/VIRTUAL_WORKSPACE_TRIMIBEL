<?php
namespace App\Services;

use App\Repositories\CheckInRepositoryInterface;
use Illuminate\Support\Carbon;

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
}
