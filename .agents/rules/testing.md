---
trigger: model_decision
description: Triggers when creating, modifying, or refactoring features, modules, controllers, Livewire components, models, or services. Enforces mandatory test coverage using Pest 5 following TDD practices.
---

# Test-Driven Development Rule (Pest 5 & Livewire v4)

This rule strictly governs all feature, module, and Livewire component changes, ensuring that every code change is accompanied by appropriate test coverage.

## 1. Mandatory Test Coverage

When the agent **adds, modifies, or refactors** any of the following, it **MUST** create or update corresponding tests:

| Change Type | Test Requirement |
|---|---|
| New route/controller method | Feature test covering the endpoint |
| New or modified Livewire v4 component | Livewire feature test (`Livewire::test()`) |
| New or modified validation rules | Test for valid + invalid input (`assertHasErrors`) |
| New or modified business logic / Domain Actions | Unit or feature test verifying action behavior |
| New or modified authorization (policies/gates) | Test for authorized + unauthorized access |
| New model relationship or accessor | Unit test verifying the relationship/accessor |
| End-to-end user journeys / client-side JS & DOM | Browser test using Pest Browser (`visit()`, `click()`) |
| Bug fix | Regression test reproducing the bug first |

## 2. Test Location

| Code Changed | Test Goes In |
|---|---|
| `app/Livewire/` | `tests/Feature/Livewire/` |
| `app-modules/<module>/src/Livewire/` | `app-modules/<module>/tests/Feature/Livewire/` |
| `app/Http/Controllers/` | `tests/Feature/` |
| `routes/web.php` or `routes/api.php` | `tests/Feature/` |
| `app/Actions/` | `tests/Feature/` or `tests/Unit/` |
| `app/Models/` | `tests/Feature/` or `tests/Unit/` |
| `app/Services/` | `tests/Unit/` |
| `app/Middleware/` | `tests/Feature/` |
| `app/Events/` / `app/Listeners/` | `tests/Feature/` |
| End-to-end UI flows / Browser DOM interactions | `tests/Browser/` |

## 3. Test Quality & Pest Conventions

- **Pest 5 & Strict Types:** Tests must use `declare(strict_types=1);`, `uses(RefreshDatabase::class);`, and descriptive `it(...)` or `test(...)` blocks.
- **Livewire Assertions:** Use native Livewire test methods:
  - Property updates & checks: `->set('prop', 'val')->assertSet('prop', 'val')`
  - Method calls: `->call('methodName', ...$args)`
  - Validation: `->assertHasErrors(['prop' => 'required'])`
  - Text & Elements: `->assertSee('Text')`, `->assertDontSee('Text')`
  - Array counts: `->assertCount('propertyName', $expectedCount)` *(Note: property name first, count second)*
  - Redirects: `->assertRedirect(route('...'))`
