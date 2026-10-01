<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    public function redirect(string $provider)
    {
        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider): RedirectResponse
    {
        try {
            $social = Socialite::driver($provider)->user();
        } catch (\Throwable $exception) {
            report($exception);

            return $this->fail('Sign-in with '.ucfirst($provider)." didn't work. Please try again or use your email and password.");
        }

        $email = $social->getEmail();
        if (! $email) {
            return $this->fail(ucfirst($provider).' did not share an email address with Tuklas. Please register with your email instead.');
        }

        $linked = SocialAccount::where('provider', $provider)
            ->where('provider_id', $social->getId())
            ->first();

        if ($linked) {
            $user = $linked->user;
        } else {
            // Never auto-link to an existing account by email: an unverified
            // provider email could otherwise take over someone's account.
            if (User::where('email', $email)->exists()) {
                return $this->fail('An account with this email already exists. Please sign in with your email and password.');
            }

            $user = DB::transaction(function () use ($social, $provider, $email) {
                $user = User::forceCreate([
                    'name' => $social->getName() ?: Str::before($email, '@'),
                    'email' => $email,
                    'password' => Hash::make(Str::random(40)),
                    'role' => Role::Youth->value,
                    'email_verified_at' => now(),
                ]);

                $user->socialAccounts()->create([
                    'provider' => $provider,
                    'provider_id' => $social->getId(),
                ]);

                return $user;
            });
        }

        if ($user->role !== Role::Youth || ! $user->is_active) {
            return $this->fail('This account cannot sign in with '.ucfirst($provider).'.');
        }

        Auth::login($user, remember: true);

        return redirect()->route('dashboard');
    }

    private function fail(string $message): RedirectResponse
    {
        return redirect()->route('login')->withErrors(['email' => $message]);
    }
}
