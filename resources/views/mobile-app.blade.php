<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Get Tuklas on your phone</title>
    <meta name="description" content="Download the latest Tuklas Android app. iOS app coming soon.">
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
            <p>Download the latest Android app here. The iOS app is coming soon.</p>
        </div>

        <div class="app-download-options">
            <section class="app-download-card">
                <span class="app-download-platform">ANDROID</span>
                <h2>Install the Android app</h2>
                <p>Get the latest Tuklas APK for Android. Current version: <strong id="android-app-version">Checking…</strong></p>
                <a class="btn btn-violet" id="android-app-download" href="{{ url('/app-updates/latest.json') }}">Download Android app</a>
                <p class="app-download-note" id="android-app-status" role="status">Loading the latest release information.</p>
                <div class="app-download-notes" id="android-app-notes" hidden>
                    <h3>What’s new</h3>
                    <p id="android-app-patch-notes"></p>
                </div>
            </section>

            <section class="app-download-card">
                <span class="app-download-platform">IPHONE &amp; IPAD</span>
                <h2>iOS app coming soon</h2>
                <p>The Tuklas app for iPhone and iPad is not available for download yet.</p>
                <p class="app-download-note">Please check this page again for availability.</p>
            </section>
        </div>

        <p class="app-download-permalink">Save this page: <a href="{{ route('app.download') }}">{{ route('app.download') }}</a></p>
    </main>
    <script>
        fetch('{{ url('/app-updates/latest.json') }}', { cache: 'no-store' })
            .then(response => {
                if (!response.ok) throw new Error('Release information is unavailable.');
                return response.json();
            })
            .then(release => {
                const link = new URL(release.apk_url, window.location.origin);
                if (link.origin !== window.location.origin || !link.pathname.startsWith('/app-updates/')) {
                    throw new Error('The download link is invalid.');
                }
                document.getElementById('android-app-version').textContent = release.version_name;
                document.getElementById('android-app-download').href = link.href;
                document.getElementById('android-app-status').textContent = 'Latest Android version is ready to download.';
                if (release.patch_notes) {
                    document.getElementById('android-app-patch-notes').textContent = release.patch_notes;
                    document.getElementById('android-app-notes').hidden = false;
                }
            })
            .catch(() => {
                document.getElementById('android-app-version').textContent = 'Unavailable';
                document.getElementById('android-app-status').textContent = 'The latest Android release is not published yet.';
            });
    </script>
</body>
</html>
