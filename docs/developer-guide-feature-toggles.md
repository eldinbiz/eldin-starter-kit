# Developer Guide: Feature Toggles & Module Management

A comprehensive, step-by-step developer guide for integrating any application module (e.g., Approvals, Audit Logs, Inventory, Invoices, API Access, Leave Management) with the centralized **Feature Toggle & Module Management System**.

---

## Table of Contents
1. [Quick Start in 3 Steps](#1-quick-start-in-3-steps)
2. [Architecture & Resolution Cascade](#2-architecture--resolution-cascade)
3. [The Feature Registry (`config/features.php`)](#3-the-feature-registry-configfeaturesphp)
4. [Route Protection with Middleware (`feature:{key}`)](#4-route-protection-with-middleware-featurekey)
5. [Blade View Gating with `@feature`](#5-blade-view-gating-with-feature)
6. [REST API Layer Gating](#6-rest-api-layer-gating)
7. [Programmatic & Background Execution Checks](#7-programmatic--background-execution-checks)
8. [Privilege & Bypass Logic](#8-privilege--bypass-logic)
9. [The Admin Settings UI & Storage](#9-the-admin-settings-ui--storage)
10. [Testing with Pest](#10-testing-with-pest)
11. [Best Practices & FAQs](#11-best-practices--faqs)

---

## 1. Quick Start in 3 Steps

Hooking a new module into the Feature Toggle system requires **zero database migrations, zero install/uninstall scripts**, and only 3 simple code touchpoints:

### Step 1: Register in `config/features.php`
```php
// config/features.php
return [
    // ... existing features ...
    'inventory' => [
        'label' => 'Inventory Management',
        'description' => 'Stock tracking, warehouse management, and item catalogs',
        'default_enabled' => true,
        'bypass_permissions' => ['manage-inventory'],
        'setting_key' => 'feature_inventory',
    ],
];
```

### Step 2: Gate Web and API Routes
```php
// routes/web.php
Route::middleware(['auth', 'feature:inventory'])->prefix('inventory')->name('inventory.')->group(function () {
    Route::resource('items', InventoryItemController::class);
});

// routes/api.php
Route::middleware(['api.company', 'feature:inventory'])->prefix('inventory')->group(function () {
    Route::apiResource('items', ApiInventoryItemController::class);
});
```

### Step 3: Gate Sidebar Menu and UI Buttons
```blade
{{-- resources/views/partials/sidebar.blade.php --}}
@feature('inventory')
    @can('view-inventory')
        <li>
            <a href="{{ route('inventory.items.index') }}" class="menu-item">
                <span>Inventory</span>
            </a>
        </li>
    @endcan
@endfeature
```

**That is all!** The System Configuration UI will automatically detect the feature, render global toggle switches, and provide per-company overrides with zero extra controller code.

---

## 2. Architecture & Resolution Cascade

The feature toggle system is built on `App\Services\FeatureService` and backed by `App\Services\SettingService`.

When checking whether a feature is active (`FeatureService::isEnabled('feature_key', $companyId)`), the system evaluates a strict 3-tier cascade:

```
┌──────────────────────────────────────────────────────────────┐
│  Tier 1: Per-Company Override                                │
│  Stored in `system_settings` where `company_id = X`          │
│  Values: true (Force ON), false (Force OFF), null (Inherit)  │
└──────────────────────────────┬───────────────────────────────┘
                               │
                      if null ("Use Global")
                               ▼
┌──────────────────────────────────────────────────────────────┐
│  Tier 2: Global Platform Default                             │
│  Stored in `system_settings` where `company_id = NULL`       │
│  Values: true (Global ON), false (Global OFF)                │
└──────────────────────────────┬───────────────────────────────┘
                               │
                       if no DB row exists
                               ▼
┌──────────────────────────────────────────────────────────────┐
│  Tier 3: Config Default                                      │
│  Read from `config/features.php` (`default_enabled`)         │
└──────────────────────────────────────────────────────────────┘
```

### Key Architectural Characteristics
- **In-Memory Per-Request Memoization:** `FeatureService` caches toggle lookups during the request lifecycle in `FeatureService::$memoized` to avoid duplicate database or Redis queries.
- **Cache Invalidation:** When toggles are updated from the admin UI, `FeatureService::flush()` is called alongside `SettingService` cache purging.
- **Non-Destructive:** Turning a feature OFF never drops tables, removes database foreign keys, or deletes tenant data. It simply restricts user access and hides UI entry points.

---

## 3. The Feature Registry (`config/features.php`)

All toggleable features in the platform are defined in `config/features.php`. Each feature definition contains:

```php
'feature_key' => [
    // Human-readable title displayed in the Admin UI
    'label' => 'Audit Logs',

    // Short summary displayed underneath the title in the Admin UI
    'description' => 'System audit trail viewer and compliance tracking',

    // Fallback state before any admin sets global or company toggles
    'default_enabled' => true,

    // Array of permission names whose holders can still access the module even when disabled
    'bypass_permissions' => ['view-audit-logs'],

    // The unique database key stored in `system_settings` table
    'setting_key' => 'feature_audit_logs',
],
```

### Registry Fields Breakdown

| Key | Type | Description |
| :--- | :--- | :--- |
| `label` | `string` | Display name in the System Configuration toggle list. |
| `description` | `string` | Explains the module scope to Platform Administrators. |
| `default_enabled` | `bool` | Default state on fresh installations (`true` or `false`). |
| `bypass_permissions` | `list<string>` | Permissions that bypass the toggle (e.g. managers/administrators). |
| `setting_key` | `string` | Database key in `system_settings`. Convention: `feature_{key}`. |

---

## 4. Route Protection with Middleware (`feature:{key}`)

The `EnsureFeatureEnabled` middleware (`App\Http\Middleware\EnsureFeatureEnabled`) is registered in `bootstrap/app.php` under the alias `feature`.

### Web Routes Example
```php
use App\Http\Controllers\PurchaseRequestController;

// Single route
Route::get('/requests', [PurchaseRequestController::class, 'index'])
    ->middleware(['auth', 'feature:approvals']);

// Route group
Route::middleware(['auth', 'feature:approvals'])->prefix('approvals')->name('approvals.')->group(function () {
    Route::get('/inbox', [ApprovalController::class, 'inbox'])->name('inbox');
    Route::get('/history', [ApprovalController::class, 'history'])->name('history');
    Route::get('/requests', [ApprovalController::class, 'requests'])->name('requests');
});
```

### Behavior When Feature is Disabled:
1. Regular users visiting the route receive an immediate **HTTP 404 (Not Found)**.
2. Platform Admins and users holding one of `bypass_permissions` are granted access.

---

## 5. Blade View Gating with `@feature`

A custom Blade directive `@feature($key)` is registered in `AppServiceProvider`. It checks `FeatureService::isEnabled($key)` against the active authenticated company context.

### Sidebar Navigation
```blade
{{-- Group: Approvals --}}
@feature('approvals')
    @canany(['view-approval-inbox', 'view-approval-history', 'manage-approvals'])
    <div class="mb-6">
        <h3 class="mb-4 text-xs uppercase text-gray-400">Approvals</h3>
        <ul class="flex flex-col gap-1.5">
            <li>
                <a href="{{ route('approvals.inbox') }}" class="menu-item">Inbox</a>
            </li>
        </ul>
    </div>
    @endcanany
@endfeature
```

### In-Page Action Buttons & Widgets
```blade
@feature('audit_logs')
    <a href="{{ route('admin.audit-trail.index') }}" class="btn btn-secondary">
        View Audit Trail
    </a>
@endfeature
```

### Else Conditions
```blade
@feature('crm')
    <livewire:crm-pipeline-card />
@else
    <p class="text-sm text-gray-500">CRM module is not enabled for your organization.</p>
@endfeature
```

---

## 6. REST API Layer Gating

For API routes in `routes/api.php`, the feature toggle automatically resolves the tenant company from either:
1. The `X-Company-ID` HTTP request header, OR
2. The `api.company` middleware binding (`App::get('active_company_id')`).

### API Route Grouping
```php
// routes/api.php
Route::prefix('v1')->group(function () {
    Route::middleware(['api.company', 'feature:api_access'])->group(function () {
        Route::get('/approvals', [ApiApprovalController::class, 'index']);
        Route::post('/approvals', [ApiApprovalController::class, 'store']);
    });
});
```

### API Response When Disabled
If a client sends a request to `/api/v1/approvals` for a company where `api_access` or `approvals` is turned off, the middleware aborts with:
```json
{
    "message": "Not Found."
}
```
*(HTTP Status: 404)*

---

## 7. Programmatic & Background Execution Checks

When writing background jobs, scheduled console commands, or inter-module service logic, check `FeatureService` directly:

### 1. In a Service Class
```php
namespace App\Services;

use App\Services\FeatureService;

class InvoiceService
{
    public function __construct(
        private readonly FeatureService $featureService,
        private readonly ApprovalEngine $approvalEngine,
    ) {}

    public function processInvoice(Invoice $invoice): void
    {
        // Check if approvals module is enabled for this tenant
        if ($this->featureService->isEnabled('approvals', $invoice->company_id)) {
            $this->approvalEngine->submit($invoice, $template);
        } else {
            // Fallback: auto-approve or mark ready immediately
            $invoice->update(['status' => 'approved']);
        }
    }
}
```

### 2. In Scheduled Artisan Commands / Jobs
```php
namespace App\Console\Commands;

use App\Models\Company;
use App\Services\FeatureService;
use Illuminate\Console\Command;

class SyncInventoryCommand extends Command
{
    protected $signature = 'inventory:sync';

    public function handle(FeatureService $featureService): int
    {
        $companies = Company::where('is_active', true)->get();

        foreach ($companies as $company) {
            if (! $featureService->isEnabled('inventory', $company->id)) {
                $this->info("Skipping Company #{$company->id}: Inventory module disabled.");
                continue;
            }

            // Perform synchronization logic...
        }

        return self::SUCCESS;
    }
}
```

---

## 8. Privilege & Bypass Logic

The middleware `EnsureFeatureEnabled` evaluates bypass privileges before denying access:

```php
// App/Http/Middleware/EnsureFeatureEnabled.php
$user = auth()->user();

if ($user) {
    // 1. Platform Admins bypass all feature gating
    if ($user->hasPlatformAccess()) {
        return $next($request);
    }

    // 2. Holders of designated bypass permissions bypass the toggle
    $definition = config("features.{$feature}", []);
    foreach ($definition['bypass_permissions'] ?? [] as $permission) {
        if ($user->getActiveRole()?->hasPermission($permission) ?? false) {
            return $next($request);
        }
    }
}
```

### Why Bypass Exists:
- Allows system administrators to configure, debug, or prepare a module (e.g. creating workflow templates or setting up inventory bins) before releasing it to regular users.
- Platform administrators always maintain full visibility across all platform features.

---

## 9. The Admin Settings UI & Storage

The Feature Toggles management page is located at:
`Admin > System Configuration > Feature Toggles` (`/admin/settings/features`).

### Authorization
- Restricted to Platform Admins via `@can('manage-platform')`.
- Company Admins (`manage-company-settings`, `manage-system-settings`) are forbidden from viewing or changing feature toggles.

### How Values are Persisted
- **Global Defaults:** Saved in `system_settings` with `key = 'feature_{key}'` and `company_id = NULL`.
- **Per-Company Overrides:**
  - Selecting **"On"** saves `system_settings` with `company_id = X` and `value = 1`.
  - Selecting **"Off"** saves `system_settings` with `company_id = X` and `value = 0`.
  - Selecting **"Use Global"** deletes the row from `system_settings` for that company so it seamlessly inherits global platform defaults.

---

## 10. Testing with Pest

Always write automated feature tests verifying that your module respects feature toggle enforcement.

### Test Structure Example
```php
<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use App\Services\FeatureService;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->company = Company::factory()->create(['is_active' => true]);
    $this->otherCompany = Company::factory()->create(['is_active' => true]);

    $this->userRole = Role::create([
        'name' => 'user',
        'permissions' => ['view-inventory'],
    ]);

    $this->user = User::factory()->create();
    $this->user->companies()->attach($this->company->id, ['status' => 'active']);
    $this->user->companyRoles()->attach($this->userRole->id, ['company_id' => $this->company->id]);

    app(FeatureService::class)->flush();
});

test('inventory returns 404 when feature is disabled for the company', function (): void {
    app(SettingService::class)->set('feature_inventory', false, 'boolean', 'feature', $this->company->id);

    $this->actingAs($this->user);
    session(['active_company_id' => $this->company->id]);

    $this->get('/inventory/items')->assertNotFound();
});

test('inventory returns 200 when feature is enabled', function (): void {
    $this->actingAs($this->user);
    session(['active_company_id' => $this->company->id]);

    $this->get('/inventory/items')->assertOk();
});

test('disabling inventory for one company does not affect other companies', function (): void {
    $this->user->companies()->attach($this->otherCompany->id, ['status' => 'active']);
    $this->user->companyRoles()->attach($this->userRole->id, ['company_id' => $this->otherCompany->id]);

    app(SettingService::class)->set('feature_inventory', false, 'boolean', 'feature', $this->company->id);

    $this->actingAs($this->user);
    session(['active_company_id' => $this->otherCompany->id]);

    $this->get('/inventory/items')->assertOk();
});

test('sidebar hides inventory link when feature is disabled', function (): void {
    app(SettingService::class)->set('feature_inventory', false, 'boolean', 'feature', $this->company->id);

    $this->actingAs($this->user);
    session(['active_company_id' => $this->company->id]);

    $this->get('/')
        ->assertOk()
        ->assertDontSee('Inventory');
});
```

---

## 11. Best Practices & FAQs

### Best Practices
1. **Always use lowercase snake_case for feature keys** (e.g. `audit_logs`, `api_access`, `purchase_orders`).
2. **Match the database setting key prefix** (`feature_{key}`).
3. **Always call `app(FeatureService::class)->flush()` in test `beforeEach()` blocks** to ensure clean memoization state across tests.
4. **Never query the `system_settings` table directly in controllers or views.** Always use `app(FeatureService::class)->isEnabled()` or `@feature`.
5. **Always provide meaningful `bypass_permissions`** so designated administrators aren't locked out of module settings.

### FAQs
- **Q: Does disabling a feature delete database records?**  
  *No. Disabling is purely an access and visibility restriction. All database rows and relations remain 100% intact.*
- **Q: Can a Company Admin toggle their own features?**  
  *No. Feature Toggles are a Platform Admin capability (`manage-platform`) intended for SaaS tiering and subscription gating.*
- **Q: How does a company switch back to platform defaults?**  
  *In the Admin UI, select the company and change the dropdown to "Use Global". This removes the override row from the database.*
