# Tuklas

Tuklas is a youth career guidance and skills development web app for Pangasinan, piloting in the Municipality of Bugallon with TESDA Lingayen trainings.

## Project status

| Available | Planned or in progress |
|---|---|
| Landing page with light and dark themes | |
| Registration, login, password reset, email verification, and two-factor authentication | Expanded career and training management screens |
| Role-based dashboards for PESO, TESDA, training center representatives, trainers, and youth | Automated job-to-qualification matching |
| Direct email/password sign-in and account security | |
| Profile editing and youth guardian details for ages 15 to 17 | |
| Skills assessment and direct messages between active, verified members | |
| Resume and certificate scanner with Gemini analysis (requires an API key) | |
| Gemini career chat (requires an API key) | |
| Flutter app for Android and iOS using the shared Laravel API | |
| Privacy notice and terms pages, with readable web sign-up dialogs | |
| TESDA page with local published programs and links to TESDA's live directory | |
| PESO employer and job management, with recommendations sent to selected career seekers | Automated qualification scoring for job matches |
| TESDA training centers, scholarship notices, course sessions and capacity | |
| Center scoped trainer assignments, trainee applications, and assessment records | |

## Roles and user flows

Tuklas uses role based access. Users sign in through the same web or mobile account; the server applies the permissions for the account's role.

### PESO super administrator

1. Open **People** to create and manage accounts. Assign TESDA administrators and training center representatives to their roles and centers.
2. Open **Employers** to add employers and verify their information.
3. Open **Jobs** to publish or close openings, then recommend a published job to an active career seeker by email. Career seekers see PESO recommendations in the job list.
4. Use the overview and activity pages to monitor accounts and platform activity.

### TESDA training administrator

1. Open **Centers** to maintain PTC and PSAT institutions.
2. Open **Programs** to maintain training course details and decide when a program is published.
3. Open **Trainers** to assign trainers to published programs at their center.
4. Publish scholarship information, create training sessions, review trainee applications, and record assessments.

### Training center representative

1. The account must be assigned to a training center by an administrator.
2. Open **Programs** to maintain that center's course information. TESDA controls publishing.
3. Open **Trainers** to assign trainers to programs belonging to the assigned center.
4. Open **Sessions** to set dates, capacity, and availability. Review applications and manage assessment schedules and results for that center's trainees.

### Trainer

Trainers can manage their permitted program information and teaching assignments. Training center representatives or TESDA administrators manage center staff assignments. Trainer accounts should be associated with a center to be assignable to that center's programs.

### Career seeker (youth)

1. Register, verify the email address, and complete the profile with education, skills, interests, and credentials.
2. Use **Skills assessment**, the resume and certificate scanner, and career chat to support career exploration.
3. Browse published jobs, scholarships, training centers, and future training sessions. Apply for an open session with available capacity.
4. Track applications and assessment records, and message active members or administrators.

Job recommendations are currently selected by PESO staff. Tuklas does not yet calculate or rank job matches automatically from profile qualifications.

## Where the features live

| Feature | Web entry point | Laravel implementation | Flutter implementation |
|---|---|---|---|
| Roles and account management | People / account forms | `app/Enums/Role.php`, `app/Http/Controllers/Admin/UserController.php`, `routes/web.php` | `tuklas_mobile/lib/screens/workspace_view.dart` |
| Employers and job opportunities | Employers, Jobs | `app/Http/Controllers/EmployerController.php`, `app/Http/Controllers/JobOpportunityController.php`, `resources/views/operations/` | `tuklas_mobile/lib/screens/operations_view.dart` |
| Training centers and scholarships | Centers, Scholarships | `app/Http/Controllers/TrainingCenterController.php`, `app/Http/Controllers/ScholarshipController.php` | `tuklas_mobile/lib/screens/operations_view.dart` |
| Programs and trainer assignments | Programs, Trainers | `app/Http/Controllers/Trainer/TrainingProgramController.php`, `app/Http/Controllers/Trainer/CenterTrainerController.php` | `tuklas_mobile/lib/screens/workspace_view.dart`, `tuklas_mobile/lib/screens/operations_view.dart` |
| Sessions and applications | Sessions, Applications | `app/Http/Controllers/TrainingSessionController.php`, `app/Http/Controllers/Trainer/TrainingApplicationController.php` | `tuklas_mobile/lib/screens/operations_view.dart` |
| Assessments | Assessments | `app/Http/Controllers/AssessmentRecordController.php` | `tuklas_mobile/lib/screens/operations_view.dart` |
| Database structure | — | `database/migrations/` | Data is served by the Laravel mobile API |

