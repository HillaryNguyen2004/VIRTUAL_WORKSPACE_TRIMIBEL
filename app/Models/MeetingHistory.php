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
        'recording_url',
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

    public function transcriptions()
    {
        // 1st arg: The related model
        // 2nd arg: The foreign key on the 'meeting_transcriptions' table
        // 3rd arg: The local key on the 'meeting_histories' table
        return $this->hasMany(MeetingTranscription::class, 'meeting_id', 'meeting_id');
    }
}
