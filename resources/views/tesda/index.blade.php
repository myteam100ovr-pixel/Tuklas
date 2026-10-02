@auth
    <x-app-layout>
        @include('tesda.content')
    </x-app-layout>
@else
    <!DOCTYPE html>
    <html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <title>TESDA training and services | {{ config('app.name', 'Tuklas') }}</title>
        <meta name="description" content="Search TESDA NC I to IV registered training programs in Pangasinan and browse official learning, scholarship, and certification resources.">
        @include('partials.theme-boot')
        @vite('resources/css/landing.css')
    </head>
    <body class="tesda-body">
        <header class="hdr" id="hdr">
            <a href="{{ route('home') }}" class="logo" aria-label="Tuklas home">tuklas</a>
            <nav class="pill" aria-label="Primary">
                <a href="{{ route('home') }}#features">Features</a>
                <a href="{{ route('scanner.index') }}">Scanner</a>
                <a href="{{ route('tesda.index') }}" aria-current="page">TESDA</a>
            </nav>
            <div class="hdr-actions">
                <a class="login" href="{{ route('login') }}">Log in</a>
                <a class="btn btn-black" href="{{ route('register') }}">Get started</a>
            </div>
        </header>

        @include('tesda.content')
    </body>
    </html>
@endauth
