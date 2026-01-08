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

        // Notify staff users about the new request
        try {
            $user = User::find($userId);
            if ($user) {
                $staffUsers = \App\Models\User::role('staff')->get();
                if ($staffUsers->isNotEmpty()) {
                    \Illuminate\Support\Facades\Notification::send(
                        $staffUsers,
                        new \App\Notifications\DayOffRequestCreatedNotification($user->id, $user->name, $data['date'])
                    );
                }
            }
        } catch (\Exception $e) {
            // Log and continue — notification failure shouldn't block request creation
            \Illuminate\Support\Facades\Log::error('DayOff notification error: ' . $e->getMessage());
        }

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
