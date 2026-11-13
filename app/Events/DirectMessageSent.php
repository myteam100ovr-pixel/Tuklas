<?php

namespace App\Events;

use App\Models\DirectMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DirectMessageSent implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public DirectMessage $message) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('users.'.$this->message->sender_id),
            new PrivateChannel('users.'.$this->message->recipient_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    /** @return array{id: int, sender_id: int, body: string, created_at: string} */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'sender_id' => $this->message->sender_id,
            'body' => $this->message->body,
            'created_at' => $this->message->created_at->toIso8601String(),
        ];
    }
}
