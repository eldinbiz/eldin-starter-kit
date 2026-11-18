---
name: feature-toggle-integration
description: "Apply this skill when creating or modifying any functional module or feature in this application to ensure it integrates with the centralized Feature Toggle system. Triggers: feature toggle, feature flag, enable module, disable module, FeatureService, EnsureFeatureEnabled, @feature, config/features.php, per-company feature toggle, module management. Covers: registering in config/features.php, route protection with feature: middleware, sidebar/UI hiding with @feature directive, API scoping via X-Company-ID, programmatic checks in services/jobs/commands, and Pest testing."
license: MIT
metadata:
  author: eldinbiz
---

# Feature Toggle & Module Integration Skill

This skill guides AI agents on how to integrate new or modified application modules with the platform's multi-tenant **Feature Toggle & Module Management System**.

Follow this guide whenever building or refactoring a module (e.g. Invoices, Inventory, Leave Requests, Custom Reports, CRM, Approvals).

---

## Architecture at a Glance

| Component | Responsibility | Key File |
| :--- | :--- | :--- |
| **Registry** | Declares all toggleable modules, descriptions, and bypass permissions | `config/features.php` |
| **Engine** | Evaluates 3-tier cascade (Company Override $\rightarrow$ Global Default $\rightarrow$ Config Default) | `app/Services/FeatureService.php` |
| **Middleware** | Intercepts HTTP requests and aborts with 404 if disabled for active tenant | `app/Http/Middleware/EnsureFeatureEnabled.php` (alias `feature`) |
| **Blade Directive** | Conditionally hides navigation links and UI widgets | `AppServiceProvider` (`@feature`) |
| **Livewire UI** | Reactive Platform Admin management matrix for global & tenant overrides | `App\Livewire\Admin\Settings\FeatureToggles` |

---

## Module Integration Checklist

Whenever you create a new module, execute the following **5 integration steps**:

### 1. Register in `config/features.php`
Add the module definition to the array in `config/features.php`:
```php
'my_module' => [
    'label' => 'Module Display Name',
    'description' => 'Short summary of what this module does',
    'default_enabled' => true, // or false
    'bypass_permissions' => ['manage-my-module'], // Permissions that bypass the toggle
    'setting_key' => 'feature_my_module',
],
```

### 2. Protect Web Routes in `routes/web.php`
Add the `feature:my_module` middleware to the route group:
```php
Route::middleware(['auth', 'feature:my_module'])
    ->prefix('my-module')
    ->name('my-module.')
    ->group(function () {
        Route::resource('items', MyModuleController::class);
    });
```

### 3. Protect API Routes in `routes/api.php`
For API endpoints, combine `api.company` with `feature:my_module`:
```php
Route::prefix('v1')->group(function () {
    Route::middleware(['api.company', 'feature:my_module'])->group(function () {
        Route::apiResource('my-module', ApiMyModuleController::class);
    });
});
```

### 4. Gate Navigation & UI Elements
Wrap menu items and action buttons in `@feature('my_module')`:

```blade
{{-- In resources/views/partials/sidebar.blade.php --}}
@feature('my_module')
    @can('view-my-module')
    <li>
        <a href="{{ route('my-module.items.index') }}" class="menu-item">
            <span>My Module</span>
        </a>
    </li>
    @endcan
@endfeature
```

### 5. Add Programmatic Checks in Background Jobs / Commands / Services
If the module dispatches background jobs, scheduled tasks, or is invoked by another service:
```php
use App\Services\FeatureService;

if (! app(FeatureService::class)->isEnabled('my_module', $companyId)) {
    // Return early, skip execution, or log bypass
    return;
}
```

---

## Writing Automated Pest Tests

Every module must include a feature test verifying toggle enforcement.

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
        'permissions' => ['view-my-module'],
    ]);

    $this->user = User::factory()->create();
    $this->user->companies()->attach($this->company->id, ['status' => 'active']);
    $this->user->companyRoles()->attach($this->userRole->id, ['company_id' => $this->company->id]);

    app(FeatureService::class)->flush();
});

test('my-module returns 404 when disabled for active company', function (): void {
    app(SettingService::class)->set('feature_my_module', false, 'boolean', 'feature', $this->company->id);

    $this->actingAs($this->user);
    session(['active_company_id' => $this->company->id]);

    $this->get('/my-module/items')->assertNotFound();
});

test('my-module returns 200 when enabled for active company', function (): void {
    $this->actingAs($this->user);
    session(['active_company_id' => $this->company->id]);

    $this->get('/my-module/items')->assertOk();
});

test('disabling my-module for one company does not affect other companies', function (): void {
    $this->user->companies()->attach($this->otherCompany->id, ['status' => 'active']);
    $this->user->companyRoles()->attach($this->userRole->id, ['company_id' => $this->otherCompany->id]);

    app(SettingService::class)->set('feature_my_module', false, 'boolean', 'feature', $this->company->id);

    $this->actingAs($this->user);
    session(['active_company_id' => $this->otherCompany->id]);

    $this->get('/my-module/items')->assertOk();
});

test('sidebar hides my-module when feature is disabled', function (): void {
    app(SettingService::class)->set('feature_my_module', false, 'boolean', 'feature', $this->company->id);

    $this->actingAs($this->user);
    session(['active_company_id' => $this->company->id]);

    $this->get('/')
        ->assertOk()
        ->assertDontSee('My Module');
});
```

---

## Anti-Patterns & Rules to Follow

- ❌ **NEVER** write raw database queries against the `system_settings` table to check if a feature is enabled. Always use `app(FeatureService::class)->isEnabled('key', $companyId)` or `@feature('key')`.
- ❌ **NEVER** drop tables or alter schemas when toggling features. Feature toggling is non-destructive access control.
- ❌ **NEVER** forget to flush `FeatureService` memoization in test `beforeEach()` (`app(FeatureService::class)->flush()`).
- ❌ **NEVER** expose feature toggle controls to regular Company Admins; it is strictly restricted to Platform Admins (`manage-platform`).
- ✅ **ALWAYS** use snake_case for feature keys (e.g. `audit_logs`, `api_access`, `time_tracking`).
