<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MeetingHistory extends Model
{
    protected $fillable = [
        'user_id',
        'meeting_id',
        'start_time',
        'end_time',
        'notes',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    public function attendees(): HasMany
    {
        return $this->hasMany(MeetingAttendee::class, 'meeting_id', 'meeting_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
