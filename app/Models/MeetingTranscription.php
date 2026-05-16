<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MeetingTranscription extends Model
{
    protected $fillable = ['meeting_id', 'user_id', 'speaker_tag', 'text'];
    
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
