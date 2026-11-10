<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Get Tuklas on your phone</title>
    <meta name="description" content="Open Tuklas on Android or iPhone from one simple link.">
    <meta name="theme-color" content="#5b17ff">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="Tuklas">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    @include('partials.theme-boot')
    @vite(['resources/css/landing.css'])
</head>
<body class="app-download-body">
    <header class="app-download-nav">
        <a href="{{ route('home') }}" class="logo" aria-label="Tuklas home">tuklas</a>
        <a href="{{ route('home') }}">Back to Tuklas</a>
    </header>

    <main class="app-download-main">
        <div class="app-download-intro">
            <p class="app-download-eyebrow">TUKLAS MOBILE</p>
            <h1>One link for your phone.</h1>
            <p>Choose your device below. You can always return to this page for the latest Android download or to open Tuklas on iPhone.</p>
        </div>

        <div class="app-download-options">
            <section class="app-download-card">
                <span class="app-download-platform">ANDROID</span>
                <h2>Install the Android app</h2>
                <p>Download the latest Tuklas APK and open it on your phone to install.</p>
                <a class="btn btn-violet" href="{{ url('/downloads/tuklas-mobile.apk') }}" download="tuklas-mobile.apk">Download Android app</a>
                <p class="app-download-note">This download link stays the same when a newer version is published.</p>
            </section>

            <section class="app-download-card">
                <span class="app-download-platform">IPHONE &amp; IPAD</span>
                <h2>Install Tuklas on iPhone</h2>
                <p>Open Tuklas in Safari, tap <strong>Share</strong>, then choose <strong>Add to Home Screen</strong>. It gets a Tuklas icon and opens in its own app-style window.</p>
                <ol class="app-download-steps">
                    <li>Tap <strong>Open Tuklas for iPhone</strong> below in Safari.</li>
                    <li>In Safari, tap the Share button and select <strong>Add to Home Screen</strong>.</li>
                    <li>Tap <strong>Add</strong>. Launch Tuklas from the new Home Screen icon.</li>
                </ol>
                <a class="btn btn-line" href="{{ route('home') }}">Open Tuklas for iPhone</a>
                <p class="app-download-note">This installs the mobile web app. A native iPhone App Store version is not available yet.</p>
            </section>
        </div>

        <p class="app-download-permalink">Save this page: <a href="{{ route('app.download') }}">{{ route('app.download') }}</a></p>
    </main>
</body>
</html>
