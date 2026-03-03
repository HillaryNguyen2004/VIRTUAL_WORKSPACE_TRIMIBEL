<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsAppConversation extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_conversations';

    protected $fillable = [
        'conversation_id',
        'whatsapp_customer_id',
        'platform_conversation_id',
        'is_open',
        'opened_at',
        'closed_at',
        'last_message_at',
        'service_window_status',
        'service_window_opened_at'
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'last_message_at' => 'datetime',
        'service_window_opened_at' => 'datetime',
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function customer()
    {
        return $this->belongsTo(WhatsAppCustomer::class, 'whatsapp_customer_id');
    }

    /**
     * Check if service window is currently open (24 hours from last customer message)
     */
    public function isServiceWindowOpen(): bool
    {
        if ($this->service_window_opened_at === null) {
            return false;
        }
        
        // Service window is 24 hours
        return $this->service_window_opened_at->addHours(24)->isFuture();
    }

    /**
     * Open the service window (customer sent a message)
     */
    public function openServiceWindow()
    {
        $this->service_window_status = 'open';
        $this->service_window_opened_at = now();
        $this->save();
    }

    /**
     * Close the service window
     */
    public function closeServiceWindow()
    {
        $this->service_window_status = 'closed';
        $this->save();
    }

    /**
     * Can send free-form reply?
     */
    public function canReplyFreeForm(): bool
    {
        return $this->isServiceWindowOpen();
    }

    /**
     * Does need template to send?
     */
    public function needsTemplateToSend(): bool
    {
        return !$this->isServiceWindowOpen();
    }
}
