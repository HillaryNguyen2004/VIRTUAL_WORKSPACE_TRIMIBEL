<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/*
    CREATE TABLE task_read_statuses (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        task_id BIGINT UNSIGNED NOT NULL,
        user_id BIGINT UNSIGNED NOT NULL,
        last_viewed_at TIMESTAMP NULL,

        created_at TIMESTAMP NULL,
        updated_at TIMESTAMP NULL,

        UNIQUE (task_id, user_id),

        FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );

 */

class TaskReadStatus extends Model
{
    protected $fillable = [
        'task_id',
        'user_id',
        'last_viewed_at',
    ];

    protected $casts = [
        'last_viewed_at' => 'datetime',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
