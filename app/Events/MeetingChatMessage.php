<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MeetingChatMessage implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $meetingId;
    public array $payload;

    public function __construct(string $meetingId, array $payload)
    {
        $this->meetingId = $meetingId;
        $this->payload = $payload;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('meeting.' . $this->meetingId);
    }

    public function broadcastAs()
    {
        return 'meeting.chat';
    }

    public function broadcastWith()
    {
        return $this->payload;
    }
}