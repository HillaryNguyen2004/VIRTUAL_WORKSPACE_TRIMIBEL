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

    // public function createRequest(array $data)
    // {
    //     $userId = Auth::id();

    //     if ($this->repo->findByUserAndDate($userId, $data['date'])) {
    //         return ['error' => 'You already made a day-off request for this date.'];
    //     }

    //     $data['user_id'] = $userId;
    //     $data['status'] = 'PENDING';

    //     $this->repo->create($data);

    //     // Notify staff users about the new request
    //     try {
    //         $user = User::find($userId);
    //         if ($user) {
    //             $staffUsers = \App\Models\User::role('staff')->get();
    //             if ($staffUsers->isNotEmpty()) {
    //                 \Illuminate\Support\Facades\Notification::send(
    //                     $staffUsers,
    //                     new \App\Notifications\DayOffRequestCreatedNotification($user->id, $user->name, $data['date'])
    //                 );
    //             }
    //         }
    //     } catch (\Exception $e) {
    //         // Log and continue — notification failure shouldn't block request creation
    //         \Illuminate\Support\Facades\Log::error('DayOff notification error: ' . $e->getMessage());
    //     }

    //     return ['success' => true];
    // }

    public function createRequest(array $data)
    {
        $userId = Auth::id();
        $dates = $data['dates'];

        // Validate dates array
        if (!is_array($dates) || empty($dates)) {
            return ['error' => "Please select at least one date."];
        }

        // Check for existing requests for each date
        foreach ($dates as $date) {
            if ($this->repo->findByUserAndDate($userId, $date)) {
                return ['error' => "You already have a day-off request for {$date}. Please remove overlapping dates."];
            }
        }

        // Create requests for each date
        $createdRequests = [];
        foreach ($dates as $date) {
            $requestData = [
                'user_id' => $userId,
                'date' => $date,
                'leave_type' => $data['leave_type'],
                'reason' => $data['reason'],
                'status' => 'PENDING',
                'request_group_id' => $data['request_group_id'] ?? null,
                'half_day_period' => $data['leave_type'] === 'OFF_HALF' ? $data['half_day_period'] : null,
            ];
            $createdRequests[] = $this->repo->create($requestData);
        }

        // Notify staff users about the new requests
        try {
            $user = User::find($userId);
            if ($user) {
                $staffUsers = \App\Models\User::role('staff')->get();
                if ($staffUsers->isNotEmpty()) {
                    $dateRange = count($dates) > 1 
                        ? "from {$dates[0]} to {$dates[count($dates) - 1]} ({$dates[0]} - {$dates[count($dates) - 1]})"
                        : "on {$dates[0]}";
                        
                    \Illuminate\Support\Facades\Notification::send(
                        $staffUsers,
                        new \App\Notifications\DayOffRequestCreatedNotification(
                            $user->id, 
                            $user->name, 
                            $dateRange
                        )
                    );
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('DayOff notification error: ' . $e->getMessage());
        }

        return ['success' => true, 'count' => count($createdRequests)];
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
