---
name: modular-architecture
description: "Apply this skill whenever creating, scaffolding, modifying, refactoring, or testing domain modules in this application using internachi/modular. Triggers: module, modular, app-modules, make:module, internachi/modular, domain module, scaffold module, modular monolith, --module, modules:sync, modules:cache, modules:list. Covers: module directory layout under app-modules/, Composer wildcard path repository configuration, mandatory composer update modules/<name> linking, Artisan make:* generators with --module=, auto-discovery of routes, views (module::view), Livewire v4 components (<x-module::... />), translations, migrations, factories, seeders, multi-tenancy company scoping, integrations with Feature Toggles, Approval Engine, REST API / Scramble, Notifications, and Pest testing."
license: MIT
metadata:
  author: eldinbiz
---

# Modular Architecture Skill (`internachi/modular`)

This skill directs AI agents on how to construct, organize, scaffold, test, and integrate domain modules using [`internachi/modular`](https://github.com/InterNACHI/modular/blob/main/README.md) within this multi-tenant Laravel scaffolding.

---

## Upstream Documentation Reference
Always consult the official repository for package internals:
- **GitHub Repository & README**: [InterNACHI/modular on GitHub](https://github.com/InterNACHI/modular/blob/main/README.md)
- **Packagist**: [internachi/modular on Packagist](https://packagist.org/packages/internachi/modular)

---

## 1. Core Directives for AI Agents

1. **Domain Code Isolation**: All business domain features (e.g. Invoices, Purchase Requests, Inventory, HR/Leave) MUST reside inside `app-modules/<module-name>/`, NEVER directly in root `app/`.
2. **Core vs Domain Boundary**:
   - `app/` is strictly for cross-cutting multi-tenant infrastructure: User/Company models, Authentication, Approval Engine core, Feature Toggles, Notification Engine, Global Layouts.
   - `app-modules/<module-name>/` houses all domain-specific models, migrations, actions, controllers, form requests, API resources, Livewire components, views, and tests.
3. **Mandatory Composer Linking**: Immediately after running `php artisan make:module <name>`, you MUST run `composer update modules/<name>` to link the module path repository into `vendor/modules/` and rebuild autoloader mappings.
4. **The 3-Layer Pattern in Modules**: Domain controllers and Livewire components must delegate persistence, multi-table transactions, and event dispatches to `Modules\<StudlyName>\Actions\*` classes.
5. **Always Use `--module=` with Artisan Generators**: When generating classes or migrations for a domain feature, ALWAYS pass `--module=<module-name>` to Laravel Artisan `make:*` commands.

---

## 2. Module Anatomy & File Structure

Every module in `app-modules/<module-name>/` follows standard Laravel conventions:

```
app-modules/<module-name>/
├── composer.json                          # Package configuration & PSR-4 autoloading
├── routes/
│   └── <module-name>-routes.php           # Web and API routes (auto-discovered)
├── resources/
│   ├── views/                             # Blade views: view('<module-name>::index')
│   │   ├── index.blade.php
│   │   ├── create.blade.php
│   │   ├── show.blade.php
│   │   └── _approval-preview.blade.php
│   ├── components/                        # Livewire SFCs (<module>/) & Blade components
│   └── lang/                              # Translations: __('<module-name>::messages.key')
├── database/
│   ├── migrations/                        # Auto-discovered database migrations
│   ├── factories/                         # Eloquent model factories
│   └── seeders/                           # Seeders (DatabaseSeeder.php, etc.)
├── src/
│   ├── Actions/                           # Domain Actions (single-purpose execute() operations)
│   │   ├── Create<Entity>Action.php
│   │   └── Update<Entity>Action.php
│   ├── Http/
│   │   ├── Controllers/                   # Web & API controllers (Thin HTTP orchestrators)
│   │   │   └── Api/v1/                    # REST API controllers
│   │   ├── Requests/                      # Form Request validation & authorization gates
│   │   └── Resources/Api/v1/              # Scramble OpenAPI Eloquent resources
│   ├── Models/                            # Eloquent domain models
│   ├── Providers/                         # Auto-discovered Service Provider
│   │   └── <StudlyName>ServiceProvider.php
│   ├── Events/                            # Domain events
│   ├── Listeners/                         # Domain event listeners
│   └── Policies/                          # Authorization policies
└── tests/
    └── Feature/                           # Pest test suite
        └── <StudlyName>Test.php
```

---

## 3. Scaffolding a New Module

When instructed to create a new module (e.g. `inventory`):

### Step 1: Run Artisan Scaffolder
```bash
php artisan make:module <module-name>
```

### Step 2: Link Composer Dependency (MANDATORY)
Root `composer.json` has a wildcard path repository (`"url": "app-modules/*"`). Link the new module immediately via:
```bash
composer update modules/<module-name>
# Or in Docker:
docker compose exec app composer update modules/<module-name>
```

> [!CAUTION]
> Skipping `composer update modules/<name>` will result in PHP `Class not found` exceptions when attempting to instantiate module classes or discover Service Providers.

---

## 4. Artisan CLI Commands with `--module`

Never manually create files without generators if an Artisan command exists. Always pass `--module=<name>`:

| Command | Example |
|---|---|
| `make:model` | `php artisan make:model Item -mfc --module=inventory` |
| `make:controller` | `php artisan make:controller ItemController --module=inventory` |
| `make:request` | `php artisan make:request StoreItemRequest --module=inventory` |
| `make:resource` | `php artisan make:resource Api/v1/ItemResource --module=inventory` |
| `make:migration` | `php artisan make:migration create_items_table --module=inventory` |
| `make:factory` | `php artisan make:factory ItemFactory --module=inventory` |
| `make:seeder` | `php artisan make:seeder ItemSeeder --module=inventory` |
| `make:test` | `php artisan make:test ItemTest --pest --module=inventory` |
| `make:policy` | `php artisan make:policy ItemPolicy --module=inventory` |
| `make:job` | `php artisan make:job ProcessRestock --module=inventory` |
| `make:mail` | `php artisan make:mail LowStockAlertMail --module=inventory` |
| `make:notification` | `php artisan make:notification ItemDepleted --module=inventory` |
| `make:command` | `php artisan make:command SyncStock --module=inventory` |
| `make:component` | `php artisan make:component StockBadge --module=inventory` |

### Seeding with `--module`
```bash
php artisan db:seed --module=<module-name>
php artisan db:seed --class=ItemSeeder --module=<module-name>
```

---

## 5. Auto-Discovery & Conventions

1. **Blade Views & Multi-Theme Portability**:
   - Stored in `resources/views/`
   - Rendered using `<module-name>::<view-name>`:
     ```php
     return view('inventory::index', ['items' => $items]);
     ```
   - **MANDATORY UI Directives:** Always construct module views using abstract **`<x-ui::*>`** components (`<x-ui::card>`, `<x-ui::button>`, `<x-ui::input>`, `<x-ui::table>`, `<x-ui::badge>`, `<x-ui::alert>`, etc.) governed by the `ui-themes` skill. Never hardcode raw theme classes (`rounded-2xl` or `border-zinc-200`) in module views.
2. **Livewire SFCs & Blade Components**:
   - Livewire Single-File Components: `resources/views/components/<module-name>/<component>.blade.php`
   - Anonymous module components: `<x-inventory::card />`
3. **Translations**:
   - Stored in `resources/lang/en/items.php`
   - Accessed via: `__('inventory::items.low_stock')`
4. **Routes**:
   - Defined in `routes/<module-name>-routes.php` (auto-loaded for both Web and `/api/v1/`).

---

## 6. Core Ecosystem Integrations

Every domain module must adhere to the application's core architecture:

### 6.1 Multi-Tenancy Scoping
- Every domain table must include:
  ```php
  $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
  ```
- In Web Controllers / Livewire, verify tenant membership:
  ```php
  $company = auth()->user()->getActiveCompany();
  if (! $company) {
      abort(403, 'No active company selected.');
  }
  ```
- In API Controllers, apply `api.company` middleware and use `app('active_company_id')`.

### 6.2 Feature Toggles
- Register in `config/features.php`:
  ```php
  'inventory' => [
      'label' => 'Inventory Management',
      'description' => 'Stock tracking and catalog management.',
      'default_enabled' => true,
      'bypass_permissions' => ['manage-inventory'],
      'setting_key' => 'feature_inventory',
  ],
  ```
- Protect routes with `feature:<module-name>` middleware.
- Wrap sidebar navigation in `@feature('<module-name>') ... @endfeature`.

### 6.3 Approval Engine (If Approvable)
- Implement `App\Contracts\Approvable` on the domain model.
- Define `getApprovalTitle()`, `getApprovalUrl()`, `getApprovalRequester()`, `getApprovalCompany()`, `getApprovalPreview()`, `onApprovalCompleted()`, `onApprovalRejected()`, `onApprovalCanceled()`, and `getApprovalTypeLabel()`.
- Submit for approval via `app(ApprovalEngine::class)->submit($model, $workflowTemplate)`.

### 6.4 REST API & OpenAPI (Scramble)
- Prefix API routes under `/api/v1/` with `auth:sanctum` and `api.company`.
- Place Eloquent API Resources in `src/Http/Resources/Api/v1/`.
- Mutating API endpoints (POST, PUT, DELETE) must inject Form Requests and delegate state mutations to `Modules\<StudlyName>\Actions\*`.

### 6.5 Notifications & Events
- Dispatch domain notifications via `NotificationRequested`:
  ```php
  event(new NotificationRequested(new NotificationPayload(...)));
  ```

---

## 7. Pest Testing Directives

- Place feature tests in `app-modules/<module-name>/tests/Feature/`.
- Test discovery is pre-configured via `phpunit.xml` (`<directory>app-modules/*/tests</directory>`) and `tests/Pest.php`.
- Run module-scoped tests:
  ```bash
  php artisan test app-modules/<module-name>/tests
  ```

---

## 8. AI Scaffolding Checklist

When asked to scaffold or build a domain module:
- [ ] 1. Scaffold module: `php artisan make:module <name>`
- [ ] 2. Link Composer (Mandatory): `composer update modules/<name>`
- [ ] 3. Register in `config/features.php`
- [ ] 4. Create model + migration + factory: `php artisan make:model <Model> -mfc --module=<name>`
- [ ] 5. Add `company_id` foreign key & tenant query scoping
- [ ] 6. Create Domain Actions in `src/Actions/` (`Create<Entity>Action.php`)
- [ ] 7. Create reactive UI using abstract `<x-ui::*>` components (governed by the `ui-themes` skill) for instant multi-theme compatibility
- [ ] 8. Expose REST API endpoints & resources in `src/Http/` (delegating to Domain Actions)
- [ ] 9. Implement `Approvable` / dispatch notifications (if applicable)
- [ ] 10. Add navigation link to `resources/views/partials/sidebar.blade.php` with `@feature`
- [ ] 11. Write Pest tests in `tests/Feature/` and verify with `composer test`
