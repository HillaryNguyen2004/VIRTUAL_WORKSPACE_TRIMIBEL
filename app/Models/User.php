<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Spatie\Permission\Traits\HasRoles;

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
        'username'
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
