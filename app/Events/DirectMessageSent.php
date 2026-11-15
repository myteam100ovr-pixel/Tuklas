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

    /** @return array{id: int, sender_id: int, recipient_id: int, body: string, attachments: list<array{index: int, name: string, mime: string, size: int}>, created_at: string} */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'sender_id' => $this->message->sender_id,
            'recipient_id' => $this->message->recipient_id,
            'body' => $this->message->body,
            'attachments' => collect($this->message->attachments ?? [])
                ->map(fn (array $file, int $index): array => [
                    'index' => $index,
                    'name' => $file['name'],
                    'mime' => $file['mime'],
                    'size' => $file['size'],
                ])
                ->values()
                ->all(),
            'created_at' => $this->message->created_at->toIso8601String(),
        ];
    }
}
