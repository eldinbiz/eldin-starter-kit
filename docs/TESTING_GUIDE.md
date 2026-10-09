# Starter Kit Testing Handbook & Guide

This guide is the definitive testing reference for developers working within this Laravel starter kit. It details how to write reliable, maintainable tests across all three architectural layers, covers reactive Livewire v4 and multi-tenant testing, and provides the anti-flakiness handbook for end-to-end browser tests.

---

## 1. Testing Strategy & Architecture

This application adheres to a **3-Layer + Livewire v4 Architecture**. Our testing strategy mirrors this design with a clear testing pyramid:

```
                      ┌─────────────────────────────┐
                      │    Pest 5 Browser Tests     │  End-to-End user journeys,
                      │     (tests/Browser/)        │  Playwright, real browser DOM
                      ├─────────────────────────────┤
                      │  Feature & Livewire Tests   │  HTTP transport, Form Requests,
                      │      (tests/Feature/)       │  Livewire::test() reactivity
                      ├─────────────────────────────┤
                      │     Domain Action Tests     │  Isolated business logic,
                      │  (tests/Unit/ or Feature/)  │  DB transactions, events
                      └─────────────────────────────┘
```

### Directory Conventions

| What You Are Testing | Test Type | File Path |
|---|---|---|
| Domain Actions (`app/Actions/`) | Unit / Feature | `tests/Feature/Actions/` or `tests/Unit/Actions/` |
| Module Actions (`app-modules/<module>/src/Actions/`) | Feature | `app-modules/<module>/tests/Feature/Actions/` |
| Livewire v4 Components (`app/Livewire/`) | Feature | `tests/Feature/Livewire/` |
| Module Livewire Components (`app-modules/...`) | Feature | `app-modules/<module>/tests/Feature/Livewire/` |
| Controllers & Form Requests | Feature | `tests/Feature/` |
| End-to-end user journeys & JS/DOM | Browser | `tests/Browser/` |

---

## 2. Layer 1: Unit Testing Domain Actions

Domain Actions encapsulate all core business modifications within `DB::transaction()`. They should be tested directly to verify database mutations, returned models, and event dispatches.

### Action Test Pattern

```php
<?php

declare(strict_types=1);

use App\Actions\Users\CreateUserAction;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

it('creates a user within a company transaction', function () {
    Event::fake();

    $company = Company::factory()->create();

    $action = app(CreateUserAction::class);

    $user = $action->execute([
        'name' => 'Alice Developer',
        'email' => 'alice@example.com',
        'password' => 'SecurePassword123!',
        'company_id' => $company->id,
    ]);

    expect($user)
        ->toBeInstanceOf(User::class)
        ->name->toBe('Alice Developer')
        ->email->toBe('alice@example.com');

    $this->assertDatabaseHas('users', [
        'email' => 'alice@example.com',
        'company_id' => $company->id,
    ]);
});
```

---

## 3. Layer 2: Feature & Livewire v4 Component Testing

### Multi-Tenant Company Scoping & Session Priming

This starter kit enforces multi-tenancy using an active company session context (`active_company_id`). In feature tests, always prime the tenant session alongside authentication:

```php
// Standard setup for authenticated multi-tenant feature tests:
$company = Company::factory()->create();
$user = User::factory()->create(['company_id' => $company->id]);

$this->actingAs($user);
session(['active_company_id' => $company->id]);
```

### Mandatory Tenant Isolation Test Pattern

Every feature or module that manipulates tenant data **must** verify tenant boundary enforcement:

```php
it('prevents users from accessing records belonging to another company', function () {
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    $userA = User::factory()->create(['company_id' => $companyA->id]);
    $itemB = SomeModel::factory()->create(['company_id' => $companyB->id]);

    $this->actingAs($userA);
    session(['active_company_id' => $companyA->id]);

    // Ensure cross-tenant access is forbidden
    $this->get(route('items.show', $itemB))
        ->assertForbidden();
});
```

### Testing Livewire v4 Reactive Components

Livewire v4 components serve as thin UI orchestrators. Use Pest's `Livewire::test()` helper to test reactive properties, filtering, and method delegation without spinning up a full browser:

