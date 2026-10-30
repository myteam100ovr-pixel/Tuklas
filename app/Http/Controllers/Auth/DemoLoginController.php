<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class DemoLoginController extends Controller
{
    public function __invoke(Request $request, string $role): RedirectResponse
    {
        abort_unless(app()->environment(['local', 'testing']), 404);

        $demoRole = Role::tryFrom($role);
        abort_if($demoRole === null, 404);

        $email = 'demo-'.$demoRole->value.'@tuklas.test';
        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            $user = User::forceCreate([
                'name' => 'Demo '.$demoRole->label(),
                'email' => $email,
                'password' => Str::random(48),
                'role' => $demoRole->value,
                'email_verified_at' => now(),
                'is_active' => true,
                'date_of_birth' => $demoRole === Role::Youth ? '2003-01-01' : null,
            ]);
        }

        abort_unless(
            $user->role === $demoRole && $user->is_active && $user->hasVerifiedEmail() && blank($user->two_factor_secret),
            403
        );

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route($demoRole->homeRoute());
    }
}
