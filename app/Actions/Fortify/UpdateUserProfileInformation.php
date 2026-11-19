<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Support\ActivityNotifier;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;

class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    /**
     * Validate and update the given user's profile information.
     *
     * @param  array<string, mixed>  $input
     */
    public function update(User $user, array $input): void
    {
        $names = ['first_name' => null, 'middle_initial' => null, 'last_name' => null];

        if (array_key_exists('first_name', $input) || array_key_exists('last_name', $input) || array_key_exists('middle_initial', $input)) {
            $names = Validator::make($input, [
                'first_name' => ['required', 'string', 'max:127'],
                'middle_initial' => ['nullable', 'string', 'max:10'],
                'last_name' => ['required', 'string', 'max:127'],
            ])->validateWithBag('updateProfileInformation');

            $names = array_map(fn (mixed $part): ?string => is_string($part) ? trim($part) : null, $names);
            $input['name'] = collect([$names['first_name'], $names['middle_initial'], $names['last_name']])
                ->filter(fn (?string $part): bool => filled($part))->implode(' ');
        } elseif (($input['name'] ?? null) === $user->name) {
            $names = [
                'first_name' => $user->first_name,
                'middle_initial' => $user->middle_initial,
                'last_name' => $user->last_name,
            ];
        } elseif (isset($input['name'])) {
            $parts = preg_split('/\s+/', trim($input['name']), 2);
            $names = [
                'first_name' => $parts[0] ?? null,
                'middle_initial' => null,
                'last_name' => $parts[1] ?? null,
            ];
        }

        $input = array_merge($input, $names);

        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'photo' => ['nullable', 'mimes:jpg,jpeg,png', 'max:1024'],
        ])->validateWithBag('updateProfileInformation');

        if (isset($input['photo'])) {
            $user->updateProfilePhoto($input['photo']);
        }

        if ($input['email'] !== $user->email &&
            $user instanceof MustVerifyEmail) {
            $this->updateVerifiedUser($user, $input);
        } else {
            $user->forceFill([
                'name' => $input['name'],
                'first_name' => $input['first_name'],
                'middle_initial' => $input['middle_initial'] ?? null,
                'last_name' => $input['last_name'],
                'email' => $input['email'],
            ])->save();
        }

        app(ActivityNotifier::class)->record($user, 'updated their account profile.');
    }

    /**
     * Update the given verified user's profile information.
     *
     * @param  array<string, string>  $input
     */
    protected function updateVerifiedUser(User $user, array $input): void
    {
        $user->forceFill([
            'name' => $input['name'],
            'first_name' => $input['first_name'],
            'middle_initial' => $input['middle_initial'] ?? null,
            'last_name' => $input['last_name'],
            'email' => $input['email'],
            'email_verified_at' => null,
        ])->save();

        $user->sendEmailVerificationNotification();
    }
}
