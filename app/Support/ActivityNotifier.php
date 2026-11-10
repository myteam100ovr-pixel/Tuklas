<?php

namespace App\Support;

use App\Enums\Role;
use App\Models\User;
use App\Notifications\WorkspaceNotice;

class ActivityNotifier
{
    public function record(User $actor, string $action): void
    {
        $role = $actor->role ?? $actor->fresh()?->role ?? Role::Youth;
        $title = match ($role) {
            Role::Youth => 'Youth activity',
            Role::Trainer => 'TESDA trainer activity',
            Role::TesdaAdmin => 'TESDA administration activity',
            Role::CenterRepresentative => 'Training center activity',
            Role::SuperAdmin => 'Admin activity',
        };

        User::query()
            ->where('role', Role::SuperAdmin->value)
            ->where('is_active', true)
            ->eachById(function (User $administrator) use ($actor, $action, $title, $role): void {
                $administrator->notify(new WorkspaceNotice(
                    $title,
                    $actor->name.' '.$action,
                    'activity',
                    $role->value,
                ));
            });
    }
}
