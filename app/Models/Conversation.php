<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'type', 'created_by'];

    public function participants()
    {
        return $this->belongsToMany(User::class, 'conversation_participants')
                    ->withTimestamps()
                    ->withPivot('last_read_at');
    }

    public function messages()
    {
        return $this->hasMany(Message::class)->with('user');
    }

    public function lastMessage()
    {
        return $this->hasOne(Message::class)->latest();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getDisplayNameAttribute()
    {
        if ($this->type === 'group') {
            return $this->name;
        }
        
        // For direct messages, show the other participant's name
        $otherParticipant = $this->participants
            ->where('id', '!=', auth()->id())
            ->first();
            
        return $otherParticipant ? $otherParticipant->name : 'Unknown User';
    }

    public function getUnreadCountAttribute()
    {
        $lastReadAt = $this->participants()
            ->where('user_id', auth()->id())
            ->first()
            ->pivot
            ->last_read_at;

        return $this->messages()
            ->where('user_id', '!=', auth()->id())
            ->where('created_at', '>', $lastReadAt ?? '1970-01-01')
            ->count();
    }
}
