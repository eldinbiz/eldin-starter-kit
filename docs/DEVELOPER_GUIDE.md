# Developer & Architecture Guide

Welcome to the application architecture and developer handbook. This guide defines the foundational design patterns, directory conventions, and coding standards used across both core platform services (`app/`) and domain modules (`app-modules/`).

---

## Table of Contents
1. [Core Architectural Philosophy](#1-core-architectural-philosophy)
2. [The 3-Layer Standard (Requests, Actions, Controllers)](#2-the-3-layer-standard)
3. [Modular Monolith Architecture (`app-modules/`)](#3-modular-monolith-architecture)
4. [Multi-Tenancy & Authorization Boundaries](#4-multi-tenancy--authorization-boundaries)
5. [Core Platform Subsystem Integrations](#5-core-platform-subsystem-integrations)
6. [PHP 8.4 Standards & Code Quality](#6-php-84-standards--code-quality)
7. [Testing & Quality Assurance](#7-testing--quality-assurance)
8. [End-to-End Feature Implementation Blueprint](#8-end-to-end-feature-implementation-blueprint)

---

## 1. Core Architectural Philosophy

This project is built as a **Modular Monolith** using **Laravel 13**, **PHP 8.4**, and [`internachi/modular`](https://github.com/InterNACHI/modular). 

To ensure long-term maintainability, testability, and reuse across Web, REST API, CLI, and Queue layers, we enforce a strict separation of concerns:

- **Core vs. Domain Boundary**:
  - `app/` is reserved for cross-cutting multi-tenant platform infrastructure (Authentication, User/Company models, Roles & Permissions, Approval Engine, Notification Engine, System Settings, Feature Toggles).
  - `app-modules/<module-name>/` houses all isolated business domains (e.g. Purchase Requests, Invoices, HR/Leave, Inventory).
- **The 3-Layer Pattern**:
  - **Form Requests**: Validate inputs and enforce request-level authorization gates.
  - **Domain Actions**: Encapsulate single-purpose business operations, transactions, and event dispatches.
  - **Thin Controllers**: Act purely as HTTP orchestrators that bind requests to actions and return responses.

```
┌─────────────────────────────────────────────────────────────┐
│                      HTTP Request / CLI                     │
└──────────────────────────────┬──────────────────────────────┘
                               │
                               ▼
┌─────────────────────────────────────────────────────────────┐
│       1. Form Request (Validation & Authorization Gate)     │
└──────────────────────────────┬──────────────────────────────┘
                               │ validated()
                               ▼
┌─────────────────────────────────────────────────────────────┐
│       2. Controller (Thin HTTP Orchestrator)                │
└──────────────────────────────┬──────────────────────────────┘
                               │ execute($data)
                               ▼
┌─────────────────────────────────────────────────────────────┐
│       3. Domain Action (Single Responsibility & DB Tx)      │
│  ┌───────────────────────────────────────────────────────┐  │
│  │ DB::transaction, Models, Events, Notifications, Media │  │
│  └───────────────────────────────────────────────────────┘  │
└──────────────────────────────┬──────────────────────────────┘
                               │ Returns Model / DTO / Result
                               ▼
┌─────────────────────────────────────────────────────────────┐
│           Response (Redirect, Blade View, API JSON)         │
└─────────────────────────────────────────────────────────────┘
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

### Layer 3: Thin Controllers (`app/Http/Controllers/...` or `Modules\<Module>\Http\Controllers\...`)
- **Responsibility**: HTTP routing, parameter extraction, action execution, and returning responses (Blade view, Redirect, or JSON).
- **Rules**:
  - Controllers must not contain queries with business logic or direct model persistence.
  - Type-hint Form Requests and Domain Actions in method signatures (dependency injection).
  - Return explicit response types (`View`, `RedirectResponse`, `JsonResponse`).

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Users\CreateUserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Users\StoreUserRequest;
use Illuminate\Http\RedirectResponse;

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
│   ├── components/                        # Blade components: <x-<module>::card />
│   └── lang/                              # Translations: __('<module>::messages.key')
├── database/
│   ├── migrations/                        # Auto-discovered migrations
│   ├── factories/                         # Model factories
│   └── seeders/                           # Module seeders
├── src/
│   ├── Actions/                           # Domain Actions
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
    └── Feature/                           # Pest / PHPUnit test suite
        └── <StudlyName>Test.php
```

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
   app(ApprovalEngine::class)->submit($model, $user, $workflowTemplate);
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
       <a href="{{ route('purchaserequests.index') }}">Purchase Requests</a>
   @endfeature
   ```

### C. Notification Engine (`App\Services\NotificationEngine`)
Dispatch events implementing `NotificationPayload` or fire `NotificationRequested`:
```php
use App\Events\NotificationRequested;
use App\Services\NotificationPayload;

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

We enforce Test-Driven Development (TDD) using **Pest v4 / PHPUnit v12**.

- **Running the Test Suite**:
  Always run tests from inside the Docker container using the `./test` runner script:
  ```bash
  docker compose exec app ./test
  ```
- **Targeting Specific Tests**:
  ```bash
  docker compose exec app ./test --filter=UserManagementTest
  ```
- **Testing Rules**:
  - Test every Form Request authorization gate (guest redirect, forbidden user, authorized user).
  - Test Domain Actions directly or via feature tests for database assertions.
  - Verify tenant isolation (ensure Company A cannot view or mutate Company B records).

---

## 8. End-to-End Feature Implementation Blueprint

When creating a new feature in either root `app/` or `app-modules/<module>/`:

1. **Create Form Request**:
   `app/Http/Requests/<Subsystem>/Store<Feature>Request.php`
   - Define validation rules and gate checks in `authorize()`.
2. **Create Domain Action**:
   `app/Actions/<Subsystem>/Create<Feature>Action.php`
   - Define `execute()` with `DB::transaction()` and model persistence.
3. **Create/Update Controller**:
   `app/Http/Controllers/<Subsystem>/<Feature>Controller.php`
   - Inject the Request and Action; delegate logic and return response.
4. **Write Pest Feature Test**:
   `tests/Feature/<Feature>Test.php`
   - Cover positive, negative, validation, and multi-tenant authorization scenarios.
5. **Format & Verify**:
   ```bash
   docker compose exec app vendor/bin/pint --format agent
   docker compose exec app ./test
   ```
