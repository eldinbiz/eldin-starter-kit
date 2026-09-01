# Environment Setup & Development Modes Guide

This guide details the workstation setup, development workflows, container management, and environment switching options for this multi-tenant scaffolding platform.

---

## Table of Contents
1. [Prerequisites](#1-prerequisites)
2. [Development Options](#2-development-options)
   - [Option A: Docker + MariaDB (Full Stack)](#option-a-docker--mariadb-full-stack)
   - [Option B: Local PHP + SQLite (Lightweight)](#option-b-local-php--sqlite-lightweight)
3. [Environment Matrix (Ports & Services)](#3-environment-matrix-ports--services)
4. [Switching Between Development Modes](#4-switching-between-development-modes)
5. [Git Branch Switching in Docker Mode](#5-git-branch-switching-in-docker-mode)
6. [Daily Development Commands](#6-daily-development-commands)
7. [Queue Worker Management](#7-queue-worker-management)
8. [Rebuilding Docker Containers & Volumes](#8-rebuilding-docker-containers--volumes)
9. [Running Automated Tests](#9-running-automated-tests)

---

## 1. Prerequisites

Before getting started, ensure your local workstation meets the following minimum requirements:

- **PHP**: `^8.4` (with `pdo`, `mbstring`, `openssl`, `curl`, `sqlite3`, `gd` extensions enabled)
- **Composer**: `^2.2`
- **Node.js**: `^22.0` (with `npm`)
- **Docker & Docker Compose**: (Required only if developing with Docker mode)

---

## 2. Development Options

Choose one of the two development workflows depending on your preferences:

### Option A: Docker + MariaDB (Full Stack)

This mode runs the entire stack inside isolated Docker containers (Laravel App, MariaDB 11, Vite HMR, and Queue Worker):

```bash
# 1. Copy environment file
copy .env.example .env

# 2. Build and launch containers in background
docker compose up -d --build

# 3. First-time initialization inside container:
docker compose exec app php artisan key:generate
docker compose exec app php artisan storage:link
docker compose exec app php artisan migrate:fresh --seed
docker compose exec app php artisan audit-log:ui enable
```

> [!NOTE]
> The Docker container automatically loads `.env.docker`, which points directly to the MariaDB service—no manual database credentials editing is required in `.env`.
> 
> Open **http://localhost:8080** in your browser.

---

### Option B: Local PHP + SQLite (Lightweight)

This mode runs natively on your host machine without Docker, using SQLite for database, cache, sessions, and queue:

```bash
# 1. First-time setup (installs Composer & NPM dependencies, creates SQLite DB, runs migrations & seeds)
composer setup

# 2. Start the unified development server (App + Queue Worker + Vite HMR)
composer dev
```

> [!NOTE]
> `composer dev` concurrently launches the PHP server, the Laravel queue worker, and the Vite HMR server in a single terminal process. Press `Ctrl+C` to terminate all services simultaneously.
> 
> Open **http://localhost:8000** in your browser.

---

## 3. Environment Matrix (Ports & Services)

| Service | Docker Mode | Local PHP Mode | Notes |
|---|---|---|---|
| **Web Application** | `http://localhost:8080` | `http://localhost:8000` | Main single-page web interface |
| **API Documentation** | `http://localhost:8080/docs/api` | `http://localhost:8000/docs/api` | Interactive OpenAPI 3.1 Scramble UI |
| **Vite HMR Server** | `http://localhost:5173` | `http://localhost:5173` | Hot module reloading for CSS/JS |
| **Database** | MariaDB (`localhost:3306`) | SQLite (`database/database.sqlite`) | Isolated per environment |
| **Queue Worker** | Docker `queue` service container | Process launched via `composer dev` | Database queue driver |

---

## 4. Switching Between Development Modes

### Switching from Local Mode to Docker

```bash
docker compose up -d --build
```
*If running for the first time in Docker, initialize the MariaDB database:*
```bash
docker compose exec app php artisan key:generate
docker compose exec app php artisan storage:link
docker compose exec app php artisan migrate:fresh --seed
docker compose exec app php artisan audit-log:ui enable
```

### Switching from Docker to Local Mode

1. Stop Docker containers:
   ```bash
   docker compose down
   ```
2. If switching back to local mode after pulling dependencies:
   ```bash
   composer setup
   composer dev
   ```

---

## 5. Git Branch Switching in Docker Mode

When switching git branches (`git checkout <branch>`), disk files update immediately via bind mounts, but cached states inside the Docker containers may remain stale:

```bash
# Complete branch synchronization one-liner
docker compose exec app composer install --no-interaction && \
docker compose exec app php artisan optimize:clear && \
docker compose exec app php artisan audit-log:ui enable && \
docker compose exec vite npm install && \
docker compose exec vite npm run build && \
docker compose restart queue
```

### What Each Step Performs:
1. `composer install` — Syncs PHP dependencies to the branch's `composer.lock`.
2. `optimize:clear` — Flushes config, route, view, event, and compiled framework caches.
3. `audit-log:ui enable` — Re-registers the Yammi audit trail dashboard routes after cache clears.
4. `npm install && npm run build` — Updates Node packages and compiles asset bundles.
5. `restart queue` — Forces the daemon queue worker to reload latest application code.
6. `php artisan migrate` — Applies any new database migrations introduced in the branch.

---

## 6. Daily Development Commands

### In Docker Mode

```bash
docker compose up -d                     # Start all background containers
docker compose down                      # Stop all containers
docker compose logs -f                   # Tail logs from all containers
docker compose logs -f app               # Tail Laravel application logs
docker compose logs -f queue             # Tail background queue worker logs
docker compose exec app php artisan ...  # Execute any Artisan command
docker compose exec app composer ...     # Execute any Composer command
```

### In Local PHP Mode

```bash
composer dev                             # Start web server, queue worker, and Vite
php artisan ...                          # Run any Artisan command
composer ...                             # Run any Composer command
```

---

## 7. Queue Worker Management

Background jobs (notifications, email delivery, file exports, async imports) are processed by Laravel's database queue driver:

### Docker Mode Queue Commands
```bash
docker compose logs -f queue                    # Watch active worker jobs
docker compose exec app php artisan queue:failed    # View failed jobs
docker compose exec app php artisan queue:retry all   # Retry all failed jobs
docker compose up -d --scale queue=3            # Scale to 3 parallel workers
```

---

## 8. Rebuilding Docker Containers & Volumes

### Full Wipe & Clean Rebuild (Resets Database)
```bash
docker compose down -v
docker compose build --no-cache
docker compose up -d
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate:fresh --seed
docker compose exec app php artisan audit-log:ui enable
```

### Rebuild Application Images (Preserving Database Volume)
```bash
docker compose down
docker compose build --no-cache
docker compose up -d
```

---

## 9. Running Automated Tests

Tests execute against an isolated in-memory SQLite database and never touch your active MariaDB or development SQLite database:

```bash
# Run all Pest 5 test suites
./test

# Run tests in Docker mode
docker compose exec app ./test

# Filter specific test class or method
./test --filter=PurchaseRequestTest
```
