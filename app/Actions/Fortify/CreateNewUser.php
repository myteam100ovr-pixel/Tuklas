<?php

namespace App\Actions\Fortify;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Jetstream\Jetstream;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        if (array_key_exists('first_name', $input) || array_key_exists('last_name', $input) || ! array_key_exists('name', $input)) {
            $names = Validator::make($input, [
                'first_name' => ['required', 'string', 'max:127'],
                'last_name' => ['required', 'string', 'max:127'],
            ])->validate();

            $input['name'] = trim($names['first_name']).' '.trim($names['last_name']);
        }

        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => $this->passwordRules(),
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
        ])->validate();

        return User::forceCreate([
            'name' => $input['name'],
            'first_name' => isset($names) ? trim($names['first_name']) : null,
            'last_name' => isset($names) ? trim($names['last_name']) : null,
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
            'role' => Role::Youth->value,
        ]);
    }
}
