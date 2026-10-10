# Deployment Guide

## Production requirements

- PHP 8.2 with PDO MySQL, OpenSSL, Fileinfo, Ctype, DOM/XML, Tokenizer, Session, JSON, and Mbstring support. Enable `intl` as well; Laravel's database inspection commands require it.
- MySQL 8 or a current MariaDB release using InnoDB and `utf8mb4`.
- Apache with `mod_rewrite` or Nginx.
- Composer 2. Node.js is needed only on the build machine because the compiled `public/build` assets can be deployed.
- HTTPS for every public installation.

The web-server document root must be the application's `public` directory, never the repository root. The `.env`, source, `storage`, database backups, and uploaded originals must not be directly web-accessible.

## Environment

Copy `.env.example` to `.env` on the server and set unique production values. At minimum:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://inventory.example.com
APP_TIMEZONE=UTC
BUSINESS_TIMEZONE=Africa/Lagos
DB_CONNECTION=mysql
DB_TIMEZONE=+00:00
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
BUSINESS_LOGO_DISK=uploads
PRODUCT_IMAGE_DISK=uploads
QUEUE_CONNECTION=sync
```

Generate `APP_KEY` once with `php artisan key:generate`. Back it up securely and do not rotate it casually. Store the production `.env` outside version control and restrict it to the application account.

Uploaded logos and product images use `storage/app/uploads`, outside the web root. They are returned only by controlled image routes with an image MIME allow-list and `X-Content-Type-Options: nosniff`. Keep this directory non-executable. The legacy `storage/app/public/.htaccess` also denies script-like extensions for Apache installations. For Nginx, deny script execution under any storage alias explicitly:

```nginx
location ~* ^/storage/.*\.(php|phtml|phar|cgi|pl|py|sh)$ { deny all; }
```

## First deployment

Run these commands from the release directory:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
npm ci
npm run build
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
php artisan optimize
```

If assets were built in CI, deploy `public/build` and omit Node/npm on the server. Ensure the web-server user can write `storage`, `storage/app/uploads`, and `bootstrap/cache`. A public storage symlink is not required for protected application images.

Complete the one-time owner setup at `/setup` over a private or local connection before exposing the site publicly. The owner chooses credentials known only to them. As an alternative, run `php artisan app:create-administrator` interactively. Both methods close permanently once an administrator exists.

No public registration route exists. No Redis instance, queue worker, paid printing service, or third-party messaging service is required. Core notifications are synchronous and database-backed.

## Apache and XAMPP

Point the virtual host at `<project>/public`, enable `mod_rewrite`, allow `.htaccess`, and use PHP 8.2. For local Artisan serving, set `APP_URL=http://127.0.0.1:8000` and run `php artisan serve`. Start MySQL in XAMPP and keep application/database timestamps in UTC.

If `php artisan db:table` reports that `intl` is missing, enable `extension=intl` in the PHP 8.2 `php.ini` used by the command line and Apache, then restart Apache. Confirm the active configuration with `php --ini`.

## Shared hosting

Prefer hosting that lets the domain document root point directly to `public`. If that is impossible, place the application above `public_html` and expose only the contents of `public`; update `public/index.php` paths carefully. Never copy `.env`, `vendor` metadata, storage originals, tests, or source directories into a publicly browsable location.

Run migrations from SSH or the hosting control panel's secure terminal. Do not expose an HTTP migration, setup, or maintenance script.

## Release verification

Before switching traffic:

```bash
php artisan about
php artisan migrate:status
php artisan route:list -v
composer check-platform-reqs
php artisan optimize
```

Then verify HTTPS login, owner/admin and cashier access, one image upload, one test stock workflow, POS checkout, receipt printing, CSV export, and the responsive viewport checklist in `docs/TESTING.md`.

## Backups and recovery

Back up the MySQL database and `storage/app/uploads` together. Keep encrypted, versioned off-server copies and periodically perform a restore drill. Before a release, take a backup, enable maintenance mode if required, deploy code/assets, run migrations, clear/rebuild caches, verify health, and then reopen traffic.

Application records are audit-oriented. Do not repair financial or stock history with direct SQL. Use the provided reversal and adjustment workflows. For release rollback, restore the matching code, database, and upload snapshot; review migration `down()` behavior before any rollback on a live database.

