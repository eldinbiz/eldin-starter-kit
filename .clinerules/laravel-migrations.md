# Laravel Migration File Generation Rule

This rule strictly governs how migration files are generated via `php artisan make:migration`, preventing timestamp collisions and ensuring correct migration ordering.

## 1. Always Use Artisan for File Generation

Never create migration files manually. Always use `php artisan make:migration` for consistent naming and correct timestamp prefixes.

Incorrect:
```
# Manually creating a file:
database/migrations/create_users_table.php  ← no timestamp prefix
```

Correct:
```bash
php artisan make:migration create_users_table
```

## 2. Timestamp Collision Prevention

`php artisan make:migration` generates a `YYYY_MM_DD_HHMMSS` timestamp prefix. Running two `make:migration` commands within the same second produces identical timestamps, which can cause incorrect migration ordering when Laravel sorts alphabetically as a tie-breaker.

### Required Mitigations

| Rule | Description |
|---|---|
| **One at a time** | Generate one migration, confirm file creation, then generate the next. Never batch multiple `make:migration` calls without a pause. |
| **Insert a pause when batching** | If multiple migrations must be generated in a single step, insert a 1-second delay between each `make:migration` call: on Windows use `timeout /t 1 /nobreak >nul`, on Unix use `sleep 1`. |
| **Ordering check** | After generating multiple related migrations, verify parent table migrations have earlier timestamps than child table migrations (tables with foreign keys must be created first). If a collision is detected, rename the affected file by incrementing the seconds portion of the timestamp. |

### Collision Resolution Example

If both `create_users_table` and `create_posts_table` end up with timestamp `2026_07_17_144500`:
```bash
# Rename the dependent (child) migration to sort after the parent:
mv database/migrations/2026_07_17_144500_create_posts_table.php \
   database/migrations/2026_07_17_144501_create_posts_table.php
```

## 3. Never Run Database-Mutating Migration Commands

**Do not execute** `php artisan migrate`, `php artisan migrate:fresh`, `php artisan migrate:rollback`, or `php artisan migrate:refresh`. These are destructive operations governed by `terminal_execution.md`. Delegate all migration execution to the user.

When migrations have been generated and are ready to run, present the user with:
- The exact `php artisan migrate` command.
- A summary of what each new migration does.
- A warning about any irreversible `down()` methods.

## 4. Cross-References

- **Schema content best practices** (column types, indexes, foreign keys, reversible `down()`, focused migrations): consult `.cline/skills/laravel-best-practices/rules/migrations.md` before writing migration content.
- **Command execution delegation**: all `migrate*` commands are delegated per `.clinerules/terminal_execution.md`.
- **Database performance** (column selection, eager loading, chunking): see `.clinerules/laravel-db-performance.md`.
