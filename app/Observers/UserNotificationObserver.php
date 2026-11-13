<?php

namespace App\Observers;

use App\Events\UserNotificationsChanged;
use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Log;

class UserNotificationObserver
{
    public function saved(DatabaseNotification $notification): void
    {
        if ($notification->notifiable_type !== User::class || ! is_numeric($notification->notifiable_id)) {
            return;
        }

        try {
            UserNotificationsChanged::dispatch((int) $notification->notifiable_id);
        } catch (\Throwable $exception) {
            Log::warning('A notification was saved but its live update could not be broadcast.', [
                'user_id' => $notification->notifiable_id,
                'exception' => $exception,
            ]);
        }
    }
}
