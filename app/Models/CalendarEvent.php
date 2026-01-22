<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CalendarEvent extends Model
{
    use HasFactory;

    protected $table = 'calendar_events';

    protected $fillable = [
        'user_id',
        'title',
        'start_date',
        'end_date',
        'category',
        'description',
        'meeting_id',
        'recurrence_type',
        'recurrence_interval',
        'recurrence_end_date',
        'recurrence_count',
    ];

    // Ensures Carbon treats these as Date objects
    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'recurrence_end_date' => 'date',
    ];

    // Optional: Relationship back to User
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}