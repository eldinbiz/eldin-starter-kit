# Modular Architecture Developer Guide (`internachi/modular`)

A comprehensive guide to the modular architecture of this application, powered by [`internachi/modular`](https://github.com/InterNACHI/modular/blob/main/README.md).

---

## Official Documentation Reference
For upstream package documentation, updates, and issue tracking, refer to the official repository:
- **GitHub Repository & Upstream README**: [InterNACHI/modular on GitHub](https://github.com/InterNACHI/modular/blob/main/README.md)
- **Packagist Package**: [internachi/modular on Packagist](https://packagist.org/packages/internachi/modular)

---

## Table of Contents
1. [Architecture Overview & Philosophy](#1-architecture-overview--philosophy)
2. [Comparison to `nwidart/laravel-modules`](#2-comparison-to-nwidartlaravel-modules)
3. [Module Lifecycle & Composer Path Repositories](#3-module-lifecycle--composer-path-repositories)
4. [Anatomy & Directory Structure of a Module](#4-anatomy--directory-structure-of-a-module)
5. [Creating a New Module](#5-creating-a-new-module)
6. [Artisan CLI Generators with `--module`](#6-artisan-cli-generators-with---module)
7. [Auto-Discovery System](#7-auto-discovery-system)
8. [Database Seeding with `--module`](#8-database-seeding-with---module)
9. [Customizing Module Stubs](#9-customizing-module-stubs)
10. [Helper & Maintenance Commands](#10-helper--maintenance-commands)
11. [Project Architectural Standards & Integrations](#11-project-architectural-standards--integrations)
    - [11.1 Multi-Tenancy Scoping](#111-multi-tenancy-scoping)
    - [11.2 Feature Toggles Integration](#112-feature-toggles-integration)
    - [11.3 Approval Engine Integration](#113-approval-engine-integration)
    - [11.4 REST API Layer & OpenAPI (Scramble)](#114-rest-api-layer--openapi-scramble)
    - [11.5 Notification Engine Integration](#115-notification-engine-integration)
    - [11.6 Navigation & Sidebar Integration](#116-navigation--sidebar-integration)
12. [Automated Testing with Pest](#12-automated-testing-with-pest)
13. [Docker & Branch Switching Workflow](#13-docker--branch-switching-workflow)
14. [Developer Scaffolding Checklist](#14-developer-scaffolding-checklist)

---

## 1. Architecture Overview & Philosophy

This project adopts a **Modular Monolith** architecture. Instead of placing all business domain features into a monolithic `app/` folder, the codebase is partitioned into two main layers:

```
┌────────────────────────────────────────────────────────┐
│                   Application Core                     │
│                        (app/)                          │
│  - Multi-tenancy context & Active Company Resolution   │
│  - Authentication & Role/Permission System             │
│  - Feature Toggle Engine (config/features.php)         │
│  - Centralized Approval Engine (Approvable contract)   │
│  - Notification Engine & Email Templates               │
│  - Global Layouts & UI Theme (TailAdmin / Tailwind v4) │
└────────────────────────────────────────────────────────┘
                           ▲
                           │ depends on core contracts
                           ▼
┌────────────────────────────────────────────────────────┐
│                   Domain Modules                       │
│                    (app-modules/)                      │
│  - app-modules/purchase-requests/                      │
│  - app-modules/invoices/                               │
│  - app-modules/inventory/                              │
│  - app-modules/leave-management/                       │
└────────────────────────────────────────────────────────┘
```

### Key Principles:
- **Native Laravel Conventions**: Modules are treated as first-class, local Composer packages. No custom loaders or foreign paradigms.
- **Package Discovery**: Uses Laravel's built-in package discovery (`extra.laravel.providers`) to boot service providers, routes, migrations, views, and translations.
- **Composer Path Repositories**: Uses Composer's standard path repository feature (`app-modules/*`) for class autoloading and dependency resolution.
- **Domain Encapsulation**: Each domain module owns its models, HTTP controllers, routes, views, database migrations, seeders, factories, and tests.

---

## 2. Comparison to `nwidart/laravel-modules`

| Feature / Aspect | `internachi/modular` (Used Here) | `nwidart/laravel-modules` |
|---|---|---|
| **Architecture** | Lightweight, native Laravel package model | Heavyweight, custom module management engine |
| **Autoloading** | Native Composer path repositories (`app-modules/*`) | Custom JSON manifest + custom autoloader |
| **Laravel Conventions** | 100% standard Laravel file names & conventions | Custom directory structure and custom Artisan commands |
| **Artisan Integration** | Enhances standard `make:*` commands with `--module=` | Replaces standard commands with `module:make-*` |
| **Dynamic Plugin CMS** | Not designed for dynamic runtime plugin uploads | Supports runtime enabling/disabling via database/JSON |
| **Extraction to Package** | Seamless: already a standard Composer package structure | Requires refactoring to publish as standalone package |

> [!NOTE]
> `internachi/modular` is designed specifically for organizing large Laravel monoliths with minimal overhead and zero custom magic.

---

## 3. Module Lifecycle & Composer Path Repositories

In the root `composer.json`, the local modules directory is registered as a wildcard path repository:

```json
"repositories": [
    {
        "type": "path",
        "url": "app-modules/*",
        "options": {
            "symlink": true
        }
    }
]
```

When a module is created, it is required in the root `composer.json`:
```json
"require": {
    "modules/purchase-requests": "*"
}
```

Composer symlinks `./app-modules/purchase-requests` into `./vendor/modules/purchase-requests`. This ensures:
1. Standard PSR-4 autoloading via `vendor/autoload.php`.
2. Instant hot-reloading (changes in `app-modules/` are immediately reflected without running `composer dump-autoload` on every edit).
3. Laravel package auto-discovery parses the module's `composer.json` on boot.

---

## 4. Anatomy & Directory Structure of a Module

Each module in `app-modules/<module-name>/` has the following structure:

```
app-modules/purchase-requests/
├── composer.json                          # Package definition & autoload mappings
├── routes/
│   └── purchase-requests-routes.php       # Web and API routes (auto-discovered)
├── resources/
│   ├── views/                             # Blade views (accessed as purchase-requests::view)
│   │   ├── index.blade.php
│   │   ├── create.blade.php
│   │   ├── show.blade.php
│   │   └── _approval-preview.blade.php
│   ├── components/                        # Anonymous Blade components (<x-purchase-requests::card />)
│   └── lang/                              # Translations (__('purchase-requests::messages.title'))
├── database/
│   ├── migrations/                        # Auto-discovered database migrations
│   ├── factories/                         # Model factories
│   └── seeders/                           # Seeders (DatabaseSeeder.php, etc.)
├── src/
│   ├── Http/
│   │   ├── Controllers/                   # Web & API controllers
│   │   │   ├── PurchaseRequestController.php
│   │   │   └── Api/
│   │   │       └── v1/
│   │   │           └── PurchaseRequestController.php
│   │   ├── Requests/                      # Form Request validation classes
│   │   └── Resources/
│   │       └── Api/
│   │           └── v1/
│   │               └── PurchaseRequestResource.php
│   ├── Models/                            # Eloquent Models
│   │   └── PurchaseRequest.php
│   ├── Providers/                         # Auto-discovered Service Provider
│   │   └── PurchaseRequestsServiceProvider.php
│   ├── Events/                            # Domain events
│   ├── Listeners/                         # Event listeners
│   └── Policies/                          # Authorization policies
└── tests/
    └── Feature/                           # Pest / PHPUnit tests
        └── PurchaseRequestTest.php
```

### Module `composer.json` Example
```json
{
    "name": "modules/purchase-requests",
    "description": "Purchase Request domain module",
    "type": "library",
    "version": "1.0",
    "license": "proprietary",
    "require": {},
    "autoload": {
        "psr-4": {
            "Modules\\PurchaseRequests\\": "src/",
            "Modules\\PurchaseRequests\\Tests\\": "tests/",
            "Modules\\PurchaseRequests\\Database\\Factories\\": "database/factories/",
            "Modules\\PurchaseRequests\\Database\\Seeders\\": "database/seeders/"
        }
    },
    "minimum-stability": "stable",
    "extra": {
        "laravel": {
            "providers": [
                "Modules\\PurchaseRequests\\Providers\\PurchaseRequestsServiceProvider"
            ]
        }
    }
}
```

---

## 5. Creating a New Module

To scaffold a new module, execute:

```bash
php artisan make:module <module-name>
```

*Example:*
```bash
php artisan make:module inventory
```

This command will:
1. Generate the standard directory layout under `app-modules/inventory/`.
2. Generate `app-modules/inventory/composer.json` with PSR-4 namespaces (`Modules\Inventory\`).
3. Generate `app-modules/inventory/src/Providers/InventoryServiceProvider.php`.
4. Register `./app-modules/inventory` into root `composer.json` under `"repositories"` and `"require"`.

### Update Composer Dependencies
After scaffolding the module, link it by running:

```bash
composer update modules/inventory
```

Or when running inside Docker:
```bash
docker compose exec app composer update modules/inventory
```

---

## 6. Artisan CLI Generators with `--module`

`internachi/modular` extends Laravel's native Artisan `make:` commands with a `--module=` option. You do not need to learn custom commands—simply append `--module=<name>`.

### Complete List of Supported Generators

| Command | Example | Generated Location |
|---|---|---|
| `make:cast` | `php artisan make:cast MoneyCast --module=inventory` | `src/Casts/MoneyCast.php` |
| `make:channel` | `php artisan make:channel StockChannel --module=inventory` | `src/Broadcasting/StockChannel.php` |
| `make:command` | `php artisan make:command SyncStock --module=inventory` | `src/Console/Commands/SyncStock.php` |
| `make:component` | `php artisan make:component StockBadge --module=inventory` | `src/View/Components/StockBadge.php` |
| `make:controller` | `php artisan make:controller ItemController --module=inventory` | `src/Http/Controllers/ItemController.php` |
| `make:event` | `php artisan make:event ItemRestocked --module=inventory` | `src/Events/ItemRestocked.php` |
| `make:exception` | `php artisan make:exception OutOfStockException --module=inventory` | `src/Exceptions/OutOfStockException.php` |
| `make:factory` | `php artisan make:factory ItemFactory --module=inventory` | `database/factories/ItemFactory.php` |
| `make:job` | `php artisan make:job ProcessRestock --module=inventory` | `src/Jobs/ProcessRestock.php` |
| `make:listener` | `php artisan make:listener NotifyPurchaser --module=inventory` | `src/Listeners/NotifyPurchaser.php` |
| `make:mail` | `php artisan make:mail LowStockAlert --module=inventory` | `src/Mail/LowStockAlert.php` |
| `make:middleware` | `php artisan make:middleware EnsureWarehouseActive --module=inventory` | `src/Http/Middleware/EnsureWarehouseActive.php` |
| `make:model` | `php artisan make:model Item -mfc --module=inventory` | `src/Models/Item.php`, migration, factory, controller |
| `make:notification` | `php artisan make:notification ItemDepleted --module=inventory` | `src/Notifications/ItemDepleted.php` |
| `make:observer` | `php artisan make:observer ItemObserver --module=inventory` | `src/Observers/ItemObserver.php` |
| `make:policy` | `php artisan make:policy ItemPolicy --module=inventory` | `src/Policies/ItemPolicy.php` |
| `make:provider` | `php artisan make:provider EventServiceProvider --module=inventory` | `src/Providers/EventServiceProvider.php` |
| `make:request` | `php artisan make:request StoreItemRequest --module=inventory` | `src/Http/Requests/StoreItemRequest.php` |
| `make:resource` | `php artisan make:resource Api/v1/ItemResource --module=inventory` | `src/Http/Resources/Api/v1/ItemResource.php` |
| `make:rule` | `php artisan make:rule ValidSku --module=inventory` | `src/Rules/ValidSku.php` |
| `make:seeder` | `php artisan make:seeder ItemSeeder --module=inventory` | `database/seeders/ItemSeeder.php` |
| `make:test` | `php artisan make:test ItemTest --pest --module=inventory` | `tests/Feature/ItemTest.php` |

---

## 7. Auto-Discovery System

`internachi/modular` automatically discovers and boots the following resources for every module:

### 1. Routes
Route files located in `routes/` (such as `routes/<module-name>-routes.php`, `routes/web.php`, or `routes/api.php`) are loaded automatically into the router.

### 2. Blade Views
Views located in `resources/views/` are registered under the module's kebab-case namespace:
```php
// Loads app-modules/purchase-requests/resources/views/index.blade.php
return view('purchase-requests::index', ['items' => $items]);
```

### 3. Blade Components
- **Class-Based Components** (`src/View/Components/StockBadge.php`):
  ```blade
  <x-inventory::stock-badge :item="$item" />
  ```
- **Anonymous Blade Components** (`resources/components/item-card.blade.php`):
  ```blade
  <x-inventory::item-card :title="$item->name" />
  ```
- **Nested Anonymous Components** (`resources/components/tables/row.blade.php`):
  ```blade
  <x-inventory::tables.row />
  ```

### 4. Database Migrations
All migration files in `database/migrations/` are automatically discovered and executed whenever `php artisan migrate` is run.

### 5. Factories & Seeders
Factories in `database/factories/` and seeders in `database/seeders/` are autoloaded under `Modules\<ModuleName>\Database\Factories\` and `Modules\<ModuleName>\Database\Seeders\`.

### 6. Translations & Localization
Translation files in `resources/lang/` are automatically available via the module namespace:
```php
// app-modules/inventory/resources/lang/en/items.php
__('inventory::items.out_of_stock')
```

### 7. Policies, Listeners & Commands
- Eloquent Policies matching model names (`ItemPolicy` for `Item`) are auto-discovered.
- Event listeners in `src/Listeners/` and Artisan commands in `src/Console/Commands/` are automatically registered.

---

## 8. Database Seeding with `--module`

You can execute seeders specific to a single module without running the entire application seeder:

```bash
# Runs Modules\<ModuleName>\Database\Seeders\DatabaseSeeder
php artisan db:seed --module=purchase-requests

# Runs a specific seeder class inside the module
php artisan db:seed --class=PurchaseRequestSeeder --module=purchase-requests
```

---

## 9. Customizing Module Stubs

You can customize the initial boilerplate generated when running `php artisan make:module`.

### Publish Config
```bash
php artisan vendor:publish --tag=modular-config
```
This publishes `config/app-modules.php`.

### Available Stub Placeholders
In custom stub templates, you can use the following placeholders:

| Placeholder | Description | Example Value (`my-module`) |
|---|---|---|
| `StubBasePath` | Base module path | `app-modules/my-module` |
| `StubModuleNamespace` | PSR-4 PHP Namespace | `Modules\MyModule` |
| `StubComposerNamespace` | Escaped composer namespace | `Modules\\MyModule\\` |
| `StubModuleName` | Kebab-case module name | `my-module` |
| `StubModuleNameSingular` | Singular name | `my module` |
| `StubModuleNamePlural` | Plural name | `my modules` |
| `StubClassNamePrefix` | StudlyCase prefix | `MyModule` |
| `StubComposerName` | Composer package name | `modules/my-module` |
| `StubMigrationPrefix` | Timestamp prefix | `2026_08_18_090000` |
| `StubFullyQualifiedTestCaseBase` | Fully qualified base test class | `Tests\TestCase` |
| `StubTestCaseBase` | Short base test class name | `TestCase` |

---

## 10. Helper & Maintenance Commands

| Command | Purpose |
|---|---|
| `php artisan modules:list` | Displays a table of all discovered modules, their paths, namespaces, and status. |
| `php artisan modules:sync` | Synchronizes project configurations (updates `phpunit.xml` test suites and IDE configurations). |
| `php artisan modules:cache` | Caches discovered module metadata for optimal production performance. |
| `php artisan modules:clear` | Clears the module discovery cache. |

---

## 11. Project Architectural Standards & Integrations

When creating a new domain module in this repository, follow these cross-cutting integration guidelines:

### 11.1 Multi-Tenancy Scoping
All domain models must belong to a Company:
1. Add `company_id` foreign key to migrations:
   ```php
   $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
   ```
2. In Web Controllers, resolve the active company via the authenticated user:
   ```php
   $company = auth()->user()->getActiveCompany();
   if (!$company) {
       abort(403, 'No active company selected.');
   }
   ```
3. In API Controllers, apply the `api.company` middleware and retrieve the active company ID:
   ```php
   $companyId = app('active_company_id');
   ```

---

### 11.2 Feature Toggles Integration
Every domain module must be registered in the centralized Feature Toggle system:

1. Register the module in `config/features.php`:
   ```php
   'inventory' => [
       'label' => 'Inventory Management',
       'description' => 'Track warehouses, stock movements, and item catalogs.',
       'default_enabled' => true,
       'bypass_permissions' => ['manage-inventory'],
       'setting_key' => 'feature_inventory',
   ],
   ```
2. Protect web routes in `routes/<module>-routes.php`:
   ```php
   Route::middleware(['web', 'auth', 'feature:inventory'])->prefix('inventory')->name('inventory.')->group(function () {
       // ...
   });
   ```
3. Gate sidebar UI in `resources/views/partials/sidebar.blade.php`:
   ```blade
   @feature('inventory')
       <a href="{{ route('inventory.index') }}">Inventory</a>
   @endfeature
   ```
4. *See [docs/developer-guide-feature-toggles.md](developer-guide-feature-toggles.md) for full details.*

---

### 11.3 Approval Engine Integration
If the module represents a document requiring multi-step approvals (e.g. Purchase Requests, Expenses):

1. Implement `App\Contracts\Approvable` on the Model:
   ```php
   namespace Modules\PurchaseRequests\Models;
   
   use App\Contracts\Approvable;
   use App\Models\Approval\ApprovalRequest;
   use Illuminate\Database\Eloquent\Model;
   use Illuminate\Database\Eloquent\Relations\MorphMany;
   
   class PurchaseRequest extends Model implements Approvable
   {
       public function approvalRequests(): MorphMany
       {
           return $this->morphMany(ApprovalRequest::class, 'approvable');
       }
       
       public function getApprovableTitle(): string { return $this->title; }
       public function getApprovableTotal(): ?float { return (float) $this->total_amount; }
       public function getApprovableDepartment(): ?string { return $this->department; }
       public function getApprovableCompanyId(): int { return $this->company_id; }
       public function getApprovableRequesterId(): int { return $this->requester_id; }
       public function getApprovableUrl(): string { return route('purchase-requests.show', $this->id); }
       
       public function getApprovablePreviewData(): array
       {
           return [
               'view' => 'purchase-requests::_approval-preview',
               'data' => ['purchaseRequest' => $this],
           ];
       }
       
       public function onApprovalCompleted(): void
       {
           $this->update(['status' => 'approved']);
       }
       
       public function onApprovalRejected(?string $reason = null): void
       {
           $this->update(['status' => 'rejected']);
       }
   }
   ```
2. Submit for approval in Controller:
   ```php
   public function submitForApproval(PurchaseRequest $purchaseRequest, ApprovalEngine $engine): RedirectResponse
   {
       $engine->submit($purchaseRequest, auth()->user());
       return redirect()->route('purchase-requests.show', $purchaseRequest->id);
   }
   ```

---

### 11.4 REST API Layer & OpenAPI (Scramble)
Expose domain endpoints under `/api/v1/` matching Scramble documentation conventions:

1. Define API routes in `routes/<module>-routes.php`:
   ```php
   Route::middleware(['api', 'auth:sanctum', 'api.company'])->prefix('api/v1')->group(function () {
       Route::get('inventory', [ItemApiController::class, 'index'])->middleware('ability:inventory:index');
       Route::get('inventory/{item}', [ItemApiController::class, 'show'])->middleware('ability:inventory:show');
   });
   ```
2. Create Eloquent API Resources in `src/Http/Resources/Api/v1/`:
   ```php
   namespace Modules\Inventory\Http\Resources\Api\v1;
   
   use Illuminate\Http\Request;
   use Illuminate\Http\Resources\Json\JsonResource;
   
   class ItemResource extends JsonResource
   {
       public function toArray(Request $request): array
       {
           return [
               'id' => $this->id,
               'sku' => $this->sku,
               'name' => $this->name,
               'created_at' => $this->created_at->toISOString(),
           ];
       }
   }
   ```
3. *See [docs/API_LAYER_GUIDE.md](API_LAYER_GUIDE.md) for full details.*

---

### 11.5 Notification Engine Integration
Trigger email and in-app notifications using `NotificationRequested` events:

```php
use App\Events\NotificationRequested;
use App\Services\Notifications\NotificationPayload;

event(new NotificationRequested(
    new NotificationPayload(
        eventType: 'inventory.low_stock',
        companyId: $company->id,
        recipientUserIds: [$manager->id],
        variables: [
            'item_name' => $item->name,
            'current_stock' => $item->stock_quantity,
        ],
        actionUrl: route('inventory.show', $item->id),
        mailableClass: LowStockAlertMail::class,
    )
));
```
*See [docs/developer-guide-notifications-and-emails.md](developer-guide-notifications-and-emails.md) for full details.*

---

### 11.6 Navigation & Sidebar Integration
Add navigation links inside `resources/views/partials/sidebar.blade.php` wrapped in `@feature` and active route highlights:

```blade
@feature('purchase-requests')
    <li>
        <a
            href="{{ route('purchase-requests.index') }}"
            class="menu-item group {{ Request::is('purchase-requests*') ? 'menu-item-active' : 'menu-item-inactive' }}"
        >
            <x-icons.document-text class="{{ Request::is('purchase-requests*') ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}" />
            <span>Purchase Requests</span>
        </a>
    </li>
@endfeature
```

---

## 12. Automated Testing with Pest

Tests for modules reside in `app-modules/<module-name>/tests/`. 

### Pest Configuration
The project is pre-configured to discover module tests:
1. `phpunit.xml` includes:
   ```xml
   <testsuite name="Modules">
       <directory>app-modules/*/tests</directory>
   </testsuite>
   ```
2. `tests/Pest.php` binds the base `TestCase`:
   ```php
   pest()->extend(TestCase::class)
       ->in('Feature', '../app-modules/*/tests');
   ```

### Running Tests
```bash
# Run all tests (Core + All Modules)
composer test

# Run tests for a specific module
php artisan test app-modules/purchase-requests/tests

# Filter tests by name
php artisan test --filter=PurchaseRequestTest
```

### Writing a Module Pest Test
```php
<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\PurchaseRequests\Models\PurchaseRequest;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->create();
    $this->user->companies()->attach($this->company->id, ['status' => 'active']);
});

test('user can view purchase requests list', function (): void {
    $this->actingAs($this->user);
    session(['active_company_id' => $this->company->id]);

    PurchaseRequest::factory()->create([
        'company_id' => $this->company->id,
        'requester_id' => $this->user->id,
        'title' => 'MacBook Pro M3 Max',
    ]);

    $this->get(route('purchase-requests.index'))
        ->assertOk()
        ->assertSee('MacBook Pro M3 Max');
});
```

---

## 13. Docker & Branch Switching Workflow

When developing in Docker or switching git branches:

```bash
# After pulling a branch containing new modules or composer changes:
docker compose exec app composer install --no-interaction
docker compose exec app php artisan optimize:clear
docker compose exec app php artisan migrate
docker compose restart queue
```

> [!TIP]
> If a newly created module is not discovered, run `docker compose exec app composer update modules/<module-name>` or `php artisan modules:clear`.

---

## 14. Developer Scaffolding Checklist

When building a new business feature as a module, follow this 10-step checklist:

- [ ] **1. Scaffold Module**: Run `php artisan make:module <name>`
- [ ] **2. Link Composer**: Run `composer update modules/<name>`
- [ ] **3. Register Feature Toggle**: Add module definition to `config/features.php`
- [ ] **4. Create Database Migration & Model**: `php artisan make:model <Model> -mfc --module=<name>`
- [ ] **5. Add Multi-Tenancy Scoping**: Add `company_id` foreign key and tenant query scopes
- [ ] **6. Build Web Routes & Views**: Add routes in `routes/<name>-routes.php` and Blade views in `resources/views/`
- [ ] **7. Expose REST API (Optional)**: Add API controller, Form Requests, and Eloquent Resource in `src/Http/`
- [ ] **8. Hook Into Approvals / Notifications (Optional)**: Implement `Approvable` and dispatch `NotificationRequested`
- [ ] **9. Add Sidebar Link**: Add navigation link in `resources/views/partials/sidebar.blade.php` wrapped in `@feature('<name>')`
- [ ] **10. Write Pest Tests**: Create tests in `tests/Feature/` and run `composer test`
