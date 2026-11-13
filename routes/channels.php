<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('users.{userId}', function (User $user, int $userId): bool {
    return $user->id === $userId
        && $user->is_active
        && $user->hasVerifiedEmail();
}, ['guards' => ['web', 'sanctum']]);
