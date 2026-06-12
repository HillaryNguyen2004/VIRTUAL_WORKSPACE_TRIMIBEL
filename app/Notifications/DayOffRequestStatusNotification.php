<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class DayOffRequestStatusNotification extends Notification
{
    use Queueable;

    protected $status;
    protected $date;

    public function __construct($status, $date)
    {
        $this->status = $status;
        $this->date = $date;
    }

    // public function via($notifiable)
    // {
    //     return ['database']; // stored in DB
    // }

    public function toDatabase($notifiable)
    {
        $formatted = $this->date->format('d/m/Y');
        return [
            'status' => $this->status,
            'date'   => $this->date,
            'message' => "Your day-off request on {$formatted} was {$this->status}."
        ];
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toBroadcast($notifiable)
    {
        $formatted = $this->date->format('d/m/Y');
        return new BroadcastMessage([
            'status' => $this->status,
            'date' => $this->date,
            'message' => "Your day-off request on {$formatted} was {$this->status}."
        ]);
    }

}
