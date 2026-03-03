<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsAppFollowUp extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_follow_ups';

    protected $fillable = [
        'whatsapp_customer_id',
        'assigned_to_user_id',
        'scheduled_at',
        'reason',
        'notes',
        'status',
        'completed_at',
        'completed_by_user_id'
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(WhatsAppCustomer::class, 'whatsapp_customer_id');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function completedBy()
    {
        return $this->belongsTo(User::class, 'completed_by_user_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeDueToday($query)
    {
        return $query->pending()
            ->whereDate('scheduled_at', today());
    }

    public function scopeOverdue($query)
    {
        return $query->pending()
            ->where('scheduled_at', '<', now());
    }

    public function scopeAssignedToUser($query, $userId)
    {
        return $query->where('assigned_to_user_id', $userId);
    }

    /**
     * Mark as completed
     */
    public function markCompleted($userId = null)
    {
        $this->status = 'completed';
        $this->completed_at = now();
        $this->completed_by_user_id = $userId ?? auth()->id();
        $this->save();
    }

    /**
     * Reschedule to a later time
     */
    public function reschedule(\DateTime $newTime)
    {
        $this->scheduled_at = $newTime;
        $this->status = 'rescheduled';
        $this->save();

        // Create a new follow-up for the new time
        return self::create([
            'whatsapp_customer_id' => $this->whatsapp_customer_id,
            'assigned_to_user_id' => $this->assigned_to_user_id,
            'scheduled_at' => $newTime,
            'reason' => $this->reason,
            'notes' => $this->notes,
            'status' => 'pending'
        ]);
    }

    public function isOverdue(): bool
    {
        return $this->status === 'pending' && $this->scheduled_at < now();
    }

    public function isDueToday(): bool
    {
        return $this->status === 'pending' && $this->scheduled_at->isToday();
    }
}
