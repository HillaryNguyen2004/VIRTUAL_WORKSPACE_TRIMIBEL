<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Support\Str;

class TaskAssignedNotification extends Notification
{
    use Queueable;

    protected $taskId;
    protected $taskName;
    protected $assignedBy;

    public function __construct($taskId, $taskName, $assignedBy)
    {
        $this->taskId = $taskId;
        $this->taskName = $taskName;
        $this->assignedBy = $assignedBy;
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'task_id'     => $this->taskId,
            'task_name'   => $this->taskName,
            'assigned_by' => $this->assignedBy,
            'message'     => "You have been assigned to task '{$this->taskName}' by {$this->assignedBy}.",
            'date'        => now()->toDateTimeString(),
        ];
    }

    // Ensure DB driver can use this too
    public function toArray($notifiable)
    {
        return $this->toDatabase($notifiable);
    }

    public function toBroadcast($notifiable)
    {
        // Broadcast payload — Laravel will use default private channel for the notifiable.
        return new BroadcastMessage($this->toDatabase($notifiable) + ['id' => (string) Str::uuid()]);
    }

    // <-- removed broadcastOn() entirely (use Laravel default private channel)
}
