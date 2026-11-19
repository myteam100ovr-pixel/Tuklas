<x-guest-layout>
    <x-auth-shell mode="register" title="Create your account" description="Create your Career Seeker account, then complete a short skills and interests assessment to unlock AI guidance. You can start without a resume or NC certificate.">
        <x-validation-errors class="auth-errors" />

        <form method="POST" action="{{ route('register') }}" class="auth-form auth-form--register">
            @csrf

            <div class="auth-name-row auth-name-row--names">
                <div class="auth-field">
                    <x-label for="last_name" value="{{ __('Last Name') }}" class="auth-label" />
                    <x-input id="last_name" class="auth-input" type="text" name="last_name" :value="old('last_name')" required autocomplete="family-name" maxlength="127" placeholder="Your last name" />
                </div>

                <div class="auth-field">
                    <x-label for="first_name" value="{{ __('First Name') }}" class="auth-label" />
                    <x-input id="first_name" class="auth-input" type="text" name="first_name" :value="old('first_name')" required autofocus autocomplete="given-name" maxlength="127" placeholder="Your first name" />
                </div>

                <div class="auth-field">
                    <x-label for="middle_initial" value="{{ __('Middle Initial') }}" class="auth-label" />
                    <x-input id="middle_initial" class="auth-input" type="text" name="middle_initial" :value="old('middle_initial')" autocomplete="additional-name" maxlength="10" placeholder="Optional" />
                </div>
            </div>

            <div class="auth-field">
                <x-label for="email" value="{{ __('Email address') }}" class="auth-label" />
                <x-input id="email" class="auth-input" type="email" name="email" :value="old('email')" required autocomplete="username" placeholder="you@example.com" />
            </div>

            <div class="auth-name-row" data-registration-location
                data-stored-location="{{ old('barangay') }}">
                <div class="auth-field">
                    <x-label for="municipality" value="{{ __('Municipality') }}" class="auth-label" />
                    <select id="municipality" class="auth-input auth-select" required autocomplete="address-level2">
                        <option value="" selected disabled>Choose municipality</option>
                        <option value="Bugallon" data-barangays="Angarian|Asinan|Banaga|Bacabac|Bolaoen|Buenlag|Cabayaoasan|Cayanga|Gueset|Hacienda|Laguit Centro|Laguit Padilla|Magtaking|Pangascasan|Pantal|Poblacion|Polong|Portic|Salasa|Salomague Norte|Salomague Sur|Samat|San Francisco|Umanday">Bugallon</option>
                        <option value="Lingayen" data-barangays="Aliwekwek|Baay|Balangobong|Balococ|Bantayan|Basing|Capandanan|Domalandan Center|Domalandan East|Domalandan West|Dorongan|Dulag|Estanza|Lasip|Libsong East|Libsong West|Malawa|Malimpuec|Maniboc|Matalava|Naguelguel|Namolan|Pangapisan North|Pangapisan Sur|Poblacion|Quibaol|Rosario|Sabangan|Talogtog|Tonton|Tumbar|Wawa">Lingayen</option>
                    </select>
                </div>

                <div class="auth-field">
                    <x-label for="barangay_selection" value="{{ __('Barangay') }}" class="auth-label" />
                    <select id="barangay_selection" class="auth-input auth-select" required autocomplete="address-level3" disabled>
                        <option value="" selected disabled>Choose town first</option>
                    </select>
                    <input id="barangay" type="hidden" name="barangay" value="{{ old('barangay') }}" />
                </div>
            </div>

            <div class="auth-name-row">
                <div class="auth-field">
                    <x-label for="contact_number" value="{{ __('Contact number') }}" class="auth-label" />
                    <x-input id="contact_number" class="auth-input" type="tel" name="contact_number" :value="old('contact_number')" required autocomplete="tel" maxlength="30" placeholder="09XX XXX XXXX" />
                </div>

                <div class="auth-field">
                    <x-label for="date_of_birth" value="{{ __('Date of birth') }}" class="auth-label" />
                    <x-input id="date_of_birth" class="auth-input" type="date" name="date_of_birth" :value="old('date_of_birth')" min="{{ now()->subYears(31)->addDay()->toDateString() }}" max="{{ now()->subYears(18)->toDateString() }}" required autocomplete="bday" aria-describedby="birth-date-help" />
                    <p id="birth-date-help" class="auth-field-help">Career Seekers must be 18 to 30 years old.</p>
                    <output class="auth-field-help" for="date_of_birth" data-calculated-age aria-live="polite"></output>
                </div>
            </div>

            <div class="auth-password-group" data-password-strength-form>
                <div class="auth-field">
                    <x-label for="password" value="{{ __('Password') }}" class="auth-label" />
                    <div class="auth-password-field">
                        <x-input id="password" class="auth-input" type="password" name="password" required maxlength="72" autocomplete="new-password" aria-describedby="password-policy" placeholder="Use 12+ characters and 1 special character" data-password-strength-input />
                        <button type="button" class="auth-password-toggle" data-auth-password-toggle aria-controls="password" aria-label="Show password" aria-pressed="false" title="Show password">
                            <svg class="ic" data-auth-password-hidden aria-hidden="true"><use href="#i-eye"/></svg>
                            <svg class="ic" data-auth-password-visible aria-hidden="true" hidden><use href="#i-eye-off"/></svg>
                        </button>
                    </div>
                    <p id="password-policy" class="auth-field-help">Use 12 to 72 characters with uppercase and lowercase letters, a number, and a symbol.</p>
                </div>

                <div class="auth-field">
                    <x-label for="password_confirmation" value="{{ __('Confirm password') }}" class="auth-label" />
                    <div class="auth-password-field">
                        <x-input id="password_confirmation" class="auth-input" type="password" name="password_confirmation" required maxlength="72" autocomplete="new-password" placeholder="Enter it again" data-password-confirmation />
                        <button type="button" class="auth-password-toggle" data-auth-password-toggle aria-controls="password_confirmation" aria-label="Show password" aria-pressed="false" title="Show password">
                            <svg class="ic" data-auth-password-hidden aria-hidden="true"><use href="#i-eye"/></svg>
                            <svg class="ic" data-auth-password-visible aria-hidden="true" hidden><use href="#i-eye-off"/></svg>
                        </button>
                    </div>
                    <small class="users-form__confirmation-error" data-password-confirmation-message hidden>Passwords do not match.</small>
                </div>

                <div class="users-form__strength" data-password-strength-meter data-strength="weak" aria-live="polite">
                    <div class="users-form__strength-heading"><span>Password strength</span><strong data-password-strength-label>Weak</strong></div>
                    <div class="users-form__strength-track" aria-hidden="true"><span></span></div>
                    <p data-password-strength-message>Enter a password to check its strength.</p>
                </div>
            </div>

            <div class="auth-terms">
                <x-checkbox name="terms" id="terms" value="1" required />
                <label for="terms">I agree to the <a href="{{ route('terms.show') }}" data-legal-open="terms">Terms of use</a> and acknowledge the <a href="{{ route('policy.show') }}" data-legal-open="privacy">Privacy notice</a>.</label>
            </div>

            <button class="auth-submit" type="submit" data-password-submit>{{ __('Create account') }} <span aria-hidden="true">→</span></button>
        </form>

    </x-auth-shell>
</x-guest-layout>
