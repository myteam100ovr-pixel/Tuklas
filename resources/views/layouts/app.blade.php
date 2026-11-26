<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Tuklas') }}</title>
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16.png') }}">
    @include('partials.theme-boot')
    @vite(['resources/css/ui.css', 'resources/css/app.css', 'resources/js/ui.js'])
    @if (request()->routeIs('scanner.index', 'tuklasbot.index', 'peso.index', 'admin.dashboard', 'admin.recommendations.index', 'trainer.dashboard', 'youth.dashboard'))
        @vite(['resources/js/app.js'])
    @endif
    @livewireStyles
</head>
<body class="tk-body tk-workspace {{ $assessmentRequired ? 'tk-workspace--assessment' : '' }}" data-workspace-user="{{ auth()->id() }}">
    @include('partials.sprite')
    <div class="sr-only" id="tk-navigation-status" role="status" aria-live="polite" hidden></div>

    <a class="tk-skip" href="#main-content">Skip to content</a>
    @unless ($assessmentRequired)
    <aside class="tk-sidebar {{ $isGroupedWorkspace ? 'tk-sidebar--admin' : '' }}">
        <a class="tk-brand" href="{{ route('dashboard') }}" aria-label="Tuklas home"><img class="tk-brand-symbol" src="{{ asset('images/tuklas-logo.png') }}" alt="">Tuklas<span class="tk-brand-dot">.</span></a>
        <p class="tk-nav-caption">WORKSPACE</p>

        <nav class="tk-tabs tk-tabs--workspace" aria-label="Primary">
            @php($previousNavGroup = null)
            @foreach ($navTabs as $tab)
                @if (!$isGroupedWorkspace && $previousNavGroup !== $tab['group'])<p class="tk-nav-caption">{{ $tab['group'] }}</p>@php($previousNavGroup = $tab['group'])@endif
                <a href="{{ $tab['href'] }}" class="tk-tab {{ $tab['active'] ? 'is-active' : '' }}" @if ($tab['active']) aria-current="page" @endif>
                    <svg class="ic tk-tab__icon" aria-hidden="true"><use href="#{{ $tab['icon'] }}"/></svg>
                    <span>{{ $tab['label'] }}</span>
                </a>
            @endforeach
        </nav>

        <div class="tk-sidebar-guide">
            <svg class="ic" aria-hidden="true"><use href="#i-cap"/></svg>
            <strong>A step toward more.</strong>
            <p>Discover training for your next opportunity.</p>
            <a href="{{ route('tesda.index') }}">Explore TESDA <span aria-hidden="true">↗</span></a>
        </div>
        @unless ($isGroupedWorkspace)
            <div class="tk-sidebar-role"><span class="tk-status-dot" aria-hidden="true"></span>{{ $navUser['role'] }}</div>
        @endunless
    </aside>
    @endunless

    <header class="tk-head">
        <div class="tk-context"><span>Workspace</span><span aria-hidden="true">/</span><strong>{{ $navCurrentLabel ?? 'Workspace' }}</strong></div>

        <div class="tk-right">
            <div class="tk-account-identity">
                <span class="tk-account-name">{{ $navUser['name'] }}</span>
                <span class="tk-account-role">{{ $navUser['role'] }}</span>
            </div>
            @if ($assessmentRequired)
                <button type="button" class="tk-ibtn" data-theme-toggle aria-label="Switch between light and dark mode" title="Light / dark mode">
                    <svg class="ic ic-sun"><use href="#i-sun"/></svg><svg class="ic ic-moon"><use href="#i-moon"/></svg>
                </button>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="tk-ibtn" type="submit" aria-label="Log out" title="Log out"><svg class="ic"><use href="#i-logout"/></svg></button>
                </form>
            @else
            <a class="tk-ibtn tk-notification-link" href="{{ route('notifications.index') }}" data-notifications-url="{{ route('notifications.index') }}" aria-label="Notifications{{ $unreadNotificationCount ? ' ('.$unreadNotificationCount.' unread)' : '' }}" title="Notifications">
                <svg class="ic" aria-hidden="true"><use href="#i-list"/></svg>
                @if ($unreadNotificationCount)<span class="tk-notification-count">{{ $unreadNotificationCount }}</span>@endif
            </a>
            <button type="button" class="tk-ibtn" data-theme-toggle aria-label="Switch between light and dark mode" title="Light / dark mode">
                <svg class="ic ic-sun"><use href="#i-sun"/></svg><svg class="ic ic-moon"><use href="#i-moon"/></svg>
            </button>
            @if ($isAdminWorkspace)
                <div class="tk-menu" data-menu>
                    <button type="button" class="tk-avatar" data-menu-btn aria-haspopup="true" aria-expanded="false" aria-label="Account menu for {{ $navUser['name'] }}">
                        @if ($navUser['photo'])<img src="{{ $navUser['photo'] }}" alt="">@else<span>{{ $navUser['initials'] }}</span>@endif
                    </button>
                    <div class="tk-pop" data-menu-pop hidden>
                        <div class="tk-pop-who">
                            <b>{{ $navUser['name'] }}</b>
                            <em class="chip">{{ $navUser['role'] }}</em>
                            <span>{{ $navUser['email'] }}</span>
                        </div>
                        <a class="tk-pop-item" href="{{ route('profile.show') }}"><svg class="ic"><use href="#i-pencil"/></svg>Profile</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="tk-pop-item" type="submit"><svg class="ic"><use href="#i-logout"/></svg>Logout</button>
                        </form>
                    </div>
                </div>
            @else
                <a class="tk-ibtn" href="{{ route('profile.show') }}" aria-label="Edit profile" title="Edit profile"><svg class="ic"><use href="#i-pencil"/></svg></a>
                <div class="tk-menu" data-menu>
                    <button type="button" class="tk-avatar" data-menu-btn aria-haspopup="true" aria-expanded="false" aria-label="Account menu">
                        @if ($navUser['photo'])<img src="{{ $navUser['photo'] }}" alt="">@else<span>{{ $navUser['initials'] }}</span>@endif
                    </button>
                    <div class="tk-pop" data-menu-pop hidden>
                        <div class="tk-pop-who">
                            <b>{{ $navUser['name'] }}</b>
                            <em class="chip">{{ $navUser['role'] }}</em>
                            <span>{{ $navUser['email'] }}</span>
                        </div>
                        <a class="tk-pop-item" href="{{ route('profile.show') }}"><svg class="ic"><use href="#i-pencil"/></svg>Edit profile</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="tk-pop-item" type="submit"><svg class="ic"><use href="#i-logout"/></svg>Log out</button>
                        </form>
                    </div>
                </div>
            @endif
            @endif
        </div>
    </header>

    <main class="tk-main" id="main-content" tabindex="-1">
        @if (!$assessmentRequired && $isGroupedWorkspace && $adminPageTabs)
            <nav class="tk-admin-page-tabs" aria-label="{{ $navCurrentLabel }} pages">
                @foreach ($adminPageTabs as $tab)
                    <a class="tk-admin-page-tab {{ $tab['active'] ? 'is-active' : '' }}" href="{{ $tab['href'] }}" @if ($tab['active']) aria-current="page" @endif>{{ $tab['label'] }}</a>
                @endforeach
            </nav>
        @endif
        {{ $slot }}
    </main>

    @if (!$assessmentRequired && ($isAdminWorkspace || $showFloatingChatButton))
        <a class="tk-admin-chat-button" href="{{ route('tuklasbot.index') }}" aria-label="Open TuklasBot chat">
            <svg class="ic" aria-hidden="true"><use href="#i-chat"/></svg><span>Ask TuklasBot</span>
        </a>
    @endif

    @stack('modals')
    @livewireScripts
</body>
</html>
