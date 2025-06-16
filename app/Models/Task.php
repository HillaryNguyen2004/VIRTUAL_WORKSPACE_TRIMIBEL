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
}