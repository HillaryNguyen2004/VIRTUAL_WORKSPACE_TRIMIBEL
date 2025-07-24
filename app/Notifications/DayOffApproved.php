<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage; // optional
use Illuminate\Notifications\Messages\DatabaseMessage;

class DayOffApproved extends Notification
{
    use Queueable;

    protected $date;

    public function __construct($date)
    {
        $this->date = $date;
    }

    public function via($notifiable)
    {
        return []; // or ['mail', 'database']
    }

    public function toDatabase($notifiable)
    {
        return [
            'message' => "Your day-off request for {$this->date} was approved.",
        ];
    }

    // Optional: Email message
    // public function toMail($notifiable)
    // {
    //     return (new MailMessage)
    //                 ->subject('Day Off Approved')
    //                 ->line("Your day-off request for {$this->date} has been approved.")
    //                 ->action('View Dashboard', url('/dashboard'));
    // }
}

