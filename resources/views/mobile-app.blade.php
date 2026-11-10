<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Get Tuklas on your phone</title>
    <meta name="description" content="Open Tuklas on Android or iPhone from one simple link.">
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
                <h2>Open Tuklas on iOS</h2>
                <p>The native iPhone app is not published on the App Store yet. Use Tuklas in Safari and add it to your Home Screen:</p>
                <ol class="app-download-steps">
                    <li>Open Tuklas in Safari.</li>
                    <li>Tap <strong>Share</strong>, then <strong>Add to Home Screen</strong>.</li>
                    <li>Tap <strong>Add</strong> to save the Tuklas shortcut.</li>
                </ol>
                <a class="btn btn-line" href="{{ route('home') }}">Open Tuklas for iPhone</a>
            </section>
        </div>

        <p class="app-download-permalink">Save this page: <a href="{{ route('app.download') }}">{{ route('app.download') }}</a></p>
    </main>
</body>
</html>
