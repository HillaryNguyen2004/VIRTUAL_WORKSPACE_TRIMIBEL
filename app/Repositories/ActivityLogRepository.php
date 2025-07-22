<?php
namespace App\Repositories;

use App\Models\ActivityLog;
use Illuminate\Support\Collection;

class ActivityLogRepository
{
    public function getRecentLogs(int $limit = 3): Collection
    {
        return ActivityLog::latest()
            ->with('user')
            ->take($limit)
            ->get();
    }

    public function getFilteredLogs(array $filters): Collection
    {
        return ActivityLog::with('user')
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('action', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhereHas('user', function ($q) use ($search) {
                          $q->where('name', 'like', "%{$search}%");
                      });
                });
            })
            ->when(!empty($filters['action']), function ($query) use ($filters) {
                $query->where('action', $filters['action']);
            })
            ->get()
            ->map(function ($log) {
                return (object)[
                    'id' => $log->id,
                    'user_name' => $log->user->name ?? 'N/A',
                    'action' => $log->action,
                    'created_at' => $log->created_at,
                    'description' => $log->description,
                ];
            });
    }

    public function getDistinctActions()
    {
        return ActivityLog::select('action')->distinct()->pluck('action');
    }
}
