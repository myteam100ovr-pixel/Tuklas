# Tuklas

Tuklas is a youth career guidance and skills development web app for Pangasinan, piloting in the Municipality of Bugallon with TESDA Lingayen trainings.

## Project status

| Available | Planned or in progress |
|---|---|
| Landing page with light and dark themes | Skills assessment |
| Registration, login, password reset, email verification, and two-factor authentication | Expanded career and training management screens |
| Role-based dashboards for PESO Bugallon, TESDA trainers, and youth | More career and training recommendations |
| Google and Facebook sign-in (requires provider credentials) | Flutter mobile app |
| Profile editing and youth guardian details for ages 15 to 17 | |
| Resume and certificate scanner with Gemini analysis (requires an API key) | |
| Gemini career chat (requires an API key) | |
| TESDA page with local published programs and links to TESDA's live directory | |

## Requirements

- PHP **8.3 or newer**, with `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `hash`, `mbstring`, `openssl`, `pcre`, `pdo`, `session`, `tokenizer`, and `xml`. The default SQLite database also needs `pdo_sqlite`. For MySQL, enable `pdo_mysql` instead.
- Composer 2
- Node.js **20.19+ or 22.12+** and npm (use an active Node.js LTS release)
- Git

Laravel 13 requires PHP 8.3 or newer. Vite 8, used by this project, requires Node.js 20.19+ or 22.12+. For updated installation instructions, see the official [Laravel installation guide](https://laravel.com/docs/13.x/installation), [Node.js downloads](https://nodejs.org/en/download/), and [Composer downloads](https://getcomposer.org/download/).

## Install tools by operating system

### Windows

Use PowerShell, Windows Terminal, Git Bash, or WSL. Install PHP 8.3+, Composer 2, Node.js LTS, and Git. Laravel Herd or Laragon can provide a local PHP environment; verify the PHP version and extensions from the same terminal you will use for the project. The [PHP for Windows downloads](https://windows.php.net/download/), [Composer Windows installer](https://getcomposer.org/Composer-Setup.exe), and [Node.js downloads](https://nodejs.org/en/download/) are also available.

If you use WSL, install the tools inside your Linux distribution and follow the Linux commands below. Keeping the project in the Linux home directory (rather than under `/mnt/c`) can improve file-watching performance.

### macOS

Install [Homebrew](https://brew.sh/), then install PHP, Composer, Node.js, and Git:

```bash
brew install php composer node git
```

SQLite is the default database and needs no separate server. To use MySQL instead, install and start it with `brew install mysql` and `brew services start mysql`.

### Linux (Ubuntu or Debian)

Install Git, PHP 8.3+ and its SQLite extensions, plus the common Laravel PHP extensions:

```bash
sudo apt update
sudo apt install -y git curl unzip sqlite3 composer \
  php-cli php-sqlite3 php-curl php-mbstring php-xml php-zip php-bcmath php-intl
```

Check that `php -v` reports PHP 8.3 or newer and that `composer --version` reports Composer 2. Install Node.js LTS from [nodejs.org](https://nodejs.org/en/download/) or a maintained Node version manager if your distribution's package is too old for Vite 8. If your distribution does not provide PHP 8.3+, use the [official Laravel installation instructions](https://laravel.com/docs/13.x/installation) for a current PHP setup.

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
| `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` | Optional Google sign-in credentials |
| `FACEBOOK_APP_ID`, `FACEBOOK_APP_SECRET` | Optional Facebook sign-in credentials |
| `GOOGLE_REDIRECT_URI`, `FACEBOOK_REDIRECT_URI` | OAuth callback addresses; use `{APP_URL}/auth/google/callback` and `{APP_URL}/auth/facebook/callback` |
| `MAIL_MAILER` | `log` for local development; messages are written to `storage/logs/laravel.log` |
| `RESEND_API_KEY` | Optional Resend email delivery key for a verified sending domain |

## Daily development

Run the Laravel server and Vite development server in two terminals:

```text
php artisan serve
npm run dev
```

Use `npm run build` to create production frontend assets. Run `php artisan test` when you want to run the project test suite.

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
| Email does not arrive in development | With `MAIL_MAILER=log`, inspect `storage/logs/laravel.log`. |
| Google or Facebook sign-in fails | Check the OAuth client credentials and callback URL against the provider console and `APP_URL`. |
| Uploaded profile photo is missing | Run `php artisan storage:link`. |

## Project structure

```text
app/Enums/Role.php
app/Http/Controllers/                 Dashboards, scanner, profile, TESDA, and chat
app/Http/Middleware/EnsureRole.php
app/Models/                           Users, scans, training programs, career paths
app/Services/                         Gemini document scanner and career assistant
app/Support/ProfileCompletion.php
app/View/Composers/NavComposer.php
database/migrations/                  Authentication, youth profiles, scans, catalogs
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
