# Developer & Architecture Guide

Welcome to the application architecture and developer handbook. This guide defines the foundational design patterns, directory conventions, and coding standards used across both core platform services (`app/`) and domain modules (`app-modules/`).

---

## Table of Contents
1. [Core Architectural Philosophy](#1-core-architectural-philosophy)
2. [The 3-Layer Standard (Presentation, Validation, Domain Actions)](#2-the-3-layer-standard)
3. [Modular Monolith Architecture (`app-modules/`)](#3-modular-monolith-architecture)
4. [Multi-Tenancy & Authorization Boundaries](#4-multi-tenancy--authorization-boundaries)
5. [Core Platform Subsystem Integrations](#5-core-platform-subsystem-integrations)
6. [PHP 8.4 Standards & Code Quality](#6-php-84-standards--code-quality)
7. [Testing & Quality Assurance](#7-testing--quality-assurance)
8. [End-to-End Feature Implementation Blueprint](#8-end-to-end-feature-implementation-blueprint)

---

## 1. Core Architectural Philosophy

This project is built as a **Modular Monolith** using **Laravel 13**, **Livewire v4**, **PHP 8.4**, **Tailwind CSS v4**, and [`internachi/modular`](https://github.com/InterNACHI/modular). 

To ensure long-term maintainability, testability, and reuse across Web SPA, REST API, CLI, and Queue layers, we enforce a strict separation of concerns:

- **Core vs. Domain Boundary**:
  - `app/` is reserved for cross-cutting multi-tenant platform infrastructure (Authentication, User/Company models, Roles & Permissions, Approval Engine, Notification Engine, System Settings, Feature Toggles).
  - `app-modules/<module-name>/` houses all isolated business domains (e.g. Purchase Requests, Invoices, HR/Leave, Inventory).
- **The 3-Layer + Dual Presentation Architecture**:
  - **Presentation Layer (Web SPA & REST API)**:
    - **Livewire v4 Components**: Reactive Single Page Application (SPA) orchestrators managing UI state, inputs, search debouncing, and modal dialogs with instant `wire:navigate` transitions.
    - **REST API v1 Controllers**: Stateless, rate-limited HTTP endpoints for external integrations, mobile clients, and system bots authenticated via Sanctum Bearer tokens and `X-Company-ID`.
    - **Thin HTTP Controllers**: Minimal orchestrators for traditional file downloads, exports, and redirect flows.
  - **Validation & Authorization Layer**:
    - **Form Requests & Livewire Validation**: Form Requests (`app/Http/Requests` or `Modules\<Module>\Http\Requests`) enforce permission gates and provide validation rules reflected by Dedoc Scramble for OpenAPI documentation.
  - **Domain Business Layer**:
    - **Domain Actions**: Single-purpose, transaction-safe classes (`app/Actions` or `Modules\<Module>\Actions`) that encapsulate all state-modifying business logic, multi-table database transactions, and event dispatches.

```
┌──────────────────────────────────────┐     ┌──────────────────────────────────────┐
│       Web SPA (Browser Users)        │     │     External API / System Bots       │
│   Livewire v4 Component / Alpine     │     │    Sanctum Token + X-Company-ID      │
└──────────────────┬───────────────────┘     └──────────────────┬───────────────────┘
                   │                                            │
                   │ validates input                            │ validates FormRequest
                   ▼                                            ▼
┌───────────────────────────────────────────────────────────────────────────────────┐
│                     1. Thin Presentation & Validation Layer                       │
│ - Livewire Component methods (save, submit) / API Controllers (store, update)     │
│ - Livewire #[Validate] / Form Requests (authorize() & rules())                     │
└─────────────────────────────────────────┬─────────────────────────────────────────┘
                                          │
                                          │ delegates execution ($action->execute())
                                          ▼
┌───────────────────────────────────────────────────────────────────────────────────┐
│                      2. Domain Business Layer (Domain Actions)                    │
│ ┌───────────────────────────────────────────────────────────────────────────────┐ │
│ │  DB::transaction(), Eloquent mutations, Approval Engine, Events, Notifications│ │
│ └───────────────────────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────┬─────────────────────────────────────────┘
                                          │
                   ┌──────────────────────┴──────────────────────┐
                   │                                             │
                   ▼                                             ▼
┌──────────────────────────────────────┐     ┌──────────────────────────────────────┐
│        Reactive DOM Update           │     │          JSON HTTP Response          │
│   (Livewire Morph / Alpine state)    │     │      (Eloquent API JsonResource)     │
└──────────────────────────────────────┘     └──────────────────────────────────────┘
```

---

## 2. The 3-Layer Standard

### Layer 1: Form Requests (`app/Http/Requests/...` or `Modules\<Module>\Http\Requests\...`)
- **Responsibility**: Validate input parameters and verify that the authenticated user is authorized to perform the operation.
- **Rules**:
  - Must declare `declare(strict_types=1);` and be `final class`.
  - Use `authorize(): bool` to check Spatie permissions (`$this->user()?->can(...)`), platform access (`$this->user()?->hasPlatformAccess()`), or active company ownership.
  - Return typed arrays from `rules(): array`.
  - **NO** database mutations or heavy business logic inside Form Requests.

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Users;

use Illuminate\Foundation\Http\FormRequest;

final class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-users')
            || ($this->user()?->hasPlatformAccess() ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'role_id' => ['nullable', 'integer', 'exists:roles,id'],
        ];
    }
}
```

---

### Layer 2: Domain Actions (`app/Actions/...` or `Modules\<Module>\Actions\...`)
- **Responsibility**: Pure, single-purpose classes that execute a discrete business operation.
- **Rules**:
  - Must declare `declare(strict_types=1);` and be `final class`.
  - Expose a single public method: `execute(...)` with explicit parameter and return types.
  - Wrap multi-table modifications inside `DB::transaction()`.
  - Handle file storage, pivot table assignments, notifications, and event dispatches.
  - Actions must never interact with the HTTP `Request` or `Session` directly; accept explicit typed arguments or arrays.

```php
<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final class CreateUserAction
{
    /**
     * @param array{
     *     name: string,
     *     email: string,
     *     password: string,
     *     company_id?: int|null,
     *     role_id?: int|null
     * } $data
     */
    public function execute(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            if (! empty($data['company_id'])) {
                $user->companies()->attach($data['company_id'], ['status' => 'active']);

                if (! empty($data['role_id'])) {
                    $user->companyRoles()->attach($data['role_id'], [
                        'company_id' => $data['company_id'],
                    ]);
                }
            }

            return $user;
        });
    }
}
```

---

### Layer 3: Presentation Layer (Livewire v4 Components & Thin Controllers)
- **Livewire v4 Components**: Manage interactive SPA UI state. In mutating actions, Livewire components **must delegate** to Domain Actions:
  ```php
  public function save(CreateUserAction $action): void
  {
      $validated = $this->validate();
      $action->execute($validated);
      $this->dispatch('user-saved');
  }
  ```
- **Thin Controllers**: Used for REST APIs or non-SPA actions. Must inject Form Requests and Domain Actions without inline business logic:
  ```php
  final class UserController extends Controller
  {
      public function store(StoreUserRequest $request, CreateUserAction $action): RedirectResponse
      {
          $action->execute($request->validated());

          return redirect()->route('admin.users.index')
              ->with('success', 'User created successfully.');
      }
  }
  ```

---

## 3. Modular Monolith Architecture

All domain features are isolated inside the `app-modules/` directory.

### Module Anatomy
```text
app-modules/<module-name>/
├── composer.json                          # Module package & PSR-4 autoloading
├── routes/
│   └── <module-name>-routes.php           # Auto-discovered web and api routes
├── resources/
│   ├── views/                             # Module views: view('<module>::index')
│   │   ├── index.blade.php
│   │   └── create.blade.php
│   ├── components/                        # Livewire SFCs (<module>/) & Blade components
│   └── lang/                              # Translations: __('<module>::messages.key')
├── database/
│   ├── migrations/                        # Auto-discovered migrations
│   ├── factories/                         # Model factories
│   └── seeders/                           # Module seeders
├── src/
│   ├── Actions/                           # Domain Actions (single-purpose execute() methods)
│   │   ├── Create<Entity>Action.php
│   │   └── Update<Entity>Action.php
│   ├── Http/
│   │   ├── Controllers/                   # Web & API controllers
│   │   │   └── Api/v1/                    # REST API controllers
│   │   ├── Requests/                      # Form Requests
│   │   └── Resources/Api/v1/              # Scramble OpenAPI Eloquent resources
│   ├── Models/                            # Domain Eloquent models
│   ├── Providers/                         # Auto-discovered Service Provider
│   │   └── <StudlyName>ServiceProvider.php
│   ├── Events/                            # Domain events
│   ├── Listeners/                         # Event listeners
│   └── Policies/                          # Authorization policies
└── tests/
    └── Feature/                           # Pest test suite
        └── <StudlyName>Test.php
```

> [!IMPORTANT]
> **Mandatory Composer Linking Step**:
> Whenever you scaffold a new module using `php artisan make:module <name>`, you **MUST** run:
> ```bash
> composer update modules/<name>
> # Or in Docker:
> docker compose exec app composer update modules/<name>
> ```
> This command creates the Composer path repository symlink and compiles the autoloader. Skipping this step will cause `Class not found` or Service Provider discovery errors.

### Artisan Generators with Modules
Always specify `--module=` when running `make:*` commands:
```bash
# Inside Docker: docker compose exec app php artisan ...
php artisan make:model Invoice -mfc --module=invoices
php artisan make:request StoreInvoiceRequest --module=invoices
php artisan make:controller InvoiceController --module=invoices
php artisan make:test --pest InvoiceWorkflowTest --module=invoices
```

---

## 4. Multi-Tenancy & Authorization Boundaries

This application enforces tenant isolation across companies and company hierarchies:

1. **Active Company Context**:
   - In Web requests: `$company = auth()->user()?->getActiveCompany();`
   - In API requests: Resolved automatically via `api.company` middleware and `X-Company-ID` header.
2. **Company Hierarchy Support**:
   - Use `$company->allAncestors()` and `$company->allDescendants()` when resolving multi-tier policies or reporting lines.
3. **Authorization Rules**:
   - Platform Admin bypass: Users with `$user->hasPlatformAccess()` can oversee global settings and cross-company records.
   - Company Admin & Standard Users: Scoped strictly to records where `company_id === $activeCompany->id`.

---

## 5. Core Platform Subsystem Integrations

### A. Approval Engine (`App\Services\ApprovalEngine`)
When a domain model requires multi-step sequential or parallel approvals:
1. Implement the `App\Contracts\Approvable` interface on the Model.
2. Submit for approval via `ApprovalEngine`:
   ```php
   app(ApprovalEngine::class)->submit($model, $workflowTemplate);
   ```

### B. Feature Toggles (`config/features.php` & `@feature`)
1. Register features in `config/features.php`.
2. Protect routes with `feature:<key>` middleware:
   ```php
   Route::middleware(['auth', 'feature:purchaserequests'])->group(...);
   ```
3. Conditionally render UI elements in Blade:
   ```blade
   @feature('purchaserequests')
       <a href="{{ route('purchaserequests.index') }}" wire:navigate>Purchase Requests</a>
   @endfeature
   ```

### C. Notification Engine (`App\Services\NotificationEngine`)
Dispatch events implementing `NotificationPayload` or fire `NotificationRequested`:
```php
use App\Events\NotificationRequested;
use App\ValueObjects\NotificationPayload;

event(new NotificationRequested(
    new NotificationPayload(
        userId: $approver->id,
        companyId: $company->id,
        type: 'approval_required',
        title: 'Action Required',
        message: 'A new purchase request requires your approval.',
        actionUrl: route('approvals.show', $approvalRequest),
        metadata: ['request_id' => $approvalRequest->id]
    )
));
```

---

## 6. PHP 8.4 Standards & Code Quality

1. **Strict Types**:
   Every PHP file MUST begin with:
   ```php
   <?php

   declare(strict_types=1);
   ```
2. **Constructor Property Promotion & Asymmetric Visibility**:
   ```php
   public function __construct(
       public readonly string $title,
       private readonly ApprovalEngine $engine,
   ) {}
   ```
3. **Explicit Return Types & PHPDoc Array Shapes**:
   ```php
   /**
    * @param array{name: string, is_active?: bool} $data
    */
    public function execute(Company $company, array $data): WorkflowTemplate
   ```
4. **Code Formatter (Laravel Pint)**:
   Always format code before committing:
   ```bash
   docker compose exec app vendor/bin/pint --format agent
   ```

---

## 7. Testing & Quality Assurance

We enforce Test-Driven Development (TDD) using **Pest 5 / PHPUnit 12**.

Test execution, real-time logging, snapshot isolation, and formal audit reports are powered by the **`eldinbiz/unit-tester-documenter`** package (`composer require --dev eldinbiz/unit-tester-documenter`).

### In-Memory Unit, Feature & Module Tests
Run fast feature and unit tests against an isolated in-memory SQLite database:
```bash
# Local execution
php artisan doctest:features

# Or in Docker mode:
docker compose exec app php artisan doctest:features

# Targeting specific test classes:
php artisan doctest:features --filter=UserManagementTest
```
*(Standard `php artisan test` is also supported)*

### End-to-End Browser Testing (`eldinbiz/unit-tester-documenter`)
Browser tests run real browser interactions via Microsoft Playwright:
```bash
# Run all browser tests
php artisan doctest:browser

# Target a specific browser test
php artisan doctest:browser tests/Browser/LoginTest.php

# Run Playwright environment self-check
php artisan doctest:browser --doctor
```
- **Automated Visual Snapshots**: Every test case automatically captures an end-of-test visual snapshot into `doctest-reports/browser-test-log/browser_test_{timestamp}/<Suite-Name>/<test_slug>.png`. Developers do not need to add manual `$page->screenshot()` calls at test end.
- **Corporate HTML Audit Reports**: Generates formal, print-ready HTML test documentation at `doctest-reports/browser_test_{timestamp}.html` with embedded visual evidence galleries and clickable camera links.
- **Post-Navigation Settle Time**: For multi-step login flows or Livewire SPA redirects, add a brief settle wait (`->wait(2)` or `->waitForText(...)`) before the final assertion so the snapshot captures the fully hydrated page.

### Artifact Maintenance & Pruning
```bash
# Clean up old test execution logs, screenshots, and HTML reports
php artisan doctest:prune --all --force
```

- **Testing Rules**:
  - Test every Form Request authorization gate (guest redirect, forbidden user, authorized user).
  - Test Domain Actions directly or via feature tests for database assertions.
  - Verify tenant isolation (ensure Company A cannot view or mutate Company B records).

---

## 8. End-to-End Feature Implementation Blueprint

When creating a new feature in either root `app/` or `app-modules/<module>/`:

1. **Scaffold Module & Link Composer (If creating a domain feature)**:
   ```bash
   php artisan make:module <module-name>
   composer update modules/<module-name>
   ```
2. **Create Domain Action**:
   `app/Actions/<Subsystem>/Create<Feature>Action.php` or `Modules\<Module>\Actions\Create<Feature>Action.php`
   - Define `execute()` with `DB::transaction()` and model persistence.
3. **Create Validation Layer**:
   `app/Http/Requests/<Subsystem>/Store<Feature>Request.php` (for API/Web FormRequest validation).
4. **Build Interactive UI (Livewire v4 SPA)**:
   Create reactive Livewire Single-File Components (`resources/views/components/<module>/<component>.blade.php`) or DataTable components.
   - Inject the Domain Action into component methods (`save()`, `submit()`) and navigate using `wire:navigate`.
5. **Expose REST API Endpoint (Optional)**:
   `src/Http/Controllers/Api/v1/<Feature>Controller.php` & `src/Http/Resources/Api/v1/<Feature>Resource.php`.
   - Apply `api.company` middleware and token abilities in routes.
6. **Write Pest Feature Tests**:
   `tests/Feature/<Feature>Test.php` or `app-modules/<module>/tests/Feature/<Feature>Test.php`.
   - Cover positive, negative, validation, and multi-tenant authorization scenarios.
7. **Format & Verify**:
   ```bash
   docker compose exec app vendor/bin/pint --format agent
   docker compose exec app php artisan doctest:features
   ```

