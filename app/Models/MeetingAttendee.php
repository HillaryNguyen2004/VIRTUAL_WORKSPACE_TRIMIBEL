<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeetingAttendee extends Model
{
    protected $fillable = [
        'meeting_id',
        'user_id',
        'name',
        'avatar_url',
        'joined_at',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
    ];
}