The same Laravel domain controllers serve browser routes in `routes/web.php` and authenticated mobile API routes in `routes/api.php`. The database migrations create the new employer, job, scholarship, center, session, assessment, and recommendation records. The mobile application calls those API routes; it does not keep a separate copy of this data.

## Requirements

- PHP **8.5.12 or newer**, with `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `hash`, `mbstring`, `openssl`, `pcre`, `pdo`, `session`, `tokenizer`, and `xml`. The default SQLite database also needs `pdo_sqlite`. For MySQL, enable `pdo_mysql` instead.
- Composer 2
- Node.js **20.19+ or 22.12+** and npm (use an active Node.js LTS release)
- Git

This project requires PHP 8.5.12 or newer within PHP 8.x. Vite 8, used by this project, requires Node.js 20.19+ or 22.12+. For updated installation instructions, see the official [Laravel installation guide](https://laravel.com/docs/13.x/installation), [Node.js downloads](https://nodejs.org/en/download/), and [Composer downloads](https://getcomposer.org/download/).

## Install tools by operating system

### Windows

Use PowerShell, Windows Terminal, Git Bash, or WSL. Install PHP 8.5.12+, Composer 2, Node.js LTS, and Git. Laravel Herd or Laragon can provide a local PHP environment; verify the PHP version and extensions from the same terminal you will use for the project. The [PHP for Windows downloads](https://windows.php.net/download/), [Composer Windows installer](https://getcomposer.org/Composer-Setup.exe), and [Node.js downloads](https://nodejs.org/en/download/) are also available.

If you use WSL, install the tools inside your Linux distribution and follow the Linux commands below. Keeping the project in the Linux home directory (rather than under `/mnt/c`) can improve file-watching performance.

### macOS

Install [Homebrew](https://brew.sh/), then install PHP, Composer, Node.js, and Git:

```bash
brew install php composer node git
```

SQLite is the default database and needs no separate server. To use MySQL instead, install and start it with `brew install mysql` and `brew services start mysql`.

### Linux (Ubuntu or Debian)

Install Git, PHP 8.5.12+ and its SQLite extensions, plus the common Laravel PHP extensions:

```bash
sudo apt update
sudo apt install -y git curl unzip sqlite3 composer \
  php-cli php-sqlite3 php-curl php-mbstring php-xml php-zip php-bcmath php-intl
```

Check that `php -v` reports PHP 8.5.12 or newer and that `composer --version` reports Composer 2. Install Node.js LTS from [nodejs.org](https://nodejs.org/en/download/) or a maintained Node version manager if your distribution's package is too old for Vite 8. If your distribution does not provide PHP 8.5.12+, use the [official Laravel installation instructions](https://laravel.com/docs/13.x/installation) for a current PHP setup.

For Fedora, Arch, and other distributions, install the equivalent PHP extensions, SQLite PDO driver, Composer 2, Git, and supported Node.js LTS using that distribution's package manager.

### Check the tools

Open a new terminal after installing them, then run:

```text
php -v
php -m
composer --version
node -v
npm -v
git --version
```

## Get the project running

Run these commands from a terminal. Replace `<repository-url>` with the Git URL for this project.

```text
git clone <repository-url> tuklas
cd tuklas
composer install
npm ci
```

Copy `.env.example` to `.env`:

**Windows PowerShell**

```powershell
Copy-Item .env.example .env
New-Item -ItemType File -Path database\database.sqlite -Force
```

**macOS and Linux**

```bash
cp .env.example .env
touch database/database.sqlite
```

The template uses SQLite by default. In `.env`, set `APP_NAME=Tuklas` and `APP_URL=http://127.0.0.1:8000`, then configure the initial super admin. These three values are read by the database seeder:

```dotenv
TUKLAS_SUPERADMIN_EMAIL=admin@example.com
TUKLAS_SUPERADMIN_NAME="PESO Bugallon"
TUKLAS_SUPERADMIN_PASSWORD="use-a-long-unique-password"
```

Generate the app key, create the database tables and initial admin, create the public storage link, generate the technology-logo partials, and build frontend assets:

```text
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
node scripts/make-tech-logos.mjs
npm run build
```

