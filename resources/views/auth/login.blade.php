<x-guest-layout>
    <x-auth-shell mode="login" title="Welcome back" description="Log in to pick up where your next step begins.">
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
                <x-input id="password" class="auth-input" type="password" name="password" required autocomplete="current-password" placeholder="Enter your password" />
            </div>

            <label for="remember_me" class="auth-remember">
                <x-checkbox id="remember_me" name="remember" />
                <span>{{ __('Remember me') }}</span>
            </label>

            <button class="auth-submit" type="submit">{{ __('Log in') }} <span aria-hidden="true">→</span></button>
        </form>

        <p class="auth-field-help">Previously used Google or Facebook? Use <a class="auth-inline-link" href="{{ route('password.request') }}">Forgot password</a> with the same email to set a password.</p>

    </x-auth-shell>
</x-guest-layout>
