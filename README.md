# Eureka by Fabtech

Meeting room booking for the Fabtech office: 10 rooms, about 150 people, built with Laravel 11 (PHP 8.2+), Blade views and MySQL.

## What it does

| Who | Can do |
|---|---|
| Employee | Find a free room by date, time, length and group size, book it, invite people (calendar invite by email), repeat weekly for up to 12 weeks, cancel, confirm they are using the room |
| Senior staff | Everything an employee can, plus book senior-only rooms (the Boardroom) |
| Reception | Everything above, book any room for anyone, see all rooms on one timeline, cancel any booking |
| Admin | Everything above, add users, change roles, mark seniors, reset passwords, deactivate people (their future bookings are cancelled), edit rooms |

Other rules:

- No double bookings. The room row is locked while a booking is saved, so two people cannot take the same slot at once.
- Check-in: the organiser gets an email 10 minutes before the start with "Yes, I need it" and "No, release it" buttons. If nobody confirms within 10 minutes of the start, the room is released and the organiser is told.
- Accounts are created by an admin. People sign in with an employee ID and a temporary password, then must set their own.
- Recurring series that end within a week show an "Extend 3 months" prompt in My bookings.
- All emails are written to `storage/logs/laravel.log` until Fabtech's sender address is set up (see Email below).

## Setup (local)

Requirements: PHP 8.2 or newer with `pdo_mysql`, `mbstring`, `intl`, Composer 2, MySQL 8 (or MariaDB 10.6+).

```bash
git clone https://github.com/buildsbyani/eureka-fabtech.git
cd eureka-fabtech
composer install
cp .env.example .env
php artisan key:generate
# create an empty MySQL database called eureka, then set DB_* in .env
php artisan migrate --seed
php artisan serve
```

Open http://localhost:8000 and sign in as `sanjay.desai` (admin), `priya.nair` (reception), `rajesh.iyer` (senior) or `ritika.menon` (employee). The seed password is `Eureka2026` (or the value of `EUREKA_SEED_PASSWORD`). Everyone is asked to set a new password on first sign-in.

Run the tests (uses SQLite in memory):

```bash
php artisan test
```

## Before go-live

1. Replace the placeholder rooms and people in `database/seeders/DatabaseSeeder.php` with Fabtech's real list, or add them in the app under Room setup and Users.
2. Mark which room(s) are senior-only.
3. Set up email (below).
4. Turn on the scheduler (below). Without it, reminders and auto-release do not run.

## Email

Set these in `.env` once Fabtech IT provides the sender account:

```
MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="eureka@<fabtech domain>"
MAIL_FROM_NAME="Eureka"
```

Ask IT to add SPF and DKIM records for the sending domain so invites land in inboxes, not spam. Emails sent: booking confirmation (with calendar invite), invitations, cancellations, check-in reminders, release notices, welcome and password reset.

For 150 users, sending inline is fine. If sending gets slow, set `QUEUE_CONNECTION=database`, run `php artisan queue:table && php artisan migrate`, and keep `php artisan queue:work` running under Supervisor.

## Deploy (cloud)

Any PHP host works (AWS Lightsail or EC2, DigitalOcean, a managed Laravel host).

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

- Point the web server document root at `public/`.
- Set `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://<your domain>` and serve over HTTPS.
- Add the scheduler to cron:

```
* * * * * cd /var/www/eureka && php artisan schedule:run >> /dev/null 2>&1
```

## Settings

In `.env` (see `config/eureka.php`): office hours (`EUREKA_OPEN`, `EUREKA_CLOSE`), reminder and release timing, how many weeks a series can repeat, and how far ahead people can book. The time zone is `APP_TIMEZONE=Asia/Kolkata`.

## Code map

- `app/Services/BookingService.php`: availability, booking with clash check, weekly repeats, cancel, release, emails
- `app/Console/Commands/ProcessCheckIns.php`: reminders and auto-release, every minute
- `app/Http/Controllers`: Home, Rooms, Bookings, Reception timeline, Admin users and rooms
- `app/Support/Clock.php`: office time helpers; `app/Support/Ics.php`: calendar invites
- `resources/views`: Blade screens; `public/css/eureka.css`: the bento design
- `tests/Feature/BookingTest.php`: clash, Boardroom, repeat, cancel and first-login rules

## Phase 2 ideas

Single sign-on, Outlook or Google calendar sync, room displays and QR check-in, door locks, monthly usage reports, waitlist alerts.
