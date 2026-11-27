<x-form-section submit="updatePassword">
    <x-slot name="title">
        {{ __('Update Password') }}
    </x-slot>

    <x-slot name="description">
        {{ __('Ensure your account is using a long, random password to stay secure.') }}
    </x-slot>

    <x-slot name="form">
        <div class="col-span-6 sm:col-span-4">
            <x-label for="current_password" value="{{ __('Current Password') }}" />
            <x-input id="current_password" type="password" class="mt-1 block w-full" wire:model="state.current_password" autocomplete="current-password" />
            <x-input-error for="current_password" class="mt-2" />
        </div>

        <div class="col-span-6 sm:col-span-4" data-password-strength-form>
            <div class="mb-4">
                <x-label for="password" value="{{ __('New Password') }}" />
                <x-input id="password" type="password" class="mt-1 block w-full" wire:model="state.password" autocomplete="new-password" maxlength="72" data-password-strength-input />
                <x-input-error for="password" class="mt-2" />
            </div>

            <div class="mb-4">
                <x-label for="password_confirmation" value="{{ __('Confirm Password') }}" />
                <x-input id="password_confirmation" type="password" class="mt-1 block w-full" wire:model="state.password_confirmation" autocomplete="new-password" maxlength="72" data-password-confirmation />
                <small class="users-form__confirmation-error" data-password-confirmation-message hidden>{{ __('Passwords do not match.') }}</small>
                <x-input-error for="password_confirmation" class="mt-2" />
            </div>

            <div class="users-form__strength" data-password-strength-meter data-strength="weak" aria-live="polite">
                <div class="users-form__strength-heading"><span>{{ __('Password strength') }}</span><strong data-password-strength-label>{{ __('Weak') }}</strong></div>
                <div class="users-form__strength-track" aria-hidden="true"><span></span></div>
                <p data-password-strength-message>{{ __('Enter a password to check its strength.') }}</p>
                <small>{{ __('Use 12 to 72 characters with uppercase and lowercase letters, a number, and a symbol.') }}</small>
            </div>
        </div>
    </x-slot>

    <x-slot name="actions">
        <x-action-message class="me-3" on="saved">
            {{ __('Saved.') }}
        </x-action-message>

        <x-button>
            {{ __('Save') }}
        </x-button>
    </x-slot>
</x-form-section>
