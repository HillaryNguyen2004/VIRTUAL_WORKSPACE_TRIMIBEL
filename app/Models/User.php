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

// public function users()
//     {
//         return $this->morphedByMany(User::class, 'model', 'model_has_roles', 'role_id', 'model_id');
//     }

}
