<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\TaskReadStatus;

/*
    ALTER TABLE tasks
    ADD COLUMN percentage INT(11) NOT NULL DEFAULT 0 AFTER active,
    ADD COLUMN estimated_time FLOAT NULL DEFAULT NULL AFTER percentage,
    ADD COLUMN score INT(11) NULL DEFAULT 0 AFTER estimated_time,
    ADD COLUMN phase_id BIGINT(20) NULL DEFAULT NULL AFTER project_id,
    ADD COLUMN parent_id BIGINT(20) NULL DEFAULT NULL AFTER phase_id;

    ALTER TABLE tasks
    ADD INDEX tasks_project_id_index (project_id),
    ADD CONSTRAINT fk_tasks_project_id
        FOREIGN KEY (project_id) REFERENCES projects(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE;

    ALTER TABLE tasks
    ADD INDEX tasks_phase_id_index (phase_id),
    ADD CONSTRAINT fk_tasks_phase_id
        FOREIGN KEY (phase_id) REFERENCES phases(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE;
 */

class Task extends Model
{
    protected $table = 'tasks';
    protected $primaryKey = 'id';
    protected $fillable = [
        'title',
        'description',
        'assigned_user_id',
        'status',
        'priority',
        'start_date',
        'due_date',
        'active',
        'project_id',
        'parent_id',
        'phase_id',
        'percentage',
        'estimated_time',
        'score',
    ];

    protected $casts = [
        'start_date' => 'date',
        'due_date' => 'date',
        'active' => 'boolean',
    ];
    public function assigneeUser()
    {
        return $this->belongsTo(\App\Models\User::class, 'assigned_user_id');
    }
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function parentTask()
    {
        return $this->belongsTo(Task::class, 'parent_id');
    }

    public function subTasks()
    {
        return $this->hasMany(Task::class, 'parent_id');
    }

    public function phase()
    {
        return $this->belongsTo(Phase::class);
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

    public function recalculateCompletion(): void
    {
        $subTasks = $this->subTasks()->where('active', 1)->get();

        if ($subTasks->isEmpty()) {
            $this->update(['percentage' => 0]);
            return;
        }

        $average = round($subTasks->avg('percentage'));

        $this->update([
            'percentage' => $average
        ]);
    }

    public function readStatuses()
    {
        return $this->hasMany(TaskReadStatus::class);
    }

    public function isUnread(?int $userId = null): bool
    {
        $userId = $userId ?: auth()->id();

        if (!$userId) {
            return false;
        }

        // Use the collection if already loaded, otherwise use relationship query
        $readStatus = $this->relationLoaded('readStatuses')
            ? $this->readStatuses->where('user_id', $userId)->first()
            : $this->readStatuses()->where('user_id', $userId)->first();

        if (!$readStatus) {
            return true;
        }

        return $readStatus->last_viewed_at === null || $readStatus->last_viewed_at->lt($this->updated_at);
    }

    public function markAsRead(?int $userId = null): void
    {
        $userId = $userId ?: auth()->id();

        if (!$userId) {
            return;
        }

        TaskReadStatus::updateOrCreate(
            ['task_id' => $this->id, 'user_id' => $userId],
            ['last_viewed_at' => now()]
        );
    }
}