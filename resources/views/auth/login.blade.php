<x-guest-layout>
    <x-auth-shell mode="login" title="{{ __('Welcome back') }}" description="Log in to pick up where your next step begins.">
        <x-validation-errors class="auth-errors" />

        @session('status')
            <div class="auth-status" role="status">{{ $value }}</div>
        @endsession

        <form method="POST" action="{{ route('login') }}" class="auth-form">
            @csrf

            <div class="auth-field">
                <x-label for="email" value="{{ __('Email address') }}" class="auth-label" />
                <x-input id="email" class="auth-input" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="you@example.com" />
            </div>

            <div class="auth-field">
                <div class="auth-label-row">
                    <x-label for="password" value="{{ __('Password') }}" class="auth-label" />
                    @if (Route::has('password.request'))
                        <a class="auth-inline-link" href="{{ route('password.request') }}">{{ __('Forgot password?') }}</a>
                    @endif
                </div>
                <div class="auth-password-field">
                    <x-input id="password" class="auth-input" type="password" name="password" required autocomplete="current-password" placeholder="{{ __('Enter your password') }}" />
                    <button type="button" class="auth-password-toggle" data-auth-password-toggle aria-controls="password" aria-label="{{ __('Show password') }}" aria-pressed="false" title="{{ __('Show password') }}">
                        <svg class="ic" data-auth-password-hidden aria-hidden="true"><use href="#i-eye"/></svg>
                        <svg class="ic" data-auth-password-visible aria-hidden="true" hidden><use href="#i-eye-off"/></svg>
                    </button>
                </div>
            </div>

            <label for="remember_me" class="auth-remember">
                <x-checkbox id="remember_me" name="remember" />
                <span>{{ __('Remember me') }}</span>
            </label>

            <button class="auth-submit" type="submit">{{ __('Log in') }} <span aria-hidden="true">→</span></button>
        </form>

    </x-auth-shell>
</x-guest-layout>
