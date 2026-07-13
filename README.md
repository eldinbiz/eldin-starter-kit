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
docker compose exec app php artisan migrate
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

- **Local → Docker:** just run `docker compose up -d --build` and it auto-uses `.env.docker` (mariadb)
- **Docker → Local:** run `composer dev` — `.env` already defaults to SQLite

No manual `.env` changes needed.

---

## What You Get

| Service | Docker Mode | Local Mode |
|---|---|---|
| App | http://localhost:8080 | http://localhost:8000 |
| Vite dev server (HMR) | http://localhost:5173 | http://localhost:5173 |
| Database | MariaDB (localhost:3306) | SQLite (database/database.sqlite) |

**Hot reload:** edit any PHP, Blade, CSS, or JS file — changes appear instantly, no restarts needed.

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

## Queue Worker

### Docker Mode

```bash
docker compose logs -f queue                   # watch worker logs
docker compose exec app php artisan queue:failed   # see failed jobs
docker compose exec app php artisan queue:retry all  # retry all
docker compose up -d --scale queue=3           # run 3 workers
```

### Local Mode

The `composer dev` command already runs `php artisan queue:listen` alongside the server and Vite. Jobs are processed automatically as you work.
