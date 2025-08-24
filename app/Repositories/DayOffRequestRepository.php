<?php
namespace App\Repositories;
use App\Models\DayOffRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class DayOffRequestRepository
{
    protected $model;

    public function __construct(DayOffRequest $model)
    {
        $this->model = $model;
    }

    public function findByUserAndDate($userId, $date)
    {
        return DayOffRequest::where('user_id', $userId)
            ->where('date', $date)
            ->first();
    }

    public function create(array $data)
    {
        return DayOffRequest::create($data);
    }

    public function getPendingWithUsers()
    {
        return DayOffRequest::where('status', 'PENDING')->with('user')->get();
    }

    public function find($id)
    {
        return DayOffRequest::findOrFail($id);
    }

    public function updateStatus($id, $status, $reviewerId)
    {
        $request = $this->find($id);
        $request->update([
            'status' => $status,
            'reviewed_by' => $reviewerId,
        ]);

        return $request;
    }

    // public function getApprovedFullDayOffs(array $filters): Collection
    // {
    //     return DB::table('day_off_requests')
    //         ->join('users', 'day_off_requests.user_id', '=', 'users.id')
    //         ->where('day_off_requests.status', 'APPROVED')
    //         ->where('day_off_requests.leave_type', '!=', 'OFF_HALF')
    //         ->when($filters['search'] ?? null, function ($query, $search) {
    //             $query->where(function ($q) use ($search) {
    //                 $q->where('users.name', 'like', "%{$search}%")
    //                   ->orWhere('day_off_requests.date', 'like', "%{$search}%");
    //             });
    //         })
    //         ->select([
    //             DB::raw('CONCAT("D-", day_off_requests.id) as id'),
    //             DB::raw('users.name as user_name'),
    //             DB::raw('"Day Off Approved" as action'),
    //             DB::raw('day_off_requests.updated_at as created_at'),
    //             DB::raw('CONCAT("Full day off approved for ", DATE_FORMAT(day_off_requests.date, "%Y-%m-%d")) as description'),
    //         ])
    //         ->get();
    // }
    public function getApprovedFullDayOffs(array $filters): Collection
    {
        return $this->model
            ->with('user')
            ->where('status', 'APPROVED')
            ->where('leave_type', '!=', 'OFF_HALF')
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->whereHas('user', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                })->orWhere('date', 'like', "%{$search}%");
            })
            ->get()
            ->map(function ($request) {
                return (object)[
                    'id' => 'D-' . $request->id,
                    'user_name' => $request->user->name,
                    'action' => 'Day Off Approved',
                    'created_at' => $request->updated_at,
                    'description' => 'Full day off approved for ' . $request->date->format('Y-m-d'),
                ];
            });
    }
}
