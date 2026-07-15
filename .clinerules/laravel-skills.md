# Laravel On-Demand Skills Rule

This project maintains additional Laravel best-practice rule files in `.cline/skills/laravel-best-practices/rules/`. These are not auto-loaded (to preserve context window), but must be consulted when relevant to the task at hand.

## When to read a rule file

Before writing code that touches any of the concerns below, read the corresponding rule file from `.cline/skills/laravel-best-practices/rules/`:

| Concern | Read |
| --- | --- |
| Form Requests and validation rules | [`validation.md`](.cline/skills/laravel-best-practices/rules/validation.md) |
| Controllers, route binding, resources, middleware | [`routing.md`](.cline/skills/laravel-best-practices/rules/routing.md) |
| Schema changes, columns, foreign keys, indexes | [`migrations.md`](.cline/skills/laravel-best-practices/rules/migrations.md) |
| Jobs, retries, uniqueness, batches, Horizon | [`queue-jobs.md`](.cline/skills/laravel-best-practices/rules/queue-jobs.md) |
| Cache lifetime, invalidation, locks, memoization | [`caching.md`](.cline/skills/laravel-best-practices/rules/caching.md) |
| Outbound requests, retries, timeouts, fakes | [`http-client.md`](.cline/skills/laravel-best-practices/rules/http-client.md) |
| Exceptions, reporting, rendering, log context | [`error-handling.md`](.cline/skills/laravel-best-practices/rules/error-handling.md) |
| Events and notifications | [`events-notifications.md`](.cline/skills/laravel-best-practices/rules/events-notifications.md) |
| Mailables and mail assertions | [`mail.md`](.cline/skills/laravel-best-practices/rules/mail.md) |
| Scheduled tasks and overlap protection | [`scheduling.md`](.cline/skills/laravel-best-practices/rules/scheduling.md) |
| Collections, lazy iteration, bulk operations | [`collections.md`](.cline/skills/laravel-best-practices/rules/collections.md) |
| Blade components, attributes, composers | [`blade-views.md`](.cline/skills/laravel-best-practices/rules/blade-views.md) |
| Environment values and application configuration | [`config.md`](.cline/skills/laravel-best-practices/rules/config.md) |
| Naming, helpers, file boundaries, PHP style | [`style.md`](.cline/skills/laravel-best-practices/rules/style.md) |
| Actions, services, dependencies, application structure | [`architecture.md`](.cline/skills/laravel-best-practices/rules/architecture.md) |

## Usage

When a task involves one of these concerns, read the relevant rule file(s) with `read_file` before making changes. The master SKILL.md at `.cline/skills/laravel-best-practices/SKILL.md` also provides overarching guidance.
