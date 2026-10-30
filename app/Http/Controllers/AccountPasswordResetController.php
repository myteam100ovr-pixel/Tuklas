<?php

namespace App\Http\Controllers;

use App\Actions\Fortify\PasswordValidationRules;
use App\Support\ActivityNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AccountPasswordResetController extends Controller
{
    use PasswordValidationRules;

    public function edit(Request $request): View
    {
        abort_unless($this->hasPendingReset($request), 404);

        return view('notifications.reset-password');
    }

    public function update(Request $request, ActivityNotifier $activity): RedirectResponse
    {
        abort_unless($this->hasPendingReset($request), 404);
        $data = $request->validate(['password' => $this->passwordRules()]);
        $user = $request->user();
        $user->forceFill([
            'password' => Hash::make($data['password']),
            'admin_password_reset_requested_at' => null,
        ])->save();
        $activity->record($user, 'completed an administrator-requested password reset.');

        return redirect()->route('notifications.index')->with('status', 'Your password was updated.');
    }

    private function hasPendingReset(Request $request): bool
    {
        return $request->user()->admin_password_reset_requested_at?->copy()->addHour()->isFuture() ?? false;
    }
}
