@props(['accent', 'roleLabel', 'eyebrow', 'title', 'description', 'focusAreas' => [], 'navigation' => []])

<div class="role-dashboard role-dashboard--{{ $accent }}">
    <div class="role-dashboard__inner">
        <div class="role-dashboard__topline">
            <div class="role-dashboard__identity">
                <a class="role-dashboard__brand" href="{{ route('home') }}" aria-label="{{ __('Tuklas home') }}"><img class="role-dashboard__brand-image" src="{{ asset('images/tuklas-logo.png') }}" alt=""><span>Tuklas</span></a>
                <span class="role-dashboard__divider" aria-hidden="true"></span>
                <span class="role-dashboard__role">{{ __($roleLabel) }}</span>
            </div>
            <nav class="role-dashboard__nav {{ $navigation !== [] ? 'role-dashboard__nav--filled' : '' }}" aria-label="{{ __('Workspace navigation') }}">
                @if ($navigation !== [])
                    @foreach ($navigation as [$label, $routeName])
                        <a href="{{ route($routeName) }}" class="{{ request()->routeIs($routeName) ? 'is-active' : '' }}" @if (request()->routeIs($routeName)) aria-current="page" @endif>{{ __($label) }}</a>
                    @endforeach
                @else
                    <a class="is-active" href="{{ route('dashboard') }}" aria-current="page">{{ __('Overview') }}</a>
                    <a href="{{ route('scanner.index') }}">{{ __('Scanner') }}</a>
                    <a href="{{ route('tesda.index') }}">TESDA</a>
                    @foreach ($focusAreas as $focusArea)
                        <span aria-disabled="true" title="{{ __('This workspace area is planned') }}">{{ __($focusArea) }} <small>{{ __('Planned') }}</small></span>
                    @endforeach
                @endif
            </nav>
            <div class="role-dashboard__right">
                <x-theme-toggle />
                <a class="role-dashboard__account" href="{{ route('profile.show') }}" aria-label="{{ __('Edit profile for :name', ['name' => auth()->user()->name]) }}">
                    <span class="role-dashboard__avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                    <span class="role-dashboard__account-copy">
                        <span>{{ auth()->user()->name }}</span>
                        <small>{{ __('Edit profile') }}</small>
                    </span>
                </a>
            </div>
        </div>
        <header class="role-dashboard__heading">
            <p class="role-dashboard__eyebrow">{{ __($eyebrow) }}</p>
            <h1>{{ __($title) }}</h1>
            <p>{{ __($description) }}</p>
        </header>
        {{ $slot }}
    </div>
</div>
