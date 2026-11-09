<x-guest-layout>
    <x-auth-shell mode="security" title="Confirm your password" description="Confirm it is you before continuing.">
        <div class="auth-field-help mb-4">
            {{ __('This is a secure area of the application. Please confirm your password before continuing.') }}
        </div>

        <x-validation-errors class="auth-errors" />

        <form method="POST" action="{{ route('password.confirm') }}">
            @csrf

            <div>
                <x-label for="password" value="{{ __('Password') }}" />
                <x-input id="password" class="auth-input block mt-1 w-full" type="password" name="password" required autocomplete="current-password" autofocus />
            </div>

            <div class="flex justify-end mt-4">
                <x-button class="auth-submit ms-4">
                    {{ __('Confirm') }}
                </x-button>
            </div>
        </form>
    </x-auth-shell>
</x-guest-layout>
