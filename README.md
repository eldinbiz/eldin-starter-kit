# Scaffolding App (Laravel)

A Laravel 13 application with MariaDB, Docker deployment, and database-driven queues.

---

## Quick Start (Docker Development)

```bash
# 1. Copy the Docker environment template
cp .env.docker .env

# 2. Start all services
docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d --build

# 3. The app is now running at http://localhost
#    Vite dev server with HMR: http://localhost:5173
```

**Hot reload works out of the box** — edit any PHP, Blade, CSS, or JS file and changes reflect immediately without restarting any containers. No `php artisan serve`, no restarting PHP-FPM or Nginx.

---

## Production Deployment

```bash
# 1. Copy and configure environment
cp .env.docker .env
# Edit .env:
#   APP_ENV=production
#   APP_DEBUG=false
#   AUTO_MIGRATE=false
#   DB_PASSWORD=<strong-password>
#   DB_ROOT_PASSWORD=<strong-root-password>

# 2. Build and start
docker compose up -d --build

# 3. Run migrations manually (one-time)
docker compose exec app php artisan migrate --force

# 4. Scale queue workers if needed
docker compose up -d --scale queue=3
```

---

## Architecture

| Service | Image | Port | Description |
|---|---|---|---|
| `nginx` | nginx:alpine | 80 | Reverse proxy → PHP-FPM, serves static assets |
| `app` | custom (PHP 8.4-FPM) | 9000 (internal) | Laravel application |
| `mariadb` | mariadb:11 | 3306 (internal) | Database |
| `queue` | custom (same image) | — | Queue worker (database driver) |
| `vite` (dev) | node:22-alpine | 5173 | Vite dev server with HMR |

### How Hot Reload Works (Dev Only)

- **PHP/Blade**: PHP-FPM processes each request independently. With `opcache.validate_timestamps=1` and `opcache.revalidate_freq=0` (set in `docker/php/php.ini`), every file change is detected on the next request — no restart needed.
- **Config/routes**: Laravel auto-detects changes in dev mode. Run `php artisan optimize:clear` inside the container if caching causes issues.
- **CSS/JS**: Vite dev server (port 5173) provides HMR. The `vite` container watches for file changes and pushes updates to the browser instantly.

### Dev vs Prod Differences

| | Dev | Prod |
|---|---|---|
| Build target | `dev` (xdebug, all deps) | `prod` (optimized, no dev deps) |
| Code source | Bind mount (live) | Copied into image |
| opcache revalidate | Every request (0s) | Disabled (timestamps off) |
| Auto-migrate | `true` | `false` |
| Xdebug | Enabled | Not installed |
| Node/npm in image | No (vite container) | Yes (builds then removed) |

---

## Queue Worker Management

The queue worker runs as a separate service. Jobs are stored in the `jobs` table (database driver).

### Viewing Queue Status

```bash
# Watch live logs of the queue worker
docker compose logs -f queue

# List failed jobs
docker compose exec app php artisan queue:failed

# Retry all failed jobs
docker compose exec app php artisan queue:retry all

# Retry a specific failed job (by ID)
docker compose exec app php artisan queue:retry <job-id>

# Clear all failed jobs from the table
docker compose exec app php artisan queue:flush

# See current queue size
docker compose exec app php artisan queue:monitor
```

### Worker Configuration

The worker is configured in `docker-compose.yml` with these flags:

| Flag | Value | Purpose |
|---|---|---|
| `--tries` | 3 | Max attempts before job is marked failed |
| `--timeout` | 90s | Max time a job can run |
| `--sleep` | 3s | Sleep between polling when queue is empty |

### Restarting the Queue Worker

```bash
# Graceful restart (finishes current job, then exits)
docker compose restart queue

# After code changes that affect jobs, force restart:
docker compose up -d --force-recreate queue
```

### Scaling Workers

```bash
# Run 3 queue workers in parallel
docker compose up -d --scale queue=3

# Scale back to 1
docker compose up -d --scale queue=1
```

### Crash Recovery

The worker uses `restart: unless-stopped`, so Docker automatically restarts it if it crashes. The worker also handles SIGTERM gracefully — it finishes the current job before stopping.

### Monitoring Failed Jobs in Production

For production, consider adding a scheduled command to alert on failed jobs. Add to `app/Console/Kernel.php`:

```php
$schedule->command('queue:failed --count')
    ->everyFiveMinutes()
    ->emailOutputOnFailure();
```

---

## Common Commands

```bash
# Enter the app container
docker compose exec app sh

# Run artisan commands
docker compose exec app php artisan route:list
docker compose exec app php artisan migrate
docker compose exec app php artisan tinker

# Run composer
docker compose exec app composer require some/package

# View all logs
docker compose logs -f

# View specific service logs
docker compose logs -f app
docker compose logs -f nginx
docker compose logs -f mariadb

# Restart everything
docker compose restart

# Stop everything
docker compose down

# Stop and remove volumes (WARNING: deletes database!)
docker compose down -v

# Rebuild after Dockerfile changes
docker compose up -d --build
```

---

## Environment Variables Reference

| Variable | Default | Description |
|---|---|---|
| `APP_ENV` | `local` | `local` or `production` |
| `APP_DEBUG` | `true` | Enable debug mode |
| `DB_HOST` | `mariadb` | Database hostname (container name) |
| `DB_DATABASE` | `laravel` | Database name |
| `DB_USERNAME` | `laravel` | Database user |
| `DB_PASSWORD` | `laravel` | Database password |
| `DB_ROOT_PASSWORD` | `rootpassword` | MariaDB root password |
| `AUTO_MIGRATE` | `true` (dev) | Auto-run migrations on start |

---

## File Structure

```
├── Dockerfile                  # Multi-stage: base → dev | prod
├── docker-compose.yml          # Production compose config
├── docker-compose.dev.yml      # Dev overrides (extends production)
├── .dockerignore               # Files excluded from Docker build
├── .env.docker                 # Docker environment template
├── docker/
│   ├── entrypoint.sh           # Container init (wait DB, key:gen, migrate)
│   ├── nginx/
│   │   └── default.conf        # Nginx site configuration
│   └── php/
│       ├── php.ini             # Dev PHP overrides
│       └── php-prod.ini        # Production PHP overrides
