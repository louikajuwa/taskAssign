# TaskAssign

A test-plan management and multi-stage assignment system built on Laravel 13 (PHP 8.4-FPM, MySQL 8, Redis 7, Nginx), with a Tailwind + jQuery frontend, queued SendGrid email, and in-app notifications — fully containerized with Docker Compose.

The multi-stage workflow is preserved exactly: **Developer → Generic Tester → Sandbox → Alpha → Beta → Live Tester**, including the "go back" cascade, the "go live" flag, and Live-Tester completion closing the plan.

**What's inside**

| Area | Implementation |
|------|----------------|
| Backend | Laravel 13, RESTful controllers, Eloquent models, service classes |
| Frontend | Blade + Tailwind (CDN) + jQuery + SweetAlert2 + Quill |
| Auth | Session-based login, **admin-only** account creation (no public registration) |
| Email | SendGrid HTTP API via cURL (`SendGridMailService`), sent through a **Redis queue** |
| Notifications | In-app bell + dropdown + full page (always created); email is opt-in per user |
| File uploads | Laravel `Storage` (multipart) |

---

## Prerequisites

- **Docker Desktop** running (or Docker Engine + the Docker Compose plugin). Nothing else is needed on the host — PHP, Composer, MySQL and Redis all run in containers.

---

## Run

From the project root:

```bash
# 1. Copy the environment file (edit values first if you want — see Environment below)
cp .env.example .env        # PowerShell: Copy-Item .env.example .env

# 2. Build the images and start all services
docker compose up -d --build

# 3. Create the database schema and the default admin account
docker compose exec app php artisan migrate --seed

# 4. Link the public storage folder (so uploaded files are reachable)
docker compose exec app php artisan storage:link
```

Then open **http://localhost:8087**.

> **APP_KEY**: `.env.example` ships with an empty `APP_KEY`. Generate one with
> `docker compose exec app php artisan key:generate` if it is not already set.

> **Port note**: browsers block port 87 (`ERR_UNSAFE_PORT`), so the app is published on
> **8087** (host) → 80 (container). To use a different host port, edit the `web` service
> `ports` in `docker-compose.yml` and set `APP_URL` in `.env` to match.

---

## Services

All services join the `taskassign` bridge network. Images/builds and ports are taken directly from `docker-compose.yml`.

| Service | Image / Build | Purpose | Port (host:container) | URL |
|---------|---------------|---------|-----------------------|-----|
| `web` | `nginx:alpine` | Web server (reverse-proxies to PHP-FPM) | `8087:80` | http://localhost:8087 |
| `app` | build `./docker/php` (`php:8.4-fpm`) | Laravel app (PHP-FPM); fixes storage perms then runs `php-fpm` | — (internal) | — |
| `db` | `mysql:8.0` | MySQL 8 database | `127.0.0.1:3387:3306` (host-only) | — |
| `redis` | `redis:7-alpine` | Cache, session-adjacent queue + email dedup store | `127.0.0.1:6387:6379` (host-only) | — |
| `queue` | build `./docker/php` | Queue worker: `queue:work redis --sleep=3 --tries=3 --backoff=10` | — (internal) | — |
| `scheduler` | build `./docker/php` | Laravel scheduler (`schedule:work`) — nightly `emails:prune-logs` | — (internal) | — |
| `phpmyadmin` | `phpmyadmin:latest` | Web DB UI (server pre-filled to `db`) | `127.0.0.1:8088:80` (host-only) | http://localhost:8088 |

The `app`, `queue`, and `scheduler` services all build from the same `./docker/php/Dockerfile` (`php:8.4-fpm` with `pdo_mysql`, `mbstring`, `exif`, `pcntl`, `bcmath`, `gd`, `zip`, and the `redis` PECL extension, plus Composer).

---

## Environment

Copy `.env.example` to `.env`. All values have working defaults for local Docker use; the key variables:

| Variable | Default | Notes |
|----------|---------|-------|
| `APP_KEY` | *(empty)* | Run `php artisan key:generate` if unset |
| `APP_URL` | `http://localhost:8087` | Must match the published web port |
| `DB_HOST` | `db` | Compose service name (do not change for Docker) |
| `DB_PORT` | `3306` | Container-internal port |
| `DB_DATABASE` | `taskassign` | Also read by the `db` container as `MYSQL_DATABASE` |
| `DB_USERNAME` | `taskassign` | Read by the `db` container as `MYSQL_USER` |
| `DB_PASSWORD` | `secret` | Read by the `db` container as `MYSQL_PASSWORD`; also `MYSQL_ROOT_PASSWORD` unless `DB_ROOT_PASSWORD` is set (fresh volumes only) |
| `REDIS_HOST` | `redis` | Compose service name |
| `REDIS_PORT` | `6379` | Container-internal port |
| `QUEUE_CONNECTION` | `redis` | Emails are dispatched to the Redis queue |
| `CACHE_STORE` | `redis` | |
| `SESSION_DRIVER` | `database` | |
| `ADMIN_EMAIL` | `admin@example.com` | Seeder creates this admin if it does not already exist |
| `ADMIN_PASSWORD` | *(empty)* | **Set a strong password before running `migrate --seed`** |
| `SENDGRID_API_KEY` | *(placeholder)* | Optional — required only for real email delivery |
| `SENDGRID_FROM_EMAIL` | `noreply@yourdomain.com` | Optional |
| `SENDGRID_FROM_NAME` | `TaskAssign` | Optional |

