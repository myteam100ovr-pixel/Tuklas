<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class WorkspaceNotice extends Notification
{
    public function __construct(
        public string $title,
        public string $message,
        public string $kind = 'account',
        public ?string $actorRole = null,
        public ?string $actionUrl = null,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array{title: string, message: string, kind: string, actor_role: string|null, action_url: string|null} */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'kind' => $this->kind,
            'actor_role' => $this->actorRole,
            'action_url' => $this->actionUrl,
        ];
    }
}
