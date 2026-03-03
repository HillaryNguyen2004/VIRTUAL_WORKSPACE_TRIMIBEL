<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsAppCustomer extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_customers';

    protected $fillable = [
        'phone',
        'wa_id',
        'display_name',
        'name',
        'notes',
        'tags',
        'stage',
        'assigned_to_user_id',
        'last_contact_at',
        'next_follow_up_at'
    ];

    protected $casts = [
        'tags' => 'array',
        'last_contact_at' => 'datetime',
        'next_follow_up_at' => 'datetime',
    ];

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function conversations()
    {
        return $this->hasMany(WhatsAppConversation::class);
    }

    public function followUps()
    {
        return $this->hasMany(WhatsAppFollowUp::class);
    }

    public function pendingFollowUps()
    {
        return $this->followUps()->where('status', 'pending')->where('scheduled_at', '<=', now());
    }

    /**
     * Scope: Get overdue follow-ups
     */
    public function scopeWithOverdueFollowUps($query)
    {
        return $query->whereHas('followUps', function ($q) {
            $q->where('status', 'pending')->where('scheduled_at', '<', now());
        });
    }

    /**
     * Scope: Get leads stuck in a stage
     */
    public function scopeStuckInStage($query, $stage, $days = 3)
    {
        return $query->where('stage', $stage)
            ->where('updated_at', '<', now()->subDays($days));
    }

    public function getFullNameAttribute()
    {
        return $this->name ?? $this->display_name ?? $this->phone;
    }

    public function getStatusBadgeAttribute()
    {
        return match($this->stage) {
            'new' => 'primary',
            'thinking' => 'info',
            'quoted' => 'warning',
            'made_up_mind' => 'secondary',
            'won' => 'success',
            'come_back' => 'danger',
            'lost' => 'dark',
        };
    }
}
