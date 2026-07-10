# Terminal Command Execution Rule

This rule strictly governs all interactions requiring command line / terminal execution, ensuring destructive operations are never executed without user oversight.

## 1. Non-Destructive Commands (Agent May Execute)

The agent is permitted to run **safe, read-only, or environment-scoped** commands directly, including:

- `php artisan route:list` — listing routes
- `php artisan list` — listing available commands
- `php artisan about` — environment info
- `php -v`, `node -v`, `composer --version` — version checks
- `git status`, `git log`, `git diff` — read-only git operations
- `ls`, `dir`, `cat`, `type` — filesystem reads
- `php artisan tinker --execute="..."` — read-only tinker queries
- `composer install` (when adding/updating non-destructive dependencies)
- `npm install` (non-destructive package additions)

## 2. Destructive Commands (MUST Delegate to User)

Commands that **mutate the database, delete data, alter schema, or change system state** must NEVER be executed by the agent. These include:

| Category | Examples |
|---|---|
| **Schema changes** | `php artisan migrate`, `php artisan migrate:fresh`, `php artisan migrate:rollback` |
| **Data seeding** | `php artisan db:seed`, `php artisan migrate:fresh --seed` |
| **Data deletion** | `php artisan db:wipe`, truncate/delete SQL via tinker |
| **Environment mutation** | `php artisan key:generate`, `php artisan storage:link` |
| **Tests (side effects)** | `php artisan test` (may use test DB, treat as potentially destructive) |
| **Cache clearing (production)** | `php artisan config:clear`, `php artisan cache:clear` (safe locally, delegate in production context) |
| **Destructive Git** | `git push`, `git reset --hard`, `git clean -fd` |
| **Package removal** | `composer remove ...`, `npm uninstall ...` |

When the agent determines a command falls into this category, it must:
- Display the exact command clearly in a code block.
- Explain what the command does, why it is needed, and any expected output.
- Instruct the user to run the command in their own terminal.
- Wait for user confirmation before proceeding with any dependent next steps.

## 3. Ambiguous Commands

If a command's destructiveness is unclear, the agent must **default to delegation** — present it to the user and explain the ambiguity rather than risk unintended data loss.
