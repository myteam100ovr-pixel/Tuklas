<?php

namespace App\Actions\Jetstream;

use App\Enums\Role;
use App\Models\User;
use App\Support\ActivityNotifier;
use Laravel\Jetstream\Contracts\DeletesUsers;

class DeleteUser implements DeletesUsers
{
    /**
     * Delete the given user.
     */
    public function delete(User $user): void
    {
        if ($user->role === Role::SuperAdmin) {
            abort_if(User::query()->where('role', Role::SuperAdmin->value)->where('is_active', true)->count() <= 1, 422, 'The last active administrator cannot be removed.');
        }

        app(ActivityNotifier::class)->record($user, 'deleted their account.');
        $user->deleteProfilePhoto();
        $user->tokens->each->delete();
        $user->delete();
    }
}
