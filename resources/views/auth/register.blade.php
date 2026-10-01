<x-guest-layout>
    <x-auth-shell mode="register" title="Create your account" description="Set up your account and start exploring what comes next.">
        <x-validation-errors class="auth-errors" />

        <form method="POST" action="{{ route('register') }}" class="auth-form auth-form--register">
            @csrf

            <div class="auth-field">
                <x-label for="name" value="{{ __('Full name') }}" class="auth-label" />
                <x-input id="name" class="auth-input" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" placeholder="Your name" />
            </div>

            <div class="auth-field">
                <x-label for="email" value="{{ __('Email address') }}" class="auth-label" />
                <x-input id="email" class="auth-input" type="email" name="email" :value="old('email')" required autocomplete="username" placeholder="you@example.com" />
            </div>

            <div class="auth-field">
                <x-label for="password" value="{{ __('Password') }}" class="auth-label" />
                <x-input id="password" class="auth-input" type="password" name="password" required autocomplete="new-password" placeholder="Create a password" />
            </div>

            <div class="auth-field">
                <x-label for="password_confirmation" value="{{ __('Confirm password') }}" class="auth-label" />
                <x-input id="password_confirmation" class="auth-input" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="Enter it again" />
            </div>

            @if (Laravel\Jetstream\Jetstream::hasTermsAndPrivacyPolicyFeature())
                <label for="terms" class="auth-terms">
                    <x-checkbox name="terms" id="terms" required />
                    <span>{!! __('I agree to the :terms_of_service and :privacy_policy', [
                        'terms_of_service' => '<a target="_blank" rel="noopener" href="'.route('terms.show').'">'.__('Terms of Service').'</a>',
                        'privacy_policy' => '<a target="_blank" rel="noopener" href="'.route('policy.show').'">'.__('Privacy Policy').'</a>',
                    ]) !!}</span>
                </label>
            @endif

            <button class="auth-submit" type="submit">{{ __('Create account') }} <span aria-hidden="true">→</span></button>
        </form>

        <div class="auth-divider"><span>or sign up with</span></div>
        <div class="auth-social">
            <a href="{{ route('social.redirect', 'google') }}" class="auth-social-button">
                <svg viewBox="0 0 20 20" aria-hidden="true"><path fill="#4285F4" d="M19.6 10.23c0-.68-.06-1.36-.18-2.02H10v3.82h5.39a4.6 4.6 0 0 1-2 3.02v2.48h3.23c1.9-1.75 2.98-4.32 2.98-7.3Z"/><path fill="#34A853" d="M10 20c2.7 0 4.97-.9 6.63-2.47l-3.24-2.48c-.9.6-2.05.96-3.39.96-2.61 0-4.82-1.76-5.61-4.13H1.05v2.56A10 10 0 0 0 10 20Z"/><path fill="#FBBC05" d="M4.39 11.88a6 6 0 0 1 0-3.76V5.56H1.05a10 10 0 0 0 0 8.88l3.34-2.56Z"/><path fill="#EA4335" d="M10 3.99c1.47 0 2.79.5 3.82 1.5l2.87-2.86A9.6 9.6 0 0 0 10 0a10 10 0 0 0-8.95 5.56l3.34 2.56C5.18 5.75 7.39 3.99 10 3.99Z"/></svg>
                Continue with Google
            </a>
            <a href="{{ route('social.redirect', 'facebook') }}" class="auth-social-button auth-social-button--facebook">
                <span class="auth-facebook-mark" aria-hidden="true">f</span>
                Continue with Facebook
            </a>
        </div>
    </x-auth-shell>
</x-guest-layout>
