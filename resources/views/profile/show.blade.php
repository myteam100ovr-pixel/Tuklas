<x-app-layout>
    <div class="suite-page suite-profile">
        <x-workspace-heading :eyebrow="__('YOUR PROFILE')" :title="__('A profile that grows with you')" :description="__('Your details, your direction. Keep your information current and manage your account in one place.')" />
        <section class="profile-identity" aria-label="{{ __('Your profile summary') }}">
            <div class="profile-identity-person">
                <span class="suite-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                <div>
                    <span class="profile-role">{{ auth()->user()->role->label() }}</span>
                    <h2>{{ auth()->user()->name }}</h2>
                    <p>{{ auth()->user()->email }}</p>
                </div>
            </div>
            <a class="profile-edit-link" href="#profile-details">{{ __('Edit your details') }} ↗</a>
        </section>
        <div class="suite-split suite-profile-layout">
            <aside class="suite-aside suite-profile-summary">
                <nav class="profile-navigation" aria-label="{{ __('Profile sections') }}">
                    <p class="workspace-eyebrow">{{ __('ON THIS PAGE') }}</p>
                    <a href="#profile-details"><span>01</span> {{ __('Personal details') }} <span aria-hidden="true">↘</span></a>
                    @if (auth()->user()->hasRole(\App\Enums\Role::Youth))
                        <a href="#career-background"><span>02</span> {{ __('Career background') }} <span aria-hidden="true">↘</span></a>
                    @endif
                    <a href="#account-settings"><span aria-hidden="true">⌘</span> {{ __('Account & security') }} <span aria-hidden="true">↘</span></a>
                </nav>
                @if (auth()->user()->hasRole(\App\Enums\Role::Youth))
                    <section class="suite-note profile-guidance">
                        <span class="profile-guidance-symbol" aria-hidden="true">✳</span>
                        <h2>{{ __('Make your next step more personal.') }}</h2>
                        <p>{{ __('Add your skills and interests to help shape your career and training recommendations.') }}</p>
                        <a href="{{ route('youth.assessment.index') }}">{{ __('Update skills & interests') }} ↗</a>
                    </section>
                @endif
            </aside>
            <div class="tk-profile">
                @if (session('status') === 'youth-details-saved')
                    <div class="alert alert-good" role="status">{{ __('Your details were saved.') }}</div>
                @endif
                <section id="profile-details" class="profile-group" aria-labelledby="personal-heading">
                    <div class="profile-group-heading">
                        <span class="profile-section-number" aria-hidden="true">01</span>
                        <div><h2 id="personal-heading">{{ __('Personal details') }}</h2><p>{{ __('How you appear on Tuklas and where we can reach you.') }}</p></div>
                    </div>
                    @if (Laravel\Fortify\Features::canUpdateProfileInformation())
                        @livewire(\App\Livewire\Profile\PersonalInformationForm::class)
                    @endif
                </section>
                @if (auth()->user()->hasRole(\App\Enums\Role::Youth))
                    <section id="career-background" class="profile-group" aria-labelledby="background-heading">
                        <div class="profile-group-heading">
                            <span class="profile-section-number" aria-hidden="true">02</span>
                            <div><h2 id="background-heading">{{ __('Career background') }}</h2><p>{{ __('Your education, community, and interests help guide your next step.') }}</p></div>
                        </div>
                        @include('profile.youth-details')
                    </section>
                @endif
                <section id="account-settings" class="profile-group" aria-labelledby="security-heading">
                    <div class="profile-group-heading">
                        <span class="profile-section-number" aria-hidden="true">⌘</span>
                        <div><h2 id="security-heading">{{ __('Account & security') }}</h2><p>{{ __('Manage your password, sign-in protection, and active sessions.') }}</p></div>
                    </div>
                    <div class="motion-setting"><div><strong>{{ __('Reduce motion') }}</strong><p>{{ __('Turn off animations and transitions for a smoother experience on budget devices.') }}</p></div><label><input type="checkbox" data-reduce-motion-toggle aria-label="{{ __('Reduce motion') }}"> {{ __('Reduce motion') }}</label></div>
                    <div class="motion-setting" data-locale-switcher-card data-locale="{{ app()->getLocale() }}" data-update-url="{{ route('account.locale.update') }}" data-saving-text="{{ __('Saving language…') }}" data-error-text="{{ __('Your language preference could not be saved. Please try again.') }}">
                        <div>
                            <strong>{{ __('Language') }}</strong>
                            <p>{{ __('The language used for Tuklas menus, pages, and forms.') }}</p>
                            <p data-locale-feedback role="status" aria-live="polite"></p>
                        </div>
                        <label for="locale-switcher" class="sr-only">{{ __('Choose a language') }}</label>
                        <select id="locale-switcher" data-locale-switcher class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                            <option value="en" @selected(app()->getLocale() === 'en')>{{ __('English') }}</option>
                            <option value="tl" @selected(app()->getLocale() === 'tl')>{{ __('Tagalog (Filipino)') }}</option>
                        </select>
                    </div>
                    @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::updatePasswords()))
                        @livewire('profile.update-password-form')
                    @endif
                    @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
                        <livewire:profile.two-factor-authentication-form lazy />
                    @endif
                    <livewire:profile.logout-other-browser-sessions-form lazy />
                    @if (Laravel\Jetstream\Jetstream::hasAccountDeletionFeatures())
                        <livewire:profile.delete-user-form lazy />
                    @endif
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
