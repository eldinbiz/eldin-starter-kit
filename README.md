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
docker compose exec app php artisan audit-log:ui enable
```

The Docker setup auto-loads `.env.docker` which points to the MariaDB container — no manual `.env` edits needed.

> **Note:** `audit-log:ui enable` is also run automatically by `entrypoint.sh` on every container start, so this step is only needed if you skipped it during first-time setup.

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
docker compose exec app php artisan audit-log:ui enable
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
docker compose exec app php artisan audit-log:ui enable
docker compose exec vite npm install
docker compose exec vite npm run build
docker compose restart queue

# If migrations changed
docker compose exec app php artisan migrate
```

```bash
# One-liner (skip migrate — run it separately if needed)
docker compose exec app composer install --no-interaction && docker compose exec app php artisan optimize:clear && docker compose exec app php artisan audit-log:ui enable && docker compose exec vite npm install && docker compose exec vite npm run build && docker compose restart queue
```

> **What each command does:**
> - `composer install` — syncs vendor to the new branch's `composer.lock`
> - `optimize:clear` — flushes config, route, view, event, and compiled caches
> - `audit-log:ui enable` — re-enables the Yammi audit trail dashboard (required after cache clear)
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
docker compose exec app php artisan audit-log:ui enable
```

```bash
# Inline (one-liner)
docker compose down -v && docker compose build --no-cache && docker compose up -d && docker compose exec app php artisan key:generate && docker compose exec app php artisan migrate:fresh --seed && docker compose exec app php artisan audit-log:ui enable
```

> **Note:** `audit-log:ui enable` is also handled automatically by `entrypoint.sh`, so after `docker compose up -d` the dashboard should already be active. Run it manually only if the `/audit-log` route returns 404.

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

---

## Architecture & Developer Guides

- **Modular Architecture Guide (`internachi/modular`)**: See [docs/MODULAR_ARCHITECTURE_GUIDE.md](docs/MODULAR_ARCHITECTURE_GUIDE.md) for module lifecycle, anatomy, CLI commands with `--module`, and core integrations.
  - Upstream package documentation: [InterNACHI/modular on GitHub](https://github.com/InterNACHI/modular/blob/main/README.md).
- **API Developer Guide & OpenAPI (Scramble)**: See [docs/API_LAYER_GUIDE.md](docs/API_LAYER_GUIDE.md) for REST API architecture, multi-tenant scoping (`X-Company-ID`), and code templates.
  - Interactive Swagger/OpenAPI 3.1 docs: [http://localhost:8000/docs/api](http://localhost:8000/docs/api) (or `http://localhost:8080/docs/api` in Docker).
  - Bruno API collection: located in `bruno/` for instant API testing in the VS Code Bruno extension.
- **Feature Toggles & Module Management Guide**: See [docs/developer-guide-feature-toggles.md](docs/developer-guide-feature-toggles.md) for per-company module enablement, middleware, and `@feature` Blade directives.
- **Notifications & Email Templates Guide**: See [docs/developer-guide-notifications-and-emails.md](docs/developer-guide-notifications-and-emails.md) for transactional emails, in-app alerts, and Quill WYSIWYG templates.