Start the Laravel server:

```text
php artisan serve
```

Open [http://127.0.0.1:8000](http://127.0.0.1:8000) and sign in with the super admin email and password from `.env`. Youth can register through the site. Trainer accounts are created by a super admin.

### Optional: use MySQL or MariaDB

Install and start MySQL 8+ or MariaDB 10.6+, create a database, then change `.env` to use it. Example database creation command (works in PowerShell, macOS Terminal, and Linux shells when the MySQL client is installed):

```text
mysql -u root -p -e "CREATE DATABASE tuklas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Set these values in `.env`, and make sure the PHP CLI has `pdo_mysql` enabled:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tuklas
DB_USERNAME=root
DB_PASSWORD=your-local-database-password
```

Then run `php artisan migrate --seed`.

## Environment variables

Never commit real secrets. `.env` is local configuration; keep API keys and OAuth secrets on the server.

| Variable | Purpose |
|---|---|
| `APP_URL` | Local site URL; use `http://127.0.0.1:8000` for the commands above |
| `DB_CONNECTION` and `DB_*` | SQLite by default; use the MySQL values above if choosing MySQL |
| `TUKLAS_SUPERADMIN_EMAIL`, `TUKLAS_SUPERADMIN_NAME`, `TUKLAS_SUPERADMIN_PASSWORD` | Initial PESO Bugallon admin created by `php artisan migrate --seed` |
| `GOOGLE_AI_API_KEY` | Enables Gemini resume/certificate scans and career chat; the key stays server-side |
| `GEMINI_MODEL` | Gemini model name; defaults to the value in `.env.example` |
| `MAIL_MAILER` | `phpmailer` for PHPMailer SMTP delivery, `smtp` for Laravel's SMTP transport, or `log` to inspect messages locally |
| `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_SCHEME` | SMTP server settings; the example uses Gmail with TLS on port 587 |
| `MAIL_TIMEOUT` | SMTP connection timeout in seconds; defaults to 15 |
| `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` | Sender address and display name for outgoing mail |

The example `.env` selects PHPMailer and Gmail SMTP on port 587 with TLS. For Gmail, enable 2-Step Verification and create an app password. Set `MAIL_USERNAME` to the Gmail address and `MAIL_PASSWORD` to the app password in the untracked `.env` file. Keep `MAIL_FROM_ADDRESS` the same as the authenticated address. Yahoo and other SMTP providers can also receive mail; change `MAIL_HOST`, `MAIL_PORT`, `MAIL_SCHEME`, username, and password to the provider's SMTP settings. Recipients can use any email service. Never commit SMTP credentials. To use Laravel's built-in SMTP transport, set `MAIL_MAILER=smtp`.

## Daily development

Run the Laravel server and Vite development server in two terminals:

```text
php artisan serve
npm run dev
```

Use `npm run build` to create production frontend assets. Run `php artisan test` when you want to run the project test suite.

## Set up the Flutter mobile app

The mobile app is in `tuklas_mobile/`. Install the Flutter SDK separately from this repository; use the official [Flutter installation guide](https://docs.flutter.dev/install) and choose the instructions for your operating system. Extract the stable SDK to a writable location, add its `bin` directory to your `PATH`, and open a new terminal. The Dart SDK bundled with Flutter must satisfy the constraint in `tuklas_mobile/pubspec.yaml` (Dart 3.13.4 or newer in the 3.x series).

For Android development, install [Android Studio](https://developer.android.com/studio), including the Android SDK, command-line tools, and an emulator. Accept the Android SDK licenses and check the setup:

```text
flutter doctor --android-licenses
flutter doctor -v
```

For iOS development, use macOS with [Xcode](https://developer.apple.com/xcode/) installed and configured. Flutter's [Android setup guide](https://docs.flutter.dev/platform-integration/android/setup) and [iOS setup guide](https://docs.flutter.dev/platform-integration/ios/setup) list the platform-specific requirements.

From the repository root, fetch the app dependencies and launch it on a running emulator or connected device:

```text
cd tuklas_mobile
flutter pub get
flutter devices
flutter run
```

The Laravel backend must also be running with its database initialized and migrations applied. The app's default API address is `http://10.0.2.2:8000/api/mobile`, which reaches the host computer from an Android emulator. For an iOS simulator, pass its API address when launching:

```text
flutter run --dart-define=API_BASE=http://127.0.0.1:8000/api/mobile
```

For a physical phone, connect the phone and computer to the same network, start Laravel so it accepts LAN connections, and substitute the computer's LAN IP:

```text
# In the Laravel project root, in a separate terminal:
php artisan serve --host=0.0.0.0

# In tuklas_mobile:
flutter run --dart-define=API_BASE=http://192.168.1.20:8000/api/mobile
```

Replace `192.168.1.20` with the computer's actual LAN IP and allow the server through the computer's firewall if prompted. `API_BASE` can also be set to another reachable API URL without editing the app source.

### Build an APK for Amazon

Build from `tuklas_mobile/` and use the deployed server's HTTPS API URL. Do not use HTTP for an app that sends passwords or bearer tokens.

```text
flutter build apk --release --dart-define=API_BASE=https://YOUR_DOMAIN/api/mobile
```

The APK is written to `build/app/outputs/flutter-apk/app-release.apk`. Replace `YOUR_DOMAIN` with the HTTPS host configured for Laravel. If using an IP address, verify that its HTTPS certificate validates for that IP. The Android release currently uses the Gradle debug signing key; configure a private release key before distributing through an app store.

Before using messaging in the APK, deploy the matching Laravel commit and apply migrations on the server:

```bash
cd /var/www/tuklas
git pull origin main
php artisan migrate --force
php artisan optimize:clear
```

The app includes registration and verification, role workspaces, account security, notifications, direct messages, career chat, scan history, PESO recommendations, and TESDA training plans. Direct messages require the `direct_messages` migration and mobile messaging API.

## Troubleshooting

| Problem | Fix |
|---|---|
| Composer reports a missing PHP extension | Enable the required extension for the PHP CLI, then check `php -m` and run `composer check-platform-reqs`. Windows PHP tools can use a different `php.ini`; check `php --ini`. |
| `could not find driver` with SQLite | Enable `pdo_sqlite` in the PHP CLI and create `database/database.sqlite` as shown above. |
| `could not find driver` with MySQL | Enable `pdo_mysql` in the PHP CLI, then run `php artisan config:clear`. |
| `Vite manifest not found` or CSS/JS does not load | Run `npm ci` and `npm run build`, or leave `npm run dev` running in a second terminal. |
| The `npm` command fails but Node works | After dependencies are installed, run `node ./node_modules/vite/bin/vite.js build` in PowerShell, macOS, or Linux. |
| `View [partials.tech-logos] not found` or a generated logo partial is missing | Run `node scripts/make-tech-logos.mjs`. |
| `SQLSTATE ... Access denied` | Check the MySQL username, password, database name, and that the server is running. |
| `419 Page Expired` | Run `php artisan optimize:clear`, then reload. |
| Email does not arrive in development | With `MAIL_MAILER=log`, inspect `storage/logs/laravel.log`. With SMTP, check the provider host, port, TLS mode, sender address, username, and app password. |
| Google or Facebook sign-in fails | Check the OAuth client credentials and callback URL against the provider console and `APP_URL`. |
| Uploaded profile photo is missing | Run `php artisan storage:link`. |

## Project structure

```text
app/Enums/Role.php
app/Http/Controllers/                 Dashboards, scanner, profile, TESDA, messaging, and chat
app/Http/Middleware/EnsureRole.php
app/Models/                           Users, scans, training programs, career paths
app/Services/                         Gemini document scanner and career assistant
app/Support/ProfileCompletion.php
app/View/Composers/NavComposer.php
database/migrations/                  Authentication, youth profiles, scans, catalogs, messages
database/seeders/DatabaseSeeder.php
resources/views/                      Blade pages, layouts, dashboard, scanner, TESDA
resources/css/                        Landing, theme, and signed-in UI styles
resources/js/                          Landing, theme, dashboard, scanner, and chat scripts
scripts/make-tech-logos.mjs            Generates the technology logo and function-chip partials
```

## Notes

- Career and document-scan AI output is guidance, not a guarantee of jobs, admission, or training slots.
- The local Tuklas training catalog contains programs published by authorized staff. For broader Pangasinan listings, the TESDA page links to TESDA's registered-provider directory.
- The setup guide covers Windows, macOS, and Linux. The current project checkout has been run in its available development environment; the other operating systems have not been independently tested here.
- Before real users register, have the privacy notice reviewed under the Data Privacy Act.

Capstone project of Tour de Force, Universidad de Dagupan.
