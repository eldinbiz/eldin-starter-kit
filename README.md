# Scaffolding App

## Start Developing

```bash
copy .env.example .env
docker compose up -d --build
```

Then edit `.env` with these changes:

```
DB_CONNECTION=mariadb
DB_HOST=mariadb
DB_DATABASE=laravel
DB_USERNAME=laravel
DB_PASSWORD=laravel
QUEUE_CONNECTION=database
CACHE_STORE=database
SESSION_DRIVER=database
```

Then run first-time setup:

```bash
docker compose exec app php artisan key:generate
docker compose exec app php artisan storage:link
docker compose exec app php artisan migrate
```

Open **http://localhost** — you're done.

---

## What You Get

| Service | URL |
|---|---|
| App (Nginx + PHP-FPM) | http://localhost |
| Vite dev server (HMR) | http://localhost:5173 |
| MariaDB | localhost:3306 |

**Hot reload:** edit any PHP, Blade, CSS, or JS file — changes appear instantly, no restarts needed.

---

## Daily Commands

```bash
docker compose up -d          # start
docker compose down           # stop
docker compose logs -f        # see all logs
docker compose exec app php artisan ...   # run artisan
docker compose exec app composer ...      # run composer
```

---

## Queue Worker

```bash
docker compose logs -f queue              # watch worker logs
docker compose exec app php artisan queue:failed   # see failed jobs
docker compose exec app php artisan queue:retry all  # retry all
docker compose up -d --scale queue=3      # run 3 workers
