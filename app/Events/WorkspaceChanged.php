<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;

class WorkspaceChanged implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public string $resource, public ?int $actorId = null)
    {
        $this->actorId ??= Auth::id();
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('workspace')];
    }

    public function broadcastAs(): string
    {
        return 'workspace.changed';
    }

    /** @return array{resource: string, actor_id: int|null} */
    public function broadcastWith(): array
    {
        return ['resource' => $this->resource, 'actor_id' => $this->actorId];
    }
}