> The `db` service reads `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` from `.env` via
> compose interpolation (defaults `taskassign` / `taskassign` / `secret`). These are local
> development credentials — use strong secrets for any real deployment.

**Email**: In-app notifications work with no configuration. To also deliver emails, set `SENDGRID_API_KEY`, `SENDGRID_FROM_EMAIL`, and `SENDGRID_FROM_NAME`, then restart (`docker compose up -d`). Each user can toggle email on/off from the top navigation bar; when off, they still get in-app notifications. Emails go through the Redis queue and are sent by the `queue` worker. Delivery is logged to the `email_logs` table and `storage/logs/email-YYYY-MM-DD.log`, deduplicated in Redis for 5 minutes, retried up to 3× with backoff, and pruned nightly at 02:00 by the scheduler (`emails:prune-logs --days=30`).

---

## Volumes & persistence

| Type | Definition | Purpose |
|------|------------|---------|
| Named volume | `dbdata` → `/var/lib/mysql` (on `db`) | Persists MySQL data across restarts |
| Bind mount | `./` → `/var/www` (on `app`, `web`, `queue`, `scheduler`) | The project source is live-mounted into the containers |
| Bind mount | `./storage/app/public` → `/var/www/storage/app/public` (on `app`) | Uploaded files |
| Bind mount | `./docker/nginx/default.conf` → `/etc/nginx/conf.d/default.conf` (on `web`) | Nginx site config |

`dbdata` is the only named volume — database contents survive `docker compose down` but are **deleted** by `docker compose down -v`.

---

## Common commands

```bash
# Start everything (no rebuild) — containers also restart automatically (restart: unless-stopped)
docker compose up -d

# Stop and remove containers (keeps the dbdata volume)
docker compose down

# Rebuild images after Dockerfile/dependency changes
docker compose up -d --build

# Tail logs
docker compose logs -f web
docker compose logs -f queue
docker compose exec app tail -f storage/logs/laravel.log

# Open a shell / REPL in the app container
docker compose exec app bash
docker compose exec app php artisan tinker

# Migrate & seed (run manually — no entrypoint does this automatically)
docker compose exec app php artisan migrate --seed
docker compose exec app php artisan storage:link

# Clear cached config/routes after code changes
docker compose exec app php artisan optimize:clear

# Reset the database from scratch (DESTROYS data)
docker compose exec app php artisan migrate:fresh --seed

# Fully re-init the DB with new .env credentials (DELETES the data volume)
docker compose down -v && docker compose up -d

# MySQL shell inside the db container
docker compose exec db mysql -u taskassign -psecret taskassign
docker compose exec db mysql -u root -psecret
```

> **Note**: migrations and seeding are **not** run by an entrypoint — the `app` container's
> command only fixes storage permissions and starts `php-fpm`. Run `migrate`/`seed`/
> `storage:link` yourself after the first `up` (see [Run](#run)).

---

## Verify

1. `docker compose ps` — all services (`web`, `app`, `db`, `redis`, `queue`, `scheduler`, `phpmyadmin`) should show `Up`.
2. Open **http://localhost:8087** — the login page should load.
3. Log in with the `ADMIN_EMAIL` / `ADMIN_PASSWORD` you set in `.env` before seeding.

4. Optionally open **http://localhost:8088** (phpMyAdmin, localhost-only) and log in with your `DB_USERNAME` / `DB_PASSWORD` to browse the database. The server is pre-filled to `db`.

If http://localhost:8087 won't load, check `docker compose logs web`. If migrate fails with `SQLSTATE ... Connection refused`, MySQL needs a few seconds on first boot — re-run `docker compose exec app php artisan migrate --seed`. Manage accounts as an admin under **Manage Users** (`/admin/users`); there is no self-service registration.

---

## Tests

Tests run against a separate MySQL database, `taskassign_test` (created in the `db` container; the `taskassign` user has privileges on it). `phpunit.xml` force-sets `DB_DATABASE=taskassign_test`, so the real `taskassign` database is never touched. If the database does not exist yet:

```bash
docker compose exec -T db mysql -uroot -p"$DB_ROOT_PASSWORD" -e "CREATE DATABASE IF NOT EXISTS taskassign_test; GRANT ALL ON taskassign_test.* TO 'taskassign'@'%';"
```

Run the suite:

```bash
docker compose exec -T app php artisan test
```

## Hardening notes

- `db`, `redis` and `phpmyadmin` are published on `127.0.0.1` only; only the web port `8087` is exposed on all interfaces.
- All services with healthchecks (`db`, `redis`, `app`, `web`) must be healthy before dependents start.
- Nginx only passes `index.php` to PHP-FPM and returns 404 for any other `.php` path (including under `/storage`).
- Upload limits: nginx `client_max_body_size 100M`, PHP `post_max_size 100M`, `upload_max_filesize 25M`.
- `queue` and `scheduler` run as root; the `app` container re-chowns `storage` and `bootstrap/cache` to `www-data` on every start.

## License

MIT — see [LICENSE](LICENSE).
