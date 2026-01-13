<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = ['title', 'description', 'staff_id', 'status', 'progress', 'start_date', 'due_date'];

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    // public function owner()
    // {
    //     return $this->belongsTo(User::class, 'owner_id');
    // }

    public function staffUser()
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    public function recalculateCompletion(): void
    {
        $tasks = $this->tasks()->where('active', 1)->get();

        if ($tasks->isEmpty()) {
            $this->update(['progress' => 0]);
            return;
        }

        $average = round($tasks->avg('percentage'));

        $this->update([
            'progress' => $average
        ]);
    }

    public function teamMembers()
    {
        return User::whereHas('tasks', function($query) {
            $query->where('project_id', $this->id);
        })->distinct()->get();
    }

}

