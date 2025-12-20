<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $table = 'tasks';
    protected $primaryKey = 'task_id';
    public $timestamps = false; 
    protected $fillable = [
        'title', 'description', 'assigned_user_id', 'status', 'due_date', 'active','project_id'
    ];
    public function assigneeUser()
    {
        return $this->belongsTo(\App\Models\User::class, 'assigned_user_id');
    }
     public function project()
    {
        return $this->belongsTo(Project::class);
    }


    public function assignedUsers()
{
    // return $this->belongsToMany(User::class, 'task_user', 'task_id', 'user_id', 'task_id', 'id')->withTimestamps();
    return $this->belongsToMany(User::class, 'task_user', 'task_id', 'user_id');
}

public function users()
{
    return $this->belongsToMany(User::class, 'task_user', 'task_id', 'user_id');
}


// public function getPercentageAttribute($value)
//     {
//         return $value ?? 0;
//     }

//     // Check if task is overdue
//     public function getIsOverdueAttribute()
//     {
//         return $this->due_date && 
//                $this->due_date < now() && 
//                $this->status !== 'completed';
//     }

}