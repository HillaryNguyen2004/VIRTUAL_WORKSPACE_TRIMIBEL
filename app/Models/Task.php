<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $table = 'tasks';
    protected $primaryKey = 'task_id';
    public $timestamps = false; 
    protected $fillable = [
        'title', 'description', 'assigned_user_id', 'status', 'due_date', 'active'
    ];
    public function assigneeUser()
    {
        return $this->belongsTo(\App\Models\User::class, 'assigned_user_id');
    }

    public function assignedUsers()
{
    // return $this->belongsToMany(User::class, 'task_user', 'task_id', 'user_id', 'task_id', 'id')->withTimestamps();
    return $this->belongsToMany(User::class, 'task_user', 'task_id', 'user_id');
}
}