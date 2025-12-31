<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class DayOffRequestCreatedNotification extends Notification
{
    use Queueable;

    protected $requesterId;
    protected $requesterName;
    protected $date;

    public function __construct($requesterId, $requesterName, $date)
    {
        $this->requesterId = $requesterId;
        $this->requesterName = $requesterName;
        $this->date = $date;
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'requester_id' => $this->requesterId,
            'requester_name' => $this->requesterName,
            'date' => $this->date,
            'message' => "{$this->requesterName} requested day off on {$this->date}"
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'requester_id' => $this->requesterId,
            'requester_name' => $this->requesterName,
            'date' => $this->date,
            'message' => "{$this->requesterName} requested day off on {$this->date}"
        ]);
    }
}