```php
<?php

declare(strict_types=1);

use App\Livewire\Users\UserTable;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders user datatable and filters reactively by search term', function () {
    $company = Company::factory()->create();
    $admin = User::factory()->create(['company_id' => $company->id]);

    $targetUser = User::factory()->create([
        'name' => 'Specific Unique Target',
        'company_id' => $company->id,
    ]);
    $otherUser = User::factory()->create([
        'name' => 'Another Unrelated Person',
        'company_id' => $company->id,
    ]);

    $this->actingAs($admin);
    session(['active_company_id' => $company->id]);

    Livewire::test(UserTable::class)
        ->assertSee('Specific Unique Target')
        ->assertSee('Another Unrelated Person')
        // Test reactive search filter
        ->set('search', 'Specific Unique')
        ->assertSee('Specific Unique Target')
        ->assertDontSee('Another Unrelated Person');
});
```

---

## 4. Layer 3: End-to-End Pest 5 Browser Testing

Pest 5 Browser tests execute in real headless Chromium instances powered by **Microsoft Playwright**. They live in `tests/Browser/` and validate end-to-end user journeys including JavaScript interactivity, Alpine modals, and client-side transitions.

### Basic Browser Test Structure

```php
<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows admin to log in and view the dashboard', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create([
        'email' => 'admin@example.com',
        'password' => bcrypt('password'),
        'company_id' => $company->id,
    ]);

    $page = visit('/login');

    // Simulate starter kit 2-step authentication flow
    $page->type('#email', 'admin@example.com')
        ->click('@next-button')
        ->type('#password', 'password')
        ->click('@login-button')
        ->assertPathIs('/dashboard')
        ->assertSee('Dashboard')
        ->assertNoJavaScriptErrors();
});
```

---

## 5. The Browser Testing Anti-Flakiness Handbook

Intermittent test failures in browser testing are almost always caused by timing assumptions, transient redirect states, or ambiguous DOM locators. Follow these four mandatory rules:

### Rule 1: The Session Flash Redirect Anti-Pattern (Assert Invariants, Not Flash Text)

> [!CAUTION]
> **Do not assert transient session flash messages across HTTP redirects in Pest Browser.**

In headless Chromium / Playwright with `SESSION_DRIVER=array` (standard in automated testing environments), standard HTTP 302 redirects (such as non-Livewire controllers redirecting back upon a validation error) consume or drop ephemeral session flash messages before the Playwright assertion can inspect the DOM.

Relying on `assertSee('The email has already been taken.')` across redirects causes **intermittent test failures**.

#### ❌ Flaky Pattern:
```php
// FLAKY: Flash message string often expires or disappears before Playwright reads the page
$page->visit('/users/create')
    ->type('@email-input', 'existing@example.com')
    ->click('@submit-button')
    ->assertSee('The email has already been taken.'); // Fails intermittently!
```

#### ✅ Deterministic Invariant Standard:
Assert the destination path, the form context presence, and the database state invariants directly:

```php
// DETERMINISTIC: Asserts route, form context, and database state
$page->visit('/users/create')
    ->type('@email-input', 'existing@example.com')
    ->click('@submit-button')
    ->assertPathIs('/users/create')
    ->assertSee('Create New User')
    ->assertNoJavaScriptErrors();

// Verify database invariant directly
expect(User::where('name', 'Attempted User')->exists())->toBeFalse();
```

---

### Rule 2: Livewire v4 Reactive Settling & Alpine Microtasks

When typing into debounced search inputs, filter toggles, or Alpine type-to-confirm inputs, Livewire v4 and Alpine trigger asynchronous network requests and DOM morphing. 

If a test immediately asserts content or clicks a button right after `type()`, it races against the in-flight network roundtrip.

#### ✅ Settling Pattern:
Introduce a brief settle pause (`->wait(0.5)`) or use `->waitForText(...)` after reactive typing:

```php
// After typing into a debounced live search field:
$page->type('@search-input', 'Unique Query')
    ->wait(0.5) // Allow Livewire 300ms debounce + network roundtrip to settle
    ->assertSee('Unique Query Match')
    ->assertDontSee('Old Irrelevant Row');
```

