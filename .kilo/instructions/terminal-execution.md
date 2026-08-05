# Terminal Command Execution Rule

This rule strictly governs all interactions requiring command line / terminal execution, ensuring the user retains full control over command execution while allowing the agent to run commands when requested for output review and debugging.

## 1. Always Ask User Preference Before Execution

Whenever a terminal command needs to be executed (whether read-only or state-modifying):

- **Display the Command:** Present the exact command clearly in a code block with context on what it does and why it is needed.
- **Ask the User:** Always ask the user whether they prefer to:
  1. **Run the command themselves** in their terminal, OR
  2. **Let the agent execute the command** on their behalf so the agent can capture output, analyze logs, and automatically debug/fix any issues or errors.

## 2. Command Categories Reference

When asking the user, provide context on what the command does:

| Category | Examples |
|---|---|
| **Package Management** | `composer require lab404/laravel-impersonate`, `npm install ...` |
| **Schema & Migrations** | `php artisan migrate`, `php artisan migrate:fresh` |
| **Data Seeding & Reset** | `php artisan db:seed`, `php artisan migrate:fresh --seed` |
| **Tests & Verification** | `composer test`, `vendor/bin/pest` |
| **Environment & Maintenance** | `php artisan key:generate`, `php artisan storage:link`, `php artisan config:clear` |
| **Git Operations** | `git status`, `git diff`, `git push`, `git reset` |

## 3. Output Review & Automated Debugging

When the user chooses to let the agent run the command:
- Execute the command via tools.
- Carefully inspect stdout/stderr.
- If errors occur, analyze the root cause and propose or apply fixes immediately.

## 4. Windows Path Separators (Vendor Binaries)

On Windows, backslash paths in PHP/Composer commands are subject to escape-sequence interpretation. Always use **forward slashes** for vendor binary paths:

```
# Correct (forward slashes — PHP on Windows handles these natively)
php vendor/bin/pint --dirty --format agent
php vendor/bin/phpunit
php vendor/bin/pest

# Incorrect (backslashes — \b, \t, \n etc. get mangled)
php vendor\bin\pint --dirty --format agent
```
