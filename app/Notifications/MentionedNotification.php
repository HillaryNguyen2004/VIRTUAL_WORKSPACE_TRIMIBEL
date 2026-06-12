<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class MentionedNotification extends Notification
{
    use Queueable;

    protected $message;
    protected $conversationId;
    protected $fromUser;

    public function __construct($message, $conversationId, $fromUser)
    {
        $this->message = $message;
        $this->conversationId = $conversationId;
        $this->fromUser = $fromUser;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'type' => 'mention',
            'conversation_id' => $this->conversationId,
            'message_id' => $this->message->id ?? null,
            'message_content' => $this->message->content ?? null,
            'from_user_id' => $this->fromUser->id ?? null,
            'from_user_name' => $this->fromUser->name ?? null,
            'created_at' => now()->toDateTimeString()
        ];
    }
}
