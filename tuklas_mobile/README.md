# Tuklas Mobile

Tuklas Mobile uses the Laravel application's API and database. Sign-in is kept
securely on the device; when the app opens, it validates the saved session with
Laravel and returns to the previous account if the token is still valid.

Mobile and website accounts share Laravel's `users` table. Create an account
from either client, verify the email address using the link sent to that
address, then sign in on mobile or the website with the same email and
password. Mobile sign-up sends the same account details through Laravel's
Fortify registration action as website sign-up.

The app also includes role-based workspaces, notifications, account security,
TuklasBot, TESDA training plans, PESO recommendations, scan history, and direct
messages between active, verified members. Administrators appear first in the
message recipient list. Messages use the same private Laravel conversation
storage as the website.

## Run from VS Code

1. Start the MySQL service used by the Laravel project.
2. Start an Android emulator.
3. Open the repository root in VS Code, select the `Tuklas Mobile (Android
   emulator)` launch configuration, and press **F5**.

The launch configuration checks that Laravel can reach its configured database
and starts the API at `http://0.0.0.0:8000` if a healthy API is not already
running. It also forwards port 8000 over Android Debug Bridge (ADB), so either
an attached Android phone or emulator can connect through
`http://127.0.0.1:8000/api/mobile`.

## Run from a terminal

Start MySQL and Laravel from the repository root:

```powershell
php artisan migrate:status
php artisan serve --host=0.0.0.0 --port=8000
```

In another terminal, start the mobile app:

```powershell
$adb = "$env:LOCALAPPDATA\Android\Sdk\platform-tools\adb.exe"
& $adb reverse tcp:8000 tcp:8000
Set-Location tuklas_mobile
flutter pub get
flutter run --dart-define=API_BASE=http://127.0.0.1:8000/api/mobile
```

For a physical phone without USB debugging, connect both devices to the same
network and pass the computer's LAN address:

```powershell
flutter run --dart-define=API_BASE=http://YOUR_COMPUTER_LAN_IP:8000/api/mobile
```

Allow the development server through Windows Firewall on a trusted private
network when prompted. Do not expose the development server to public networks.

If the server is unavailable during startup, the app keeps the saved token and
shows **Retry connection** instead of treating the session as signed out.
Signing in again is only needed when the server says that the saved token is
invalid or expired.

## Build for the deployed server

The API URL is selected at build time using `API_BASE`. Use the HTTPS address of
the deployed Laravel application:

```powershell
flutter build apk --release --dart-define=API_BASE=https://YOUR_DOMAIN/api/mobile
```

The resulting APK is at
`build/app/outputs/flutter-apk/app-release.apk`. Install it on a device to try
the app. The current Android release build uses the debug signing key; configure
a private release key before app store distribution.

Before messaging can work, deploy the Laravel code that provides the mobile
messages endpoints and run `php artisan migrate --force` on the server. This
creates the `direct_messages` table. The app uses the HTTPS API URL for
sign-in, token authentication, and messaging; do not build a credentialed app
with an HTTP API URL.
