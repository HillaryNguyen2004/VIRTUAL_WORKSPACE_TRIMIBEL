<?php
namespace App\Services;

use App\Repositories\DayOffRequestRepository;
use Illuminate\Support\Facades\Auth;

class DayOffService
{
    protected $repo;

    public function __construct(DayOffRequestRepository $repo)
    {
        $this->repo = $repo;
    }

    public function createRequest(array $data)
    {
        $userId = Auth::id();

        if ($this->repo->findByUserAndDate($userId, $data['date'])) {
            return ['error' => 'You already made a day-off request for this date.'];
        }

        $data['user_id'] = $userId;
        $data['status'] = 'PENDING';

        $this->repo->create($data);

        return ['success' => true];
    }

    public function getPendingRequests()
    {
        return $this->repo->getPendingWithUsers();
    }

    public function approveRequest($id)
    {
        return $this->repo->updateStatus($id, 'APPROVED', Auth::id());
    }

    public function rejectRequest($id)
    {
        return $this->repo->updateStatus($id, 'REJECTED', Auth::id());
    }
}
