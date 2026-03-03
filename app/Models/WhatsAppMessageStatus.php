<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsAppMessageStatus extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_message_statuses';

    protected $fillable = [
        'message_id',
        'platform_message_id',
        'status',
        'delivered_at',
        'read_at',
        'error_message'
    ];

    protected $casts = [
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    public function message()
    {
        return $this->belongsTo(Message::class);
    }

    /**
     * Update status from webhook
     */
    public function updateFromWebhook(string $status, ?string $timestamp = null)
    {
        $this->status = $status;

        if ($status === 'delivered' && $timestamp) {
            $this->delivered_at = \Carbon\Carbon::createFromTimestamp($timestamp);
        } elseif ($status === 'read' && $timestamp) {
            $this->read_at = \Carbon\Carbon::createFromTimestamp($timestamp);
            $this->delivered_at = $this->delivered_at ?? now();
        }

        $this->save();
    }
}
