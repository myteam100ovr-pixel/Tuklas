<?php

namespace App\Http\Controllers\Api;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\PasswordValidationRules;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Fortify;
use Laravel\Sanctum\PersonalAccessToken;

class MobileAuthController extends Controller
{
    use PasswordValidationRules;

    public function register(Request $request, CreateNewUser $createUser): JsonResponse
    {
        $validated = $request->validate([
            'device_name' => ['required', 'string', 'max:100'],
        ]);

        $user = $createUser->create($request->only([
            'name', 'email', 'password', 'password_confirmation', 'terms',
        ]));

        event(new Registered($user));

        return $this->issueToken($user, $validated['device_name'], 201);
    }

    public function token(Request $request, TwoFactorAuthenticationProvider $twoFactorProvider): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:100'],
        ]);

        $user = User::query()->where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        abort_unless($user->is_active, 403, 'This account is inactive.');
        $this->verifyTwoFactorIfNeeded($request, $user, $twoFactorProvider);

        return $this->issueToken($user, $validated['device_name']);
    }

    public function exchangeSocialTicket(Request $request, TwoFactorAuthenticationProvider $twoFactorProvider): JsonResponse
    {
        $validated = $request->validate([
            'ticket' => ['required', 'string', 'max:100'],
            'device_name' => ['required', 'string', 'max:100'],
        ]);
        $ticketKey = 'mobile-social-ticket:'.hash('sha256', $validated['ticket']);
        $userId = Cache::get($ticketKey);

        if (! is_int($userId)) {
            throw ValidationException::withMessages(['ticket' => ['This sign-in link is invalid or expired.']]);
        }

        $user = User::find($userId);

        if (! $user || ! $user->is_active || $user->role->value !== 'youth') {
            abort(403, 'This account cannot sign in with the mobile app.');
        }

        $this->verifyTwoFactorIfNeeded($request, $user, $twoFactorProvider);
        Cache::forget($ticketKey);

        return $this->issueToken($user, $validated['device_name']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $this->presentUser($request->user())]);
    }

    public function updateAccount(Request $request, UpdateUserProfileInformation $updateProfile): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($request->user()->id)],
        ]);

        $updateProfile->update($request->user(), $validated);

        return response()->json(['user' => $this->presentUser($request->user()->fresh())]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => $this->passwordRules(),
        ]);
        $user = $request->user();

        if (! Hash::check($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The provided password does not match your current password.'],
            ]);
        }

        $user->forceFill(['password' => Hash::make($validated['password'])])->save();
        $currentToken = $user->currentAccessToken();
        $tokens = $user->tokens();

        if ($currentToken instanceof PersonalAccessToken) {
            $tokens->whereKeyNot($currentToken->getKey())->delete();
        } else {
            $tokens->delete();
        }

        return response()->json(['message' => 'Password updated.']);
    }

    public function requestPasswordReset(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);
        Password::sendResetLink($request->only('email'));

        return response()->json([
            'message' => 'If an account exists for this email, a password reset link has been sent.',
        ], 202);
    }

    public function resetPassword(Request $request, ResetUserPassword $resetPassword): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
            'password' => $this->passwordRules(),
            'password_confirmation' => ['required', 'string'],
        ]);

        $status = Password::reset($validated, function (User $user, string $password) use ($validated, $resetPassword): void {
            $resetPassword->reset($user, [...$validated, 'password' => $password]);
            $user->tokens()->delete();
        });

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => [__($status)]]);
        }

        return response()->json(['message' => __($status)]);
    }

    public function destroyToken(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return response()->json([], 204);
    }

    public function sendVerification(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email address already verified.']);
        }

        $user->sendEmailVerificationNotification();

        return response()->json(['message' => 'Verification email sent.'], 202);
    }

    public function security(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'two_factor_enabled' => $user->hasEnabledTwoFactorAuthentication(),
            'two_factor_setup_pending' => ! is_null($user->two_factor_secret) && ! $user->hasEnabledTwoFactorAuthentication(),
            'passkeys_available' => false,
        ]);
    }

    public function enableTwoFactor(Request $request, EnableTwoFactorAuthentication $enable): JsonResponse
    {
        $this->confirmCurrentPassword($request);
        $user = $request->user();

        if ($user->hasEnabledTwoFactorAuthentication()) {
            return response()->json(['message' => 'Two-factor authentication is already enabled.'], 409);
        }

        $enable($user);
        $secret = Fortify::currentEncrypter()->decrypt($user->two_factor_secret);

        return response()->json([
            'qr_code_url' => $user->twoFactorQrCodeUrl(),
            'manual_setup_key' => $secret,
        ], 201);
    }

    public function confirmTwoFactor(Request $request, ConfirmTwoFactorAuthentication $confirm): JsonResponse
    {
        $validated = $request->validate(['code' => ['required', 'digits:6']]);
        $user = $request->user();
        $confirm($user, $validated['code']);
        $user = $user->fresh();

        return response()->json([
            'two_factor_enabled' => $user->hasEnabledTwoFactorAuthentication(),
            'recovery_codes' => $user->recoveryCodes(),
        ]);
    }

    public function regenerateRecoveryCodes(Request $request, GenerateNewRecoveryCodes $generate): JsonResponse
    {
        $this->confirmCurrentPassword($request);
        $user = $request->user();
        abort_unless($user->hasEnabledTwoFactorAuthentication(), 409);

        $generate($user);

        return response()->json(['recovery_codes' => $user->fresh()->recoveryCodes()]);
    }

    public function disableTwoFactor(Request $request, DisableTwoFactorAuthentication $disable): JsonResponse
    {
        $this->confirmCurrentPassword($request);
        $user = $request->user();
        abort_unless($user->hasEnabledTwoFactorAuthentication(), 409);

        $disable($user);

        return response()->json(['two_factor_enabled' => false]);
    }

    private function confirmCurrentPassword(Request $request): void
    {
        $validated = $request->validate(['current_password' => ['required', 'string']]);

        if (! Hash::check($validated['current_password'], $request->user()->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The provided password does not match your current password.'],
            ]);
        }
    }

    private function verifyTwoFactorIfNeeded(Request $request, User $user, TwoFactorAuthenticationProvider $provider): void
    {
        if (! $user->hasEnabledTwoFactorAuthentication()) {
            return;
        }

        if (! $request->filled('code') && ! $request->filled('recovery_code')) {
            abort(response()->json(['two_factor_required' => true], 409));
        }

        $challenge = $request->validate([
            'code' => ['nullable', 'digits:6', 'required_without:recovery_code'],
            'recovery_code' => ['nullable', 'string', 'max:100', 'required_without:code'],
        ]);

        if (filled($challenge['recovery_code'] ?? null)) {
            $recoveryCode = collect($user->recoveryCodes())->first(
                fn (string $stored): bool => hash_equals($stored, $challenge['recovery_code'])
            );

            if ($recoveryCode === null) {
                throw ValidationException::withMessages([
                    'recovery_code' => ['The recovery code is invalid.'],
                ]);
            }

            $user->replaceRecoveryCode($recoveryCode);

            return;
        }

        $secret = Fortify::currentEncrypter()->decrypt($user->two_factor_secret);

        if (! $provider->verify($secret, $challenge['code'])) {
            throw ValidationException::withMessages([
                'code' => ['The authentication code is invalid.'],
            ]);
        }
    }

    private function issueToken(User $user, string $deviceName, int $status = 200): JsonResponse
    {
        $token = $user->createToken($deviceName, ['*'], now()->addDays(90));

        return response()->json([
            'token' => $token->plainTextToken,
            'user' => $this->presentUser($user),
        ], $status);
    }

    /** @return array<string, mixed> */
    private function presentUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified' => $user->hasVerifiedEmail(),
            'role' => $user->role->value,
            'role_label' => $user->role->label(),
            'date_of_birth' => $user->date_of_birth?->toDateString(),
        ];
    }
}
