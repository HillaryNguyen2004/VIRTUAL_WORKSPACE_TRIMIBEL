<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

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
            'date'        => now()->format('Y-m-d H:i:s'),
        ];
    }

    // Ensure DB driver can use this too
    public function toArray($notifiable)
    {
        return $this->toDatabase($notifiable);
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'task_id'     => $this->taskId,
            'task_name'   => $this->taskName,
            'assigned_by' => $this->assignedBy,
            'message'     => "You have been assigned to task '{$this->taskName}' by {$this->assignedBy}.",
            'date'        => now()->format('Y-m-d H:i:s'),
        ]);
    }

    // <-- removed broadcastOn() entirely (use Laravel default private channel)
}
