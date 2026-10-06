# Tuklas Mobile

Tuklas Mobile uses the Laravel application's API and database. Sign-in is kept
securely on the device; when the app opens, it validates the saved session with
Laravel and returns to the previous account if the token is still valid.

Mobile and website accounts share Laravel's `users` table. Create an account
from either client, verify the email address using the link sent to that
address, then sign in on mobile or the website with the same email and
password. Mobile sign-up sends the same account details through Laravel's
Fortify registration action as website sign-up.

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