- **Session & Tenant Scoping:** Prime session with `session(['active_company_id' => $company->id])` and use `$this->actingAs($user)`.
- **Pest Browser Test Conventions:**
  - Group browser tests with `->group('browser')`.
  - **Automated End-Result Snapshots (No Manual Screenshots):** The browser runner (`php artisan rubberstamp:browser`) provided by the `eldinbiz/laravel-rubberstamp` package automatically captures an end-of-test visual snapshot for each test case into `doctest-reports/browser-test-log/browser_test_{timestamp}/<Suite-Name>/<test_slug>.png` and links it into the corporate HTML audit report. Do NOT add manual `$page->screenshot()` boilerplate at the end of tests unless an intermediate state snapshot is explicitly needed.
  - **Playwright Auto-Waiting (Never Use Dusk `waitFor`):** DO NOT call Laravel Dusk methods like `->waitFor($selector)`—it does not exist on Pest's `Webpage` class and triggers a fatal `Call to undefined method` error. Pest Browser is powered by Microsoft Playwright, which has built-in actionability auto-waiting on all interaction methods (`type()`, `click()`, `fill()`, `select()`).
  - **No Session Flash Checks Across Redirects (Deterministic Invariants):** Never assert transient session flash message strings across HTTP 302 redirects (e.g. `assertSee('Email already taken')` after submitting a standard form) as headless Chromium drops flash messages under the array session driver. Always assert the route destination (`assertPathIs(...)`), form context presence (`assertSee(...)`), and database state invariants (`expect(...->exists())->toBeFalse()`).
  - **Livewire Reactive Settling:** When typing into debounced live search filters, filter toggles, or Alpine type-to-confirm inputs, always introduce a settle delay (`->wait(0.5)` or `->waitForText(...)`) before asserting filtered DOM updates or clicking confirmation buttons.
  - **Post-Navigation Settle Time (SPA / Auth Redirects):** After multi-step flows, form submissions, or SPA/Livewire redirects (such as clicking `@login-button`), use a brief settle time (e.g. `->wait(2)` or `->waitForText(...)`) before asserting end state so the auto-captured snapshot captures the fully hydrated UI rather than an intermediate transition state.
  - **Explicit Waiting APIs:** If explicit waiting is required, only use `->waitForText($text)` for text, `->wait($seconds)` for settle time, or `->waitForEvent($state)` for load states.
  - **Dedicated Test IDs:** Always assign `data-testid="..."` to interactive Blade components and target them in tests using Pest's native `@` shorthand (e.g. `->click('@next-button')`, `->click('@login-button')`). Avoid ambiguous CSS class or raw text locators in multi-step flows to prevent Playwright strict mode crashes.
  - **Simulate Real User Authentication:** For protected flows, simulate the real 2-step authentication flow:
    `visit('/login')->type('#email', $user->email)->click('@next-button')->type('#password', 'password')->click('@login-button')->assertPathIs('/')`.
  - **Headed vs. Parallel:** Never pass `--parallel` when running in headed mode (`--headed` is strictly incompatible with `--parallel`).
- Do **NOT** delete or skip existing tests without explicit user approval.

## 4. Reporting & Executing Test Results

The test runners, real-time loggers, visual snapshot captures, and corporate HTML audit reports are provided by the **`eldinbiz/laravel-rubberstamp`** package (`composer require --dev eldinbiz/laravel-rubberstamp`).

After creating or modifying tests, report the appropriate test execution command:

- **Fast In-Memory Unit, Feature & Module Tests:**
  ```bash
  php artisan rubberstamp:features
  ```
  *(or `php artisan test` / `php artisan rubberstamp:features --filter=...`)*

- **End-to-End Browser Tests (Playwright / Chromium):**
  ```bash
  php artisan rubberstamp:browser
  ```
  *(or `php artisan rubberstamp:browser tests/Browser/LoginTest.php`). This command executes Playwright environment self-diagnostics (`--doctor`), disables output buffering for real-time streaming, isolates snapshots per test case, and generates print-ready corporate HTML reports in `doctest-reports/`.*
  *(Supports dynamic arguments such as `php artisan rubberstamp:browser --filter=...` or `php artisan rubberstamp:browser --doctor`)*

- **Artifact Maintenance & Pruning:**
  ```bash
  php artisan rubberstamp:prune --all --force
  ```

If investigating test failures or reviewing visual evidence:
- For Unit/Feature tests (`rubberstamp:features`): inspect the latest `test_*.log` file in `doctest-reports/test-log/`.
- For Browser tests (`rubberstamp:browser`):
  - Inspect the latest `browser_test_*.log` file in `doctest-reports/browser-test-log/`.
  - Review visual evidence in the generated HTML report at `doctest-reports/browser_test_{timestamp}.html`.
  - Check isolated visual snapshots in `doctest-reports/browser-test-log/browser_test_{timestamp}/<Suite-Name>/<test_case_slug>.png`.

## 5. Exception

Trivial changes that do not affect behavior (e.g., formatting, PHPDoc updates, type hints that don't change logic) do not require new tests. The agent must note when invoking this exception.
