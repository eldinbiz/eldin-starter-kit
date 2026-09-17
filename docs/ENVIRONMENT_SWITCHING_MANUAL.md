# Development Environment Transition Manual: Local ⇋ Docker

This manual provides exhaustive step-by-step instructions, exact CLI command sequences, daily command equivalency references, and troubleshooting procedures for transitioning a workstation between **Non-Docker (Local PHP 8.4 + SQLite)** and **Docker (Multi-Container + MariaDB 11)** development environments.

---

## Table of Contents
1. [Architecture & Storage Comparison](#1-architecture--storage-comparison)
2. [Pre-Flight Transition Checklist](#2-pre-flight-transition-checklist)
3. [Playbook A: Switching from Local (Non-Docker) to Docker](#3-playbook-a-switching-from-local-non-docker-to-docker)
4. [Playbook B: Switching from Docker to Local (Non-Docker)](#4-playbook-b-switching-from-docker-to-local-non-docker)
5. [Side-by-Side CLI Command Equivalency Matrix](#5-side-by-side-cli-command-equivalency-matrix)
6. [Data & Database Considerations](#6-data--database-considerations)
7. [Cross-Platform Nuances (Windows vs Linux/macOS/WSL)](#7-cross-platform-nuances-windows-vs-linuxmacoswsl)
8. [Troubleshooting & Common Gotchas](#8-troubleshooting--common-gotchas)

---

## 1. Architecture & Storage Comparison

Understanding how each environment is wired prevents common caching and isolation issues:

```
┌────────────────────────────────────────────────────────┐     ┌────────────────────────────────────────────────────────┐
│             Non-Docker (Local Mode)                    │     │                   Docker Mode                          │
│                                                        │     │                                                        │
│  Host OS (Windows / macOS / Linux)                     │     │  Docker Compose Network (`laravel`)                   │
│  ├── PHP 8.4 CLI Server   --> http://localhost:8000   │     │  ├── Nginx Reverse Proxy --> http://localhost:8080    │
│  ├── Vite HMR Server      --> http://localhost:5173   │     │  │   └── PHP-FPM 8.4 (`app:9000`)                      │
│  ├── Queue Worker         --> `composer dev` process  │     │  ├── Vite Container     --> http://localhost:5173    │
│  ├── Database             --> `database.sqlite` (file)│     │  ├── Queue Container    --> `laravel-queue` worker   │
│  ├── Dependencies         --> Host `./vendor`         │     │  ├── Database Container --> MariaDB 11 (`:3306`)     │
│  └── Config File          --> Reads `.env` directly   │     │  ├── Dependencies       --> Container volume `vendor`│
│                                                        │     │  └── Config Overrides   --> Reads `.env.docker`      │
└────────────────────────────────────────────────────────┘     └────────────────────────────────────────────────────────┘
```

### Key Architectural Differences:

| Aspect | Local Mode (Non-Docker) | Docker Mode | Critical Implication |
|---|---|---|---|
| **Web Server** | `php artisan serve` on port `8000` | Nginx FastCGI on port `8080` | URLs and redirects must use port `8080` in Docker, `8000` in Local. |
| **Database** | SQLite (`database/database.sqlite`) | MariaDB 11 (TCP port `3306`) | Data is completely separated. Migrations/seeders must run in the active target. |
| **PHP Vendor** | Host directory `./vendor` | Container anonymous volume `/var/www/html/vendor` | New Composer packages added in one environment are **not** auto-synced to the other. |
| **Node Modules** | Host directory `./node_modules` | Bind-mounted `./node_modules` | Native binary extensions (Rollup/esbuild) may differ between Windows and Linux Alpine. |
| **Vite HMR** | Local Node process on port `5173` | Docker Node container on port `5173` | Both compete for port `5173`. Must stop one before starting the other. |
| **Configuration** | Reads root `.env` | Reads root `.env` + overrides from `.env.docker` | Container uses `.env.docker` for DB and queue connection settings. |

---

## 2. Pre-Flight Transition Checklist

Before switching in either direction, perform this quick sanity check:

- [ ] **No Uncommitted Work**: Commit or stash your git changes (`git status`) so file modifications remain clean.
- [ ] **Port Availability**: Verify target ports are released:
  - Port `5173` (Vite dev server)
  - Port `8000` (Local PHP) or `8080` (Docker Nginx)
  - Port `3306` (MariaDB / MySQL)
- [ ] **Clean Cached Manifests**: Flush Laravel framework caches so cached paths or database connection drivers do not contaminate the new mode.

---

## 3. Playbook A: Switching from Local (Non-Docker) to Docker

Follow these steps when moving your active development workstation into Docker.

### Step 1: Stop Local Processes
Terminate any running `composer dev` processes.
- In your running terminal: Press `Ctrl + C`.
- Ensure no orphan PHP or Node processes are occupying ports:

**Windows (PowerShell):**
```powershell
Get-Process -Name "php", "node" -ErrorAction SilentlyContinue | Stop-Process -Force
```

**Linux / macOS:**
```bash
killall php node 2>/dev/null || true
```

### Step 2: Check for Local MySQL Conflicts
Docker's MariaDB binds to host port `3306`. If you have a local MySQL/MariaDB server running natively on your workstation (e.g. via XAMPP, Laragon, Homebrew, or Windows Service), stop it:

**Windows (PowerShell as Admin if service):**
```powershell
Stop-Service MySQL -ErrorAction SilentlyContinue
Stop-Service MariaDB -ErrorAction SilentlyContinue
```

**Linux / macOS:**
```bash
sudo systemctl stop mysql 2>/dev/null || sudo brew services stop mysql 2>/dev/null || true
```

### Step 3: Clean Stale Local Build & Cache Artifacts
Delete the local Vite pointer and clear cached configs that may contain host-specific file paths:

**Windows (PowerShell):**
```powershell
if (Test-Path "public\hot") { Remove-Item "public\hot" -Force }
php artisan optimize:clear
```

**Linux / macOS:**
```bash
rm -f public/hot
php artisan optimize:clear
```

### Step 4: Ensure Environment Files Exist
Ensure `.env` exists (provides `APP_KEY`) and `.env.docker` is present:
```bash
# If .env does not exist yet:
cp .env.example .env
```

### Step 5: Start Docker Containers
Spin up all background containers (`app`, `nginx`, `mariadb`, `queue`, `vite`):

```bash
docker compose up -d --build
```

### Step 6: Synchronize Dependencies & Initialize Database
Because the container uses an isolated anonymous volume for `vendor/`, install dependencies and initialize the MariaDB schema:

```bash
# 1. Sync PHP dependencies inside container
docker compose exec app composer install --no-interaction

# 2. Generate application key (if not already set in .env)
docker compose exec app php artisan key:generate --force

# 3. Create public storage symlink
docker compose exec app php artisan storage:link

# 4. Run migrations and seed database in MariaDB
docker compose exec app php artisan migrate --seed

# 5. Enable Yammi audit log UI routes
docker compose exec app php artisan audit-log:ui enable
```

> [!TIP]
> **Need a completely fresh Docker database?** Run:
> ```bash
> docker compose exec app php artisan migrate:fresh --seed
> ```

### Step 7: Verify Docker Environment
- **Web App**: Open [http://localhost:8080](http://localhost:8080)
- **API Docs (Scramble)**: Open [http://localhost:8080/docs/api](http://localhost:8080/docs/api)
- **Vite HMR**: Inspect console in browser — verify scripts load from `http://localhost:5173`
- **Container Health**: Run `docker compose ps` (all services should show `Up` / `healthy`)

---

## 4. Playbook B: Switching from Docker to Local (Non-Docker)

Follow these steps when transitioning from Docker back to running natively on your workstation with Local PHP and SQLite.

### Step 1: Stop and Tear Down Docker Containers
Shut down all running Docker containers to release ports `8080`, `5173`, and `3306`:

```bash
docker compose down
```

> [!NOTE]
> `docker compose down` stops and removes the containers and the default network, but **preserves** your MariaDB volume (`mariadb_data`). Your Docker database state remains safe.

### Step 2: Fix File Permissions (Linux / macOS / WSL Only)
Docker runs as `www-data` or `root`, meaning files created in `storage/` or `bootstrap/cache/` by containers may be owned by root on the host filesystem. Restore ownership to your local user:

**Linux / macOS / WSL:**
```bash
sudo chown -R $USER:$USER storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```
*(On native Windows with Docker Desktop, file permissions are automatically mapped, so this step can be skipped).*

### Step 3: Remove Stale Docker Build & Cache Artifacts
Purge container-generated cached manifests and any leftover `public/hot` file:

**Windows (PowerShell):**
```powershell
if (Test-Path "public\hot") { Remove-Item "public\hot" -Force }
Get-ChildItem -Path "bootstrap\cache\*.php" | Remove-Item -Force -ErrorAction SilentlyContinue
```

**Linux / macOS:**
```bash
rm -f public/hot
rm -f bootstrap/cache/*.php
```

### Step 4: Verify Host `.env` Configuration
Open `.env` on your host machine and verify that database settings point to SQLite:
```ini
APP_URL=http://localhost:8000

DB_CONNECTION=sqlite
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=laravel
# DB_USERNAME=root
# DB_PASSWORD=

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database
```

Ensure the SQLite database file exists:
**Windows (PowerShell):**
```powershell
if (-not (Test-Path "database\database.sqlite")) { New-Item -ItemType File -Path "database\database.sqlite" -Force }
```

**Linux / macOS:**
```bash
touch database/database.sqlite
```

### Step 5: Synchronize Host Dependencies & Native Binaries
If new Composer or NPM packages were installed while working inside Docker, update your host directories:

```bash
# 1. Update host PHP vendor directory
composer install

# 2. Re-install NPM packages for host platform architecture
npm install

# 3. Clear and re-discover Laravel packages on host
php artisan optimize:clear
php artisan package:discover --ansi
```

### Step 6: Synchronize Local SQLite Database
Apply any pending database migrations to your local SQLite file:

```bash
php artisan migrate

# If you need default test records / demo data:
php artisan db:seed
```

> [!WARNING]
> Do NOT run `composer setup` if you have existing development data in `database/database.sqlite` that you want to keep, as `composer setup` unlinks and recreates the SQLite file. Use `php artisan migrate` instead.

### Step 7: Launch Local Development Server
Start the concurrent development runner (App server + Queue worker + Vite HMR):

```bash
composer dev
```

### Step 8: Verify Local Environment
- **Web App**: Open [http://localhost:8000](http://localhost:8000)
- **API Docs (Scramble)**: Open [http://localhost:8000/docs/api](http://localhost:8000/docs/api)
- **Pest Tests**: Run `php artisan rubberstamp:features` (or `php artisan test`) to verify full suite passes

---

## 5. Side-by-Side CLI Command Equivalency Matrix

Bookmark this quick-reference table for common development tasks across both environments:

| Developer Task | Non-Docker (Local Mode) | Docker Mode |
|---|---|---|
| **Start Dev Stack** | `composer dev` | `docker compose up -d` |
| **Stop Dev Stack** | `Ctrl + C` in terminal | `docker compose down` |
| **Check Container Status** | N/A (`Get-Process php`) | `docker compose ps` |
| **Tail Application Logs** | `tail -f storage/logs/laravel.log` | `docker compose logs -f app` |
| **Tail Queue Worker Logs** | Output visible in `composer dev` terminal | `docker compose logs -f queue` |
| **Tail Vite HMR Logs** | Output visible in `composer dev` terminal | `docker compose logs -f vite` |
| **Run Migrations** | `php artisan migrate` | `docker compose exec app php artisan migrate` |
| **Rollback Migration** | `php artisan migrate:rollback` | `docker compose exec app php artisan migrate:rollback` |
| **Fresh Database + Seed** | `php artisan migrate:fresh --seed` | `docker compose exec app php artisan migrate:fresh --seed` |
| **Run Database Seeders** | `php artisan db:seed` | `docker compose exec app php artisan db:seed` |
| **Install Composer Package** | `composer require vendor/pkg` | `docker compose exec app composer require vendor/pkg` *(Then run `composer install` on host)* |
| **Sync Composer Packages** | `composer install` | `docker compose exec app composer install` |
| **Install NPM Package** | `npm install pkg` | `docker compose exec vite npm install pkg` *(Then run `npm install` on host)* |
| **Build Assets for Production**| `npm run build` | `docker compose exec vite npm run build` |
| **Clear Framework Cache** | `php artisan optimize:clear` | `docker compose exec app php artisan optimize:clear` |
| **Re-discover Packages** | `php artisan package:discover --ansi` | `docker compose exec app php artisan package:discover --ansi` |
| **Re-enable Audit Log UI** | `php artisan audit-log:ui enable` | `docker compose exec app php artisan audit-log:ui enable` |
| **Execute Artisan CLI** | `php artisan <command>` | `docker compose exec app php artisan <command>` |
| **Open Interactive Tinker** | `php artisan tinker` | `docker compose exec -it app php artisan tinker` |
| **Run Pest 5 Test Suite** | `php artisan rubberstamp:features` | `docker compose exec app php artisan rubberstamp:features` |
| **Run Filtered Pest Test** | `php artisan rubberstamp:features --filter=PurchaseRequestTest` | `docker compose exec app php artisan rubberstamp:features --filter=PurchaseRequestTest` |
| **Run Browser Tests (Playwright)**| `php artisan rubberstamp:browser` | `docker compose exec app php artisan rubberstamp:browser` |
| **Scale Queue Workers** | Open separate terminal: `php artisan queue:work` | `docker compose up -d --scale queue=3` |
| **Access Database CLI** | `sqlite3 database/database.sqlite` | `docker compose exec mariadb mariadb -ularavel -plaravel laravel` |
| **Container Bash Shell** | N/A | `docker compose exec -it app sh` |

---

## 6. Data & Database Considerations

### Data Separation
The Local environment uses SQLite (`database/database.sqlite`), while Docker uses a persistent named MariaDB volume (`mariadb_data`).
- Changes made to records in Local mode **will not** appear in Docker mode.
- Changes made to records in Docker mode **will not** appear in Local mode.

### How to Point Host CLI Tools to Docker's MariaDB
If you wish to run `php artisan` commands or GUI tools (TablePlus, DBeaver, HeidiSQL, PhpStorm) directly from your host workstation while Docker's database container is running:
1. Docker forwards MariaDB port `3306:3306` to your host machine.
2. In your host `.env`, you can temporarily configure:
   ```ini
   DB_CONNECTION=mariadb
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=laravel
   DB_USERNAME=laravel
   DB_PASSWORD=laravel
   ```
3. Connect your host GUI database client to `127.0.0.1:3306` with user `laravel` and password `laravel`.

---

## 7. Cross-Platform Nuances (Windows vs Linux/macOS/WSL)

### 1. Windows Line Endings (`CRLF` vs `LF`)
Docker shell scripts (`docker/entrypoint.sh`) must use Unix line endings (`LF`). If Git on Windows checks out files with `CRLF`, Alpine Linux inside Docker will fail with:
```
entrypoint.sh: /usr/local/bin/entrypoint.sh: line 2: $'\r': not found
```
**Fix:** Keep `.gitattributes` configured with `*.sh text eol=lf`.

### 2. NPM Architecture Mismatches (`node_modules`)
Because `node_modules` is bind-mounted directly from the host filesystem into Docker's `vite` container:
- If you run `npm install` inside Docker, Alpine Linux installs Linux x86_64 binaries for packages with native code (e.g. `@rollup/rollup-linux-x64-musl`, `esbuild`, `lightningcss`).
- If you then switch to Local mode on Windows and execute `npm run dev`, Vite will error out because the Windows native binary is missing.
- **Remedy:** Always execute `npm install` on the host machine whenever switching back to Local Windows mode.

### 3. Linux/macOS File Permissions
On Unix systems, Docker container writes to `storage/` and `bootstrap/cache/` as UID 33 (`www-data`) or UID 0 (`root`).
- When switching back to local mode, local PHP running under your personal user account will throw `ErrorException: Permission denied` when attempting to write to `storage/logs/laravel.log` or compile Blade views in `storage/framework/views/`.
- **Remedy:** Execute `sudo chown -R $USER:$USER storage bootstrap/cache && chmod -R 775 storage bootstrap/cache`.

---

## 8. Troubleshooting & Common Gotchas

### Issue 1: "Address already in use" on Port 5173
- **Cause:** Local Vite server is still running while Docker attempts to start `laravel-vite`, or vice versa.
- **Resolution:**
  1. Identify process on port 5173:
     - Windows: `netstat -ano | findstr :5173`
     - Linux/macOS: `lsof -i :5173`
  2. Stop the conflicting process or shut down the opposing environment (`Ctrl + C` or `docker compose down`).

### Issue 2: "Address already in use" on Port 3306
- **Cause:** Host machine has native MySQL or MariaDB running, preventing Docker's `laravel-mariadb` from binding port 3306.
- **Resolution:**
  - Stop the host service: `Stop-Service MySQL` (Windows) or `sudo systemctl stop mysql` (Linux).
  - Alternatively, remap Docker's external port in `docker-compose.yml` (e.g., `"3307:3306"`).

### Issue 3: Browser Loads Blank Page or 404 on Vite Assets (`public/hot`)
- **Cause:** `public/hot` file was created by an environment that is no longer running. Laravel detects `public/hot` and attempts to load scripts from the old host/port instead of pre-compiled assets.
- **Resolution:** Delete `public/hot`:
  ```bash
  rm -f public/hot
  php artisan optimize:clear
  ```

### Issue 4: "Class not found" After Switching Environments
- **Cause:** A Composer package was installed in one environment, but the other environment's `vendor/` directory was not synchronized.
- **Resolution:**
  - In Docker: `docker compose exec app composer install`
  - In Local: `composer install`

### Issue 5: Yammi Audit Log UI 404 Route Not Found
- **Cause:** Cache clearing flushes registered routes for the dynamically registered Yammi Audit Log UI.
- **Resolution:**
  - In Docker: `docker compose exec app php artisan audit-log:ui enable`
  - In Local: `php artisan audit-log:ui enable`

---

*Need to configure new domain modules or approval workflows? Refer to the [Developer Handbook](DEVELOPER_GUIDE.md) and [Tutorial: Building Your First Module](TUTORIAL_BUILDING_YOUR_FIRST_MODULE.md).*
