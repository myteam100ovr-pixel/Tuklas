<?php

namespace App\Actions\Fortify;

use App\Enums\Role;
use App\Models\User;
use App\Support\ActivityNotifier;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
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
        $registrationDetails = null;

        if (array_key_exists('first_name', $input) || array_key_exists('last_name', $input) || ! array_key_exists('name', $input)) {
            $registrationDetails = Validator::make($input, [
                'first_name' => ['required', 'string', 'max:127'],
                'last_name' => ['required', 'string', 'max:127'],
                'contact_number' => ['required', 'string', 'max:30', 'regex:/^[0-9+\-\s()]*$/'],
                'barangay' => ['required', 'string', 'max:100'],
                'date_of_birth' => ['bail', 'required', 'date', function (string $attribute, mixed $value, \Closure $fail): void {
                    $age = Carbon::parse($value)->age;

                    if ($age < 15 || $age > 30) {
                        $fail('Tuklas is for youth aged 15 to 30.');
                    }
                }],
            ])->validate();

            $input['name'] = trim($registrationDetails['first_name']).' '.trim($registrationDetails['last_name']);
        }

        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => $this->passwordRules(),
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
        ])->validate();

        $user = DB::transaction(function () use ($input, $registrationDetails): User {
            $user = User::forceCreate([
                'name' => $input['name'],
                'first_name' => isset($registrationDetails) ? trim($registrationDetails['first_name']) : null,
                'last_name' => isset($registrationDetails) ? trim($registrationDetails['last_name']) : null,
                'email' => $input['email'],
                'password' => Hash::make($input['password']),
                'role' => Role::Youth->value,
                'date_of_birth' => $registrationDetails['date_of_birth'] ?? null,
            ]);

            if (isset($registrationDetails)) {
                $user->youthProfile()->create([
                    'contact_number' => trim($registrationDetails['contact_number']),
                    'barangay' => trim($registrationDetails['barangay']),
                ]);
            }

            return $user;
        });

        app(ActivityNotifier::class)->record($user, 'registered an account.');

        return $user;
    }
}