For Alpine type-to-confirm modals (where the delete button remains disabled until the exact entity name is typed):
```php
$page->click('@delete-trigger-btn')
    ->assertSee('Confirm Deletion')
    ->type('@type-to-confirm-input', 'Company Alpha')
    ->wait(0.5) // Allow Alpine reactive binding to remove the disabled attribute
    ->click('@confirm-delete-btn')
    ->wait(1.0); // Allow modal dismissal and deletion action to complete
```

---

### Rule 3: Strict Mode Locator Prevention (Use `data-testid` & `@` Shorthand)

Microsoft Playwright enforces **strict mode** by default: if a locator matches more than one element, the test immediately crashes with a `strict mode violation`.

Avoid clicking raw text strings like `->click('Delete')` or generic CSS classes when multiple rows exist in a datatable.

#### ✅ Implementation:
1. In your Blade component or view template, assign descriptive `data-testid` attributes:
   ```blade
   <x-ui::button data-testid="user-create-submit" type="submit">
       Create User
   </x-ui::button>
   ```
2. In your Pest Browser test, query using Pest's native `@` shorthand:
   ```php
   $page->click('@user-create-submit');
   ```

For elements inside repeating loops or table rows, append unique identifiers:
```blade
<button data-testid="delete-user-{{ $user->id }}">Delete</button>
```
```php
$page->click('@delete-user-'.$targetUser->id);
```

---

### Rule 4: Livewire SPA Navigation (`wire:navigate`)

Links decorated with `wire:navigate` or `wire:navigate.hover` perform client-side HTML prefetching and DOM morphing rather than standard full-page reloads.

- Do **not** expect full page unload lifecycles.
- Always assert on the destination DOM elements (`assertSee`, `assertPathIs`, `@test-id`) directly.

---

### Rule 5: Playwright Auto-Waiting (Never Use Dusk `waitFor`)

> [!WARNING]
> Never call `->waitFor($selector)`. This is a legacy Laravel Dusk method that does not exist in Pest 5 / Playwright and throws a fatal `Call to undefined method` error.

Pest Browser methods (`click()`, `type()`, `fill()`, `select()`) **automatically wait** for elements to attach, become visible, and become actionable before executing. 

If explicit waiting is needed, use:
- `->waitForText('Some Text')` — waits for text to appear in DOM.
- `->wait(seconds)` — brief settle time for transitions or debounce.

---

## 6. Running Tests

### Standard Test Execution

```bash
# Run entire test suite
php artisan test

# Run with compact output
php artisan test --compact

# Filter by test name or class
php artisan test --filter=UserManagementTest

# Run only affected tests with Test Impact Analysis (Tia)
./vendor/bin/pest --tia
```

### Running with RubberStamp (Optional Diagnostic Runner)

If you prefer real-time streamed logs, visual snapshots, and HTML audit documentation:

```bash
# Run unit & feature tests with real-time log streaming
php artisan rubberstamp:features

# Run browser tests with Playwright self-check and automated snapshots
php artisan rubberstamp:browser

# Run specific browser test
php artisan rubberstamp:browser tests/Browser/UserManagementTest.php

# Prune old test run artifacts
php artisan rubberstamp:prune --all --force
```

---

## 7. Developer Pre-Merge Checklist

Before opening a pull request or submitting code changes:

- [ ] **Action Isolation**: All database mutations in Domain Actions are wrapped in `DB::transaction()`.
- [ ] **Multi-Tenancy**: Tenant-scoped tests prime `session(['active_company_id' => ...])`.
- [ ] **Tenant Isolation Assertion**: Verified that cross-company data access returns 403 Forbidden.
- [ ] **No Flash Redirect Checks**: Browser tests assert route paths, form context, and database state invariants rather than transient session flash strings across HTTP redirects.
- [ ] **Unique Locators**: All interactive elements in new views define `data-testid="..."` and are targeted with `@` shorthand.
- [ ] **Settle Delays on Debounce**: Reactive Livewire searches and type-to-confirm inputs use `->wait(0.5)` before assertions.
- [ ] **Green Test Suite**: `php artisan test` (or `php artisan rubberstamp:browser`) passes with 0 failures and 0 JavaScript errors.
