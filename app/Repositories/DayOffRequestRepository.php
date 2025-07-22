<?php
namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class DayOffRequestRepository
{
    public function getApprovedFullDayOffs(array $filters): Collection
    {
        return DB::table('day_off_requests')
            ->join('users', 'day_off_requests.user_id', '=', 'users.id')
            ->where('day_off_requests.status', 'APPROVED')
            ->where('day_off_requests.leave_type', '!=', 'OFF_HALF')
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('users.name', 'like', "%{$search}%")
                      ->orWhere('day_off_requests.date', 'like', "%{$search}%");
                });
            })
            ->select([
                DB::raw('CONCAT("D-", day_off_requests.id) as id'),
                DB::raw('users.name as user_name'),
                DB::raw('"Day Off Approved" as action'),
                DB::raw('day_off_requests.updated_at as created_at'),
                DB::raw('CONCAT("Full day off approved for ", DATE_FORMAT(day_off_requests.date, "%Y-%m-%d")) as description'),
            ])
            ->get();
    }
}
