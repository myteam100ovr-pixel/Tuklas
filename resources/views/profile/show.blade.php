<x-app-layout>
    <div class="tk-profile">
        <div class="dash-title"><h1>Edit profile</h1></div>

        @if (session('status') === 'youth-details-saved')
            <div class="alert alert-good" role="status">Your details were saved.</div>
        @endif

        @if (auth()->user()->hasRole(\App\Enums\Role::Youth))
            @include('profile.youth-details')
        @endif

        @if (Laravel\Fortify\Features::canUpdateProfileInformation())
            @livewire('profile.update-profile-information-form')
        @endif

        @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::updatePasswords()))
            @livewire('profile.update-password-form')
        @endif

        @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
            @livewire('profile.two-factor-authentication-form')
        @endif

        @livewire('profile.logout-other-browser-sessions-form')

        @if (Laravel\Jetstream\Jetstream::hasAccountDeletionFeatures())
            @livewire('profile.delete-user-form')
        @endif
    </div>
</x-app-layout>