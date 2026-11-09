<x-guest-layout>
    <x-auth-shell mode="security" title="Reset your password" description="Enter your email to receive a password reset link.">
        @session('status')
            <div class="auth-status">
                {{ $value }}
            </div>
        @endsession

        <x-validation-errors class="auth-errors" />

        <form method="POST" action="{{ route('password.email') }}">
            @csrf

            <div class="block">
                <x-label for="email" value="{{ __('Email') }}" />
                <x-input id="email" class="auth-input block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            </div>

            <div class="flex items-center justify-end mt-4">
                <x-button class="auth-submit">
                    {{ __('Email Password Reset Link') }}
                </x-button>
            </div>
        </form>
    </x-auth-shell>
</x-guest-layout>
