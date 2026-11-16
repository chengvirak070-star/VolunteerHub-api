# VolunteerHub — API

Laravel REST API for **VolunteerHub**, a volunteer platform for Cambodia. Organizers post events, volunteers join them, post stories, follow each other, and collect approved volunteer hours.

Used by two React apps: the admin panel (`http://localhost:5173`) and the user web app (`http://localhost:5174`).

**Stack:** Laravel 13 · PostgreSQL (local ServBay or Neon) · Sanctum Bearer tokens · API Resources · Form Requests · Policies

## Run locally

Needs PHP 8.4 with `pdo_pgsql`, Composer, and a PostgreSQL database named `volunteerhub` (UTF8).

```bash
composer install
cp .env.example .env          # then fill in DB_HOST, DB_USERNAME, DB_PASSWORD
php artisan key:generate
php artisan migrate --seed    # tables + demo data
php artisan storage:link
php artisan serve             # http://localhost:8000
```

For a local database, set `DB_HOST=127.0.0.1`, `DB_USERNAME=postgres` and remove `DB_SSLMODE`.

## Demo accounts (password: `password`)

| Role | Email |
|---|---|
| Admin | admin@volunteerhub.test |
| Organizer | greenmekong@volunteerhub.test, readcambodia@volunteerhub.test, healthfriends@volunteerhub.test |
| Volunteer | sokha@volunteerhub.test (+ 19 more) |

## Endpoints so far

Send `Accept: application/json` and `Authorization: Bearer <token>` (except register/login).

| Method | Path | |
|---|---|---|
| POST | /api/auth/register | name, username, email, password, role, organization_name (organizers) |
| POST | /api/auth/login | returns `{ data: { token, user } }` · 5 tries/min |
| POST | /api/auth/logout | |
| GET | /api/auth/me | |
| GET | /api/users/{username} | profile with hours, followers, following |
| GET | /api/users/{username}/followers · /following | paginated |
| PUT | /api/profile | name, bio, avatar (jpg/png/webp, max 5 MB) |
| POST / DELETE | /api/users/{id}/follow | |

## Progress

- [x] Phase 1 — setup, database, models, seeders
- [x] Phase 2 — auth, profiles, follow
- [ ] Phase 3 — events, registrations, attendance, hour logs
- [ ] Phase 4 — posts, likes, comments, hashtags, feed, search
- [ ] Phase 5 — notifications, reports, bug reports
- [ ] Phase 6 — admin endpoints and dashboard
- [ ] Phase 7 — tests and full README
