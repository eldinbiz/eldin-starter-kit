# Scaffolding App

## Prerequisites

- PHP 8.3+
- Composer 2
- Node.js 22+
- **Docker + Docker Compose** (for Docker mode only)

---

## Development Modes

Choose one depending on your workstation:

### Option A: Docker + MariaDB (Full Stack)

```bash
# First time only
copy .env.example .env
docker compose up -d --build

# Wait for containers to be ready, then:
docker compose exec app php artisan key:generate
docker compose exec app php artisan storage:link
docker compose exec app php artisan migrate:fresh --seed
```

The Docker setup auto-loads `.env.docker` which points to the MariaDB container — no manual `.env` edits needed.

Open **http://localhost:8080** — you're done.

---

### Option B: Local PHP + SQLite (Lightweight)

No Docker required. Uses SQLite for the database, cache, sessions, and queue.

```bash
# First time only
composer setup
```

After setup, start the dev server:

```bash
composer dev
```

Open **http://localhost:8000** — you're done.

---

## Switching Between Modes

### Local → Docker

```bash
docker compose up -d --build
```

Auto-loads `.env.docker` (MariaDB). No manual `.env` edits needed.

**First time only** — run setup inside the container:

```bash
docker compose exec app php artisan key:generate
docker compose exec app php artisan storage:link
docker compose exec app php artisan migrate:fresh --seed
```

### Docker → Local

**First time only** — if `vendor/`, `node_modules/`, or the SQLite database are missing/stale:

```bash
composer setup
```

**After initial setup** — start the dev server, queue, and Vite:

```bash
composer dev
```

---

## What You Get

| Service | Docker Mode | Local Mode |
|---|---|---|
| App | http://localhost:8080 | http://localhost:8000 |
| Vite dev server (HMR) | http://localhost:5173 | http://localhost:5173 |
| Database | MariaDB (localhost:3306) | SQLite (database/database.sqlite) |

**Hot reload:** edit any PHP, Blade, CSS, or JS file — changes appear instantly, no restarts needed.

---

## Switching Branches in Docker Mode

When you `git checkout` a different branch, the files on disk update instantly (bind mount), but cached state inside the containers stays stale:
**vendor**, **bootstrap/cache**, and compiled frontend assets still reference the old branch.

```bash
# Step by step
docker compose exec app composer install --no-interaction
docker compose exec app php artisan optimize:clear
docker compose exec vite npm install
docker compose exec vite npm run build
docker compose restart queue

# If migrations changed
docker compose exec app php artisan migrate
```

```bash
# One-liner (skip migrate — run it separately if needed)
docker compose exec app composer install --no-interaction && docker compose exec app php artisan optimize:clear && docker compose exec vite npm install && docker compose exec vite npm run build && docker compose restart queue
```

> **What each command does:**
> - `composer install` — syncs vendor to the new branch's `composer.lock`
> - `optimize:clear` — flushes config, route, view, event, and compiled caches
> - `npm install && npm run build` — rebuilds frontend assets
> - `restart queue` — forces the queue worker to reload new code

---

## Daily Commands

### Docker Mode

```bash
docker compose up -d                    # start
docker compose down                     # stop
docker compose logs -f                  # see all logs
docker compose exec app php artisan ... # run artisan
docker compose exec app composer ...    # run composer
```

### Local Mode

```bash
composer dev                            # start (server + queue + vite)
# Ctrl+C to stop all three
php artisan ...                         # run artisan
composer ...                            # run composer
```

---

## Running Tests

Tests run against an in-memory SQLite database — they never touch your development
database (MariaDB or SQLite), so no separate test database setup is needed.

### Docker Mode

```bash
docker compose exec app composer test
```

### Local Mode

```bash
composer test
```

---

## Queue Worker

### Docker Mode

```bash
docker compose logs -f queue                   # watch worker logs
docker compose exec app php artisan queue:failed   # see failed jobs
docker compose exec app php artisan queue:retry all  # retry all
docker compose up -d --scale queue=3           # run 3 workers

```

### Rebuilding Docker Images

**Full rebuild (wipe database, start fresh):**

```bash
# Step by step
docker compose down -v
docker compose build --no-cache
docker compose up -d
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate:fresh --seed
```

```bash
# Inline (one-liner)
docker compose down -v && docker compose build --no-cache && docker compose up -d && docker compose exec app php artisan key:generate && docker compose exec app php artisan migrate:fresh --seed
```

**Rebuild images, keep database:**

```bash
# Step by step
docker compose down
docker compose build --no-cache
docker compose up -d
```

```bash
# Inline (one-liner)
docker compose down && docker compose build --no-cache && docker compose up -d
```

**Normal restart (no rebuild, keep everything):**

```bash
docker compose up -d --build
```

---

### Local Mode

The `composer dev` command already runs `php artisan queue:listen` alongside the server and Vite. Jobs are processed automatically as you work.
