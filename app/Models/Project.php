<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/*
    ALTER TABLE projects
    RENAME COLUMN progress TO percentage
 */

class Project extends Model
{
    protected $fillable = ['title', 'description', 'staff_id', 'status', 'percentage', 'start_date', 'due_date'];

    public function phases()
    {
        return $this->hasMany(Phase::class)->orderBy('start_date');
    }

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
        $tasks = $this->tasks()
            ->where('active', 1)
            ->whereNull('parent_id')
            ->get();

        if ($tasks->isEmpty()) {
            $this->update(['percentage' => 0]);
            return;
        }

        $average = round($tasks->avg('percentage'));

        $this->update([
            'percentage' => $average
        ]);
    }

    public function teamMembers()
    {
        return User::whereHas('tasks', function ($query) {
            $query->where('project_id', $this->id);
        })->distinct()->get();
    }

}

