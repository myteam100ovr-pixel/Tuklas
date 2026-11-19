<x-guest-layout>
    <x-auth-shell mode="security" title="Choose a new password" description="Use a unique password to keep your account secure.">
        <x-validation-errors class="auth-errors" />

        <form method="POST" action="{{ route('password.update') }}" data-password-strength-form>
            @csrf

            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <div class="block">
                <x-label for="email" value="{{ __('Email') }}" />
                <x-input id="email" class="auth-input block mt-1 w-full" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
            </div>

            <div class="mt-4">
                <x-label for="password" value="{{ __('Password') }}" />
                <x-input id="password" class="auth-input block mt-1 w-full" type="password" name="password" required maxlength="72" autocomplete="new-password" data-password-strength-input />
            </div>

            <div class="mt-4">
                <x-label for="password_confirmation" value="{{ __('Confirm Password') }}" />
                <x-input id="password_confirmation" class="auth-input block mt-1 w-full" type="password" name="password_confirmation" required maxlength="72" autocomplete="new-password" data-password-confirmation />
                <small class="users-form__confirmation-error" data-password-confirmation-message hidden>Passwords do not match.</small>
            </div>

            <div class="users-form__strength" data-password-strength-meter data-strength="weak" aria-live="polite">
                <div class="users-form__strength-heading"><span>Password strength</span><strong data-password-strength-label>Weak</strong></div>
                <div class="users-form__strength-track" aria-hidden="true"><span></span></div>
                <p data-password-strength-message>Enter a password to check its strength.</p>
                <small>Use 12 to 72 characters with uppercase and lowercase letters, a number, and a symbol.</small>
            </div>

            <div class="flex items-center justify-end mt-4">
                <x-button class="auth-submit" data-password-submit>
                    {{ __('Reset Password') }}
                </x-button>
            </div>
        </form>
    </x-auth-shell>
</x-guest-layout>
