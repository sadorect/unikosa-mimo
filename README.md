# UNIKOSA Alumni Platform

A global alumni platform for UNIKOSA (a Nigerian secondary school alumni association). Members are spread across multiple continents, organized by graduating set/year and diaspora chapters.

## Tech Stack

- **Backend:** Laravel 12 + PHP 8.2
- **Frontend:** Vue 3 + Inertia.js
- **Database:** PostgreSQL 16
- **Cache/Queue/Sessions:** Redis 7
- **File Storage:** Cloudflare R2 (S3-compatible)
- **Admin Panel:** Filament v3
- **Permissions:** spatie/laravel-permission
- **Payments:** Paystack (NGN) + Stripe (International)
- **Containerization:** Docker + Docker Compose

## Quick Start (Docker)

```bash
# Clone the repo
git clone <repo-url>
cd unikosa-mimo

# Copy environment file
cp .env.example .env

# Build and start containers
docker compose up -d

# Install dependencies
docker compose exec app composer install
docker compose exec app npm install

# Generate app key
docker compose exec app php artisan key:generate

# Run migrations and seeders
docker compose exec app php artisan migrate --seed

# Build frontend assets
docker compose exec app npm run build
```

The app will be available at `http://localhost`.

## Services

| Service | Port | Description |
|---------|------|-------------|
| nginx | 80 | Web server / reverse proxy |
| app | 9000 | PHP-FPM (Laravel) |
| postgres | 5432 | PostgreSQL 16 |
| redis | 6379 | Redis 7 |
| queue | - | Laravel queue worker |
| scheduler | - | Laravel task scheduler |

## Default Credentials

After seeding, the following users are created:

| Role | Email | Password |
|------|-------|----------|
| Super Admin | admin@unikosa.org | password |
| Member | john@example.com | password |

## Roles & Permissions

- **Super Admin** — Full system access
- **Set Representative** — Manage own set's members/events
- **Chapter Head** — Manage own chapter's events
- **Content Moderator** — Approve forum posts, blog submissions
- **Finance Admin** — View/manage dues & campaigns
- **Member** — Default role

## Environment Variables

Key variables in `.env`:

```env
# Database
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=unikosa
DB_USERNAME=unikosa
DB_PASSWORD=secret

# Redis
REDIS_HOST=redis
REDIS_PORT=6379

# File Storage (Cloudflare R2)
R2_ENDPOINT=
R2_ACCESS_KEY_ID=
R2_SECRET_ACCESS_KEY=
R2_BUCKET=
R2_URL=

# Payments
PAYSTACK_KEY=
PAYSTACK_SECRET=
STRIPE_KEY=
STRIPE_SECRET=
```

## Modules

1. **Authentication & Profiles** — Registration with approval workflow
2. **Member Directory** — Searchable/filterable by set, chapter, profession
3. **Sets & Chapters** — Graduating year groups and geographic chapters
4. **Financials** — Dues, campaigns, Paystack/Stripe integration
5. **Fellowship** — Forum, photo gallery, announcements
6. **Events** — Calendar, RSVP, ticketing
7. **Jobs & Career** — Job board, referrals
8. **Business Directory** — Alumni-owned businesses
9. **Blog/News** — Admin + contributor publishing
10. **Notifications** — In-app and email

## Admin Panel

Access the admin panel at `/admin`. Login with the admin credentials above.

## License

Proprietary — All rights reserved.
