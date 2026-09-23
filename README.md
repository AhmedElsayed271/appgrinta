# Grinta (Laravel Admin + API)

Grinta is a Laravel-based admin dashboard + API backend for managing **football/sports matches**, **competitions**, **teams**, **players**, **clients**, and **notifications**. It includes:

- Full admin dashboard (users, roles, permissions, clients, matches, competitions, etc.)
- Localized UI + route prefixing (English / Arabic)
- Per-client timezone support (match times are converted to the client’s timezone)
- Push notification support (Firebase) for match events and custom notifications
- External football data integration (api-sports) for syncing matches and teams

---

## 🚀 Getting Started (Local Setup)

### Requirements

- PHP 8.2+ (the project is set up for Laravel 12)
- Composer
- MySQL / MariaDB (or compatible database)
- Node.js + npm (optional, for frontend asset compilation)

### Install

```bash
cd /path/to/project
cp .env.example .env
composer install
npm install          # optional (if you want to compile assets)
```

### Environment

Update `.env` with your local database credentials and application URL:

```env
APP_NAME=Grinta
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=grinta
DB_USERNAME=root
DB_PASSWORD=
```

### Database

```bash
php artisan key:generate
php artisan migrate
php artisan db:seed          # optional (no default seeds by default)
```

### Run the app

```bash
php artisan serve
```

Then open: `http://localhost:8000/en/dashboard` (or `/ar/dashboard`).

---

## 🧩 Primary Features

### ✅ Dashboard (Admin)

The admin dashboard supports managing:

- Users / Roles / Permissions
- Clients (with per-client timezone setting)
- Matches (with matches import + status updates)
- Competitions + Teams + Players
- Categories / Posts (with featured posts and bulk operations)
- Countries
- Settings (API credentials, leagues, seasons, etc.)
- Notifications (send in-app / push via Firebase)

**Dashboard routes are localized** and use the prefix: `/{locale}/dashboard` (e.g. `/en/dashboard`).

---

## 📲 Public API (JSON)

A JSON API is exposed under `routes/api.php`, including endpoints for:

- Authentication (login/register/logout)
- Clients (`/client/profile`, `/client/favourite_teams`, etc.)
- Matches (`/matches`, `/matches/{id}`, `/matches/{id}/events`)
- Teams, Competitions, Players, Countries, Posts, Notifications

The API uses `app/Http/Resources/*` for consistent JSON formatting.

---

## 🌍 Timezone Support

Each client has a `timezone` field (stored in the `clients` table). When an authenticated client requests matches, match datetimes are converted to their timezone.

Update a client’s timezone from the dashboard edit screen.

---

## 🔄 External Football Data Sync (API-Sports)

This project includes helpers and routes to sync data from **Football API (api-sports.io)**:

- Import matches/teams/competitions via `GetDataFromFootBallApi` controller
- Update live match status + events from the API

**Settings table** holds the external API key and league/season configuration.

---

## 🔔 Notifications

Notifications are sent via Firebase (using stored `fb_token` values from clients). The admin dashboard can send:

- Match-based notifications (start, half-time, goal, card, end)
- Custom-post notifications
- URL / team / competition notifications

There are also console commands to test/send notifications and maintain event records:

- `php artisan end:match` (trigger end-of-match notifications)
- `php artisan clear:events` (removes auto-generated events)
- `php artisan test:noti` (debug/test notification sending)

---

## 🛠️ Useful Artisan Commands

```bash
php artisan migrate
php artisan migrate:rollback
php artisan route:list
php artisan config:cache
php artisan view:clear
php artisan cache:clear
```

---

## 📦 Project Structure (Key Folders)

- `app/Http/Controllers/Dashboard/` – Admin UI controllers
- `app/Http/Controllers/Api/` – API controllers
- `app/Http/Resources/` – JSON response shaping
- `resources/views/dashboard/` – Dashboard Blade views
- `routes/dashboard/web.php` – Admin route definitions
- `routes/api.php` – Public API routes

---

## 🧪 Notes

- The project uses `mcamara/laravel-localization` for locale routing.
- Frontend assets are based on a Metronic-like theme.

---

## 🏁 License

MIT
