<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Tuklas') }}</title>
    @include('partials.theme-boot')
    @vite(['resources/css/ui.css', 'resources/js/ui.js'])
    @livewireStyles
</head>
<body class="tk-body">
    @include('partials.sprite')
    <div class="tk-guest-top">
        <button type="button" class="tk-ibtn" data-theme-toggle aria-label="Switch between light and dark mode" title="Light / dark mode">
            <svg class="ic ic-sun"><use href="#i-sun"/></svg><svg class="ic ic-moon"><use href="#i-moon"/></svg>
        </button>
    </div>
    <main class="tk-guest-main">{{ $slot }}</main>
    @livewireScripts
</body>
</html>