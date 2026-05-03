<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasRoles;
    use HasApiTokens, HasFactory, Notifiable;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'department_id',
        'username',
        'google_email',
        'google_access_token',
        'google_refresh_token',
        'is_google_connected',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function incrementLoginAttempts()
    {
        $this->login_attempts += 1;
        if ($this->login_attempts >= 5) {
            $this->blocked = true;
        }
        $this->save();
    }

    public function resetLoginAttempts()
    {
        $this->login_attempts = 0;
        $this->save();
    }

    public function isBlocked()
    {
        return $this->blocked;
    }

    public function assignedTasks()
    {
        // return $this->belongsToMany(Task::class, 'task_user', 'user_id', 'task_id', 'id', 'task_id')->withTimestamps();
        return $this->belongsToMany(Task::class, 'task_user', 'user_id', 'task_id')->withTimestamps();
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function teamLeader()
    {
        return $this->belongsTo(User::class, 'team_leader_id');
    }

    public function teamMembers()
    {
        return $this->hasMany(User::class, 'team_leader_id');
    }

    public function dayOffRequests()
    {
        return $this->hasMany(\App\Models\DayOffRequest::class);
    }

    public function checkIns()
    {
        return $this->hasMany(CheckIn::class, 'user_name', 'name');
    }

    public function hasHalfDayOffOn($date)
    {
        return $this->dayOffRequests
            ->where('date', $date)
            ->where('leave_type', 'OFF_HALF')
            ->where('status', 'APPROVED')
            ->isNotEmpty();
    }

    public function hasFullDayOffOn($date)
    {
        return $this->dayOffRequests
            ->where('date', $date)
            ->where('leave_type', 'OFF_FULL')
            ->where('status', 'APPROVED')
            ->isNotEmpty();
    }

    public function conversations()
    {
        return $this->belongsToMany(Conversation::class, 'conversation_participants')
            ->withTimestamps()
            ->withPivot('last_read_at');
    }

    // public function users()
//     {
//         return $this->morphedByMany(User::class, 'model', 'model_has_roles', 'role_id', 'model_id');
//     }

    // Check if user is a team leader
    public function isTeamLeader()
    {
        return is_null($this->team_leader_id) ||
            User::where('team_leader_id', $this->id)->exists();
    }

    public function tasks()
    {
        return $this->belongsToMany(Task::class, 'task_user')
            ->withTimestamps();
    }

    public function ownedDocuments()
    {
        return $this->hasMany(Document::class, 'owner_id');
    }

    public function sharedDocuments()
    {
        return $this->belongsToMany(Document::class, 'document_shares')
            ->withPivot('permission')
            ->withTimestamps();
    }

    public function sharedFolders()
    {
        return $this->belongsToMany(PersonalFolder::class, 'personal_folder_shares', 'user_id', 'folder_id')
            ->withPivot(['permission', 'shared_by'])
            ->withTimestamps();
    }

    public function delegatablePermissionNames(): array
    {
        return $this->getAllPermissions()->pluck('name')->values()->all();
    }

    // public function syncDepartmentAddonPermissions(): void
    // {
    //     // Admin should not be touched
    //     if ($this->hasRole('admin'))
    //         return;

    //     // Remove previous department permissions:
    //     // easiest approach: recompute full "department addon set" and sync ONLY that addon set
    //     // BUT we don't want to destroy user's own custom direct perms (if you have).
    //     // So we tag department perms by naming convention or store in a separate table.
    //     // For now: simplest method -> treat ALL direct perms as "department addon"
    //     // If you want per-user extra perms later, see STEP 9.

    //     $deptPermNames = [];

    //     if ($this->department_id) {
    //         $deptPermNames = Permission::query()
    //             ->whereIn('id', $this->department->permissions()->pluck('permissions.id'))
    //             ->pluck('name')
    //             ->toArray();
    //     }
    //     // 2) Direct permissions user already has (important for subadmin)
    //     $currentDirectPermissionNames = $this->permissions
    //         ->pluck('name')
    //         ->toArray();

    //     // 3) Merge (don't overwrite subadmin perms)
    //     $merged = array_values(array_unique(array_merge(
    //         $currentDirectPermissionNames,
    //         $deptPermissionNames
    //     )));

    //     // Direct permissions = department addon
    //     $this->syncPermissions($deptPermNames);
    // }

    // public function syncDepartmentAddonPermissions(): void
    // {
    //     // Admin should not be touched (admin bypasses all anyway)
    //     if ($this->hasRole('admin')) {
    //         return;
    //     }

    //     // If user has no department, do nothing (or you can clear dept perms if you want)
    //     if (!$this->department_id || !$this->department) {
    //         return;
    //     }

    //     // 1) Get department permission NAMES
    //     $deptPermNames = $this->department
    //         ->permissions()
    //         ->pluck('name')
    //         ->toArray();

    //     // 2) Get user's current DIRECT permissions (important for subadmin custom perms)
    //     $currentDirectPermissionNames = $this->permissions
    //         ->pluck('name')
    //         ->toArray();

    //     // 3) Merge (keep subadmin direct perms + add department perms)
    //     $merged = array_values(array_unique(array_merge(
    //         $currentDirectPermissionNames,
    //         $deptPermNames
    //     )));

    //     // 4) Apply merged list as direct permissions
    //     $this->syncPermissions($merged);

    //     // 5) Clear Spatie cache (important)
    //     app(PermissionRegistrar::class)->forgetCachedPermissions();
    // }


    public function syncDepartmentAddonPermissions(): void
    {
        if ($this->hasRole('admin'))
            return;

        if (!$this->department_id || !$this->department)
            return;

        $deptPermNames = $this->department->permissions()->pluck('name')->toArray();

        // subadmin keeps direct perms + dept perms
        if ($this->hasRole('subadmin')) {
            $direct = $this->permissions->pluck('name')->toArray();
            $merged = array_values(array_unique(array_merge($direct, $deptPermNames)));
            $this->syncPermissions($merged);
        } else {
            // staff/user/substaff: department is the source of truth
            $this->syncPermissions($deptPermNames);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function hasDepartmentRolePermission(string $permissionName): bool
    {
        // Admin always allowed
        if ($this->hasRole('admin')) {
            return true;
        }

        // Subadmin uses Spatie direct/role permissions
        if ($this->hasRole('subadmin') || $this->hasRole('substaff')) {
            return $this->can($permissionName);
        }

        // Must have a department for department-based checks
        if (!$this->department_id) {
            return false;
        }

        // Get all role IDs user has (Spatie)
        $roleIds = $this->roles->pluck('id')->all();
        if (empty($roleIds)) {
            $roleIds = $this->roles()->pluck('roles.id')->all(); // make explicit
        }

        if (empty($roleIds)) {
            return false;
        }

        return DB::table('department_role_permissions as drp')
            ->join('permissions as p', 'p.id', '=', 'drp.permission_id')
            ->where('drp.department_id', $this->department_id)
            ->whereIn('drp.role_id', $roleIds)
            ->where('p.name', $permissionName)
            ->exists();
    }


    // Accessors for progress tracking
//     public function getTotalTasksCountAttribute()
//     {
//         // Combine both assigned tasks and direct tasks
//         return $this->assignedTasks->count() + $this->tasks->count();
//     }

    //     public function getCompletedTasksCountAttribute()
//     {
//         $assignedCompleted = $this->assignedTasks->where('status', 'completed')->count();
//         $directCompleted = $this->tasks->where('status', 'completed')->count();

    //         return $assignedCompleted + $directCompleted;
//     }

    //     public function getCompletionRateAttribute()
//     {
//         $total = $this->total_tasks_count;
//         if ($total === 0) return 0;

    //         return round(($this->completed_tasks_count / $total) * 100, 1);
//     }

    //     public function getStatusAttribute()
//     {
//         $tasks = $this->assignedTasks->merge($this->tasks);

    //         if ($tasks->isEmpty()) {
//             return 'inactive';
//         }

    //         $completedTasks = $tasks->where('status', 'completed')->count();
//         $totalTasks = $tasks->count();
//         $completionRate = ($completedTasks / $totalTasks) * 100;

    //         // Check for overdue tasks
//         $overdueTasks = $tasks->filter(function ($task) {
//             return $task->due_date && 
//                    $task->due_date < now() && 
//                    $task->status !== 'completed';
//         });

    //         if ($overdueTasks->count() > 0) {
//             return 'overdue';
//         }

    //         if ($completionRate >= 80) {
//             return 'active';
//         } elseif ($completionRate >= 50) {
//             return 'busy';
//         } else {
//             return 'needs_help';
//         }
//     }

    // // ...existing code...
// public function tasks()
// {
//     // If your tasks table has `user_id` as the owner/creator FK:
//     return $this->hasMany(\App\Models\Task::class, 'user_id');

    //     // If your tasks table uses a different column (example: created_by):
//     // return $this->hasMany(\App\Models\Task::class, 'created_by');

    //     // If tasks are related via a pivot (already have assignedTasks), do not duplicate.
// }
// ...existing code...
}
