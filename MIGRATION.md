# Pilot Hotel Laravel migration

This folder is the Laravel + Tailwind version of the original static Pilot Hotel prototype.

## What changed

- Static HTML pages became Blade templates in `resources/views`.
- The browser `localStorage` reservation store was replaced with an Eloquent `Reservation` model and a database table.
- Reservation creation now uses a Laravel `POST` route, server-side validation, CSRF protection, and redirects with a success message.
- Reservation search and status filtering are available through query parameters and a small client-side enhancement.
- The separate dashboard and reservations CSS files were replaced with Tailwind CSS in `resources/css/app.css`.
- Vite compiles Tailwind and the small modal/filter script in `resources/js/app.js`.
- The original static files remain in the parent folder for comparison; this Laravel app is in `pilot-hotel-laravel`.

## Requirements

- PHP 8.2+
- Composer
- Node.js and npm
- SQLite, MySQL, or another Laravel-supported database

## Run locally

From the workspace root:

```powershell
cd pilot-hotel-laravel
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
npm run dev
php artisan serve
```

Open `http://127.0.0.1:8000`. In a second terminal, `npm run dev` watches and rebuilds Tailwind assets. For a production asset build, run `npm run build`.

The current scaffold uses SQLite by default. Create `database/database.sqlite` if it does not exist, or change the `DB_*` values in `.env` for MySQL/PostgreSQL.

## Routes

| Method | URL | Purpose |
| --- | --- | --- |
| GET | `/` | Staff portal entry screen |
| GET | `/dashboard` | Dashboard statistics and recent reservations |
| GET | `/reservations` | Searchable and filterable reservation list |
| POST | `/reservations` | Validate and create a reservation |
| PUT/PATCH | `/reservations/{reservation}` | Validate and update a reservation |

Authentication is intentionally not enabled yet; the original login screen was a visual prototype. Add Laravel Breeze or Fortify before using this with real staff accounts.

## Data model

`reservations` stores the reservation code, guest contact details, room, check-in/check-out dates, status, amount, guest counts, notes, and timestamps. The migration is `database/migrations/2026_09_13_000003_create_reservations_table.php`. Seed records are in `database/seeders/ReservationSeeder.php`.

## Old-to-new map

| Original file | Laravel replacement |
| --- | --- |
| `index.html` | `resources/views/auth/login.blade.php` |
| `dashboard.html` | `resources/views/dashboard.blade.php` |
| `reservations.html` | `resources/views/reservations/index.blade.php` |
| `reservations-data.js` | `app/Models/Reservation.php`, migration, and seeder |
| `reservationsscript.js` | `app/Http/Controllers/ReservationController.php` plus `resources/js/app.js` |
| `style.css`, `dashboard.css.css`, `reservations-page.css` | `resources/css/app.css` |

## Next recommended steps

1. Add authentication and authorization for front-desk roles.
2. Add a `rooms` table and validate room availability before creating a reservation.
3. Replace the placeholder occupancy calculation with a query over rooms and active stays.
4. Add feature tests for reservation validation, filtering, and updates.
5. Add edit UI fields that submit to the existing update route.
