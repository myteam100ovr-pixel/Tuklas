<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Tuklas') }}</title>
    @include('partials.theme-boot')
    @vite(['resources/css/ui.css', 'resources/js/ui.js'])
    @if (request()->routeIs('scanner.index'))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    @livewireStyles
</head>
<body class="tk-body">
    @include('partials.sprite')

    <header class="tk-head">
        <a class="tk-brand" href="{{ route('dashboard') }}" aria-label="Tuklas home">tuklas</a>

        <nav class="tk-tabs" aria-label="Primary">
            @foreach ($navTabs as $tab)
                <a href="{{ $tab['href'] }}" class="tk-tab {{ $tab['active'] ? 'is-active' : '' }}" @if ($tab['active']) aria-current="page" @endif>{{ $tab['label'] }}</a>
            @endforeach
        </nav>

        <div class="tk-right">
            <button type="button" class="tk-ibtn" data-theme-toggle aria-label="Switch between light and dark mode" title="Light / dark mode">
                <svg class="ic ic-sun"><use href="#i-sun"/></svg><svg class="ic ic-moon"><use href="#i-moon"/></svg>
            </button>
            <a class="tk-ibtn" href="{{ route('profile.show') }}" aria-label="Edit profile" title="Edit profile"><svg class="ic"><use href="#i-pencil"/></svg></a>

            <div class="tk-menu" data-menu>
                <button type="button" class="tk-avatar" data-menu-btn aria-haspopup="true" aria-expanded="false" aria-label="Account menu">
                    @if ($navUser['photo'])<img src="{{ $navUser['photo'] }}" alt="">@else<span>{{ $navUser['initials'] }}</span>@endif
                </button>
                <div class="tk-pop" data-menu-pop hidden>
                    <div class="tk-pop-who">
                        <b>{{ $navUser['name'] }}</b>
                        <span>{{ $navUser['email'] }}</span>
                        <em class="chip">{{ $navUser['role'] }}</em>
                    </div>
                    <a class="tk-pop-item" href="{{ route('profile.show') }}"><svg class="ic"><use href="#i-pencil"/></svg>Edit profile</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="tk-pop-item" type="submit"><svg class="ic"><use href="#i-logout"/></svg>Log out</button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <main class="tk-main">{{ $slot }}</main>

    @stack('modals')
    @livewireScripts
</body>
</html>
