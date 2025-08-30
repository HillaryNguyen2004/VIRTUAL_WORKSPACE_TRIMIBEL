<?php
namespace App\Services;
use App\Models\User;
use App\Notifications\DayOffRequestStatusNotification;
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

    // public function approveRequest($id)
    // {
    //     return $this->repo->updateStatus($id, 'APPROVED', Auth::id());
    // }

    // public function rejectRequest($id)
    // {
    //     return $this->repo->updateStatus($id, 'REJECTED', Auth::id());
    // }


public function approveRequest($id)
{
    $request = $this->repo->updateStatus($id, 'APPROVED', Auth::id());

    if ($request && $request->user) {
        $request->user->notify(new DayOffRequestStatusNotification('APPROVED', $request->date));
    }

    return $request;
}

public function rejectRequest($id)
{
    $request = $this->repo->updateStatus($id, 'REJECTED', Auth::id());

    if ($request && $request->user) {
        $request->user->notify(new DayOffRequestStatusNotification('REJECTED', $request->date));
    }

    return $request;
}

}
