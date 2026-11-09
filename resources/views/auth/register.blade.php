<x-guest-layout>
    <x-auth-shell mode="register" title="Create your account" description="Create your Youth account to explore career guidance, skills, and local TESDA training.">
        <x-validation-errors class="auth-errors" />

        <form method="POST" action="{{ route('register') }}" class="auth-form auth-form--register">
            @csrf

            <div class="auth-name-row">
                <div class="auth-field">
                    <x-label for="first_name" value="{{ __('First Name') }}" class="auth-label" />
                    <x-input id="first_name" class="auth-input" type="text" name="first_name" :value="old('first_name')" required autofocus autocomplete="given-name" maxlength="127" placeholder="Your first name" />
                </div>

                <div class="auth-field">
                    <x-label for="last_name" value="{{ __('Last Name') }}" class="auth-label" />
                    <x-input id="last_name" class="auth-input" type="text" name="last_name" :value="old('last_name')" required autocomplete="family-name" maxlength="127" placeholder="Your last name" />
                </div>
            </div>

            <div class="auth-field">
                <x-label for="email" value="{{ __('Email address') }}" class="auth-label" />
                <x-input id="email" class="auth-input" type="email" name="email" :value="old('email')" required autocomplete="username" placeholder="you@example.com" />
            </div>

            <div class="auth-name-row">
                <div class="auth-field">
                    <x-label for="contact_number" value="{{ __('Contact number') }}" class="auth-label" />
                    <x-input id="contact_number" class="auth-input" type="tel" name="contact_number" :value="old('contact_number')" required autocomplete="tel" maxlength="30" placeholder="09XX XXX XXXX" />
                </div>

                <div class="auth-field">
                    <x-label for="barangay" value="{{ __('Barangay') }}" class="auth-label" />
                    <x-input id="barangay" class="auth-input" type="text" name="barangay" :value="old('barangay')" required autocomplete="address-level3" maxlength="100" placeholder="Barangay in Bugallon" />
                </div>
            </div>

            <div class="auth-field">
                <x-label for="date_of_birth" value="{{ __('Date of birth') }}" class="auth-label" />
                <x-input id="date_of_birth" class="auth-input" type="date" name="date_of_birth" :value="old('date_of_birth')" required autocomplete="bday" aria-describedby="birth-date-help" />
                <p id="birth-date-help" class="auth-field-help">Tuklas is for youth aged 15 to 30.</p>
                <output class="auth-field-help" for="date_of_birth" data-calculated-age aria-live="polite"></output>
            </div>

            <div class="auth-field">
                <x-label for="password" value="{{ __('Password') }}" class="auth-label" />
                <x-input id="password" class="auth-input" type="password" name="password" required autocomplete="new-password" placeholder="Create a password" />
            </div>

            <div class="auth-field">
                <x-label for="password_confirmation" value="{{ __('Confirm password') }}" class="auth-label" />
                <x-input id="password_confirmation" class="auth-input" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="Enter it again" />
            </div>

            <div class="auth-terms">
                <x-checkbox name="terms" id="terms" value="1" required />
                <label for="terms">I agree to the <a href="{{ route('terms.show') }}">Terms of use</a> and acknowledge the <a href="{{ route('policy.show') }}">Privacy notice</a>.</label>
            </div>

            <button class="auth-submit" type="submit">{{ __('Create account') }} <span aria-hidden="true">→</span></button>
        </form>

        <p class="auth-field-help">Previously used Google or Facebook? Use <a class="auth-inline-link" href="{{ route('password.request') }}">Forgot password</a> with the same email to set a password.</p>

        <x-demo-accounts />
    </x-auth-shell>
</x-guest-layout>
