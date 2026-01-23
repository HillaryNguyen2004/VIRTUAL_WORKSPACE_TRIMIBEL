<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/*
    CREATE TABLE phases (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        project_id BIGINT UNSIGNED NOT NULL,
        name VARCHAR(255) NOT NULL,
        start_date DATE NULL,
        due_date DATE NULL,
        created_at TIMESTAMP NULL DEFAULT NULL,
        updated_at TIMESTAMP NULL DEFAULT NULL,

        INDEX phases_project_id_index (project_id),

        CONSTRAINT fk_phasess_project_id
            FOREIGN KEY (project_id)
            REFERENCES projects(id)
            ON DELETE CASCADE
            ON UPDATE CASCADE
    ) ENGINE=InnoDB
    DEFAULT CHARSET=utf8mb4
    COLLATE=utf8mb4_unicode_ci;

 */

class Phase extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'title',
        'start_date',
        'due_date',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }
}
