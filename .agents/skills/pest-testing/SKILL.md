---
name: pest-testing
description: "Use this skill for Pest PHP testing in Laravel projects only. Trigger whenever any test is being written, edited, fixed, or refactored — including fixing tests that broke after a code change, adding assertions, converting PHPUnit to Pest, adding datasets, and TDD workflows. Always activate when the user asks how to write something in Pest, mentions test files or directories (tests/Feature, tests/Unit, tests/Browser), or needs browser testing, smoke testing multiple pages for JS errors, architecture tests, or faster test runs with Test Impact Analysis. Covers: test()/it()/expect() syntax, datasets, mocking, browser testing (visit/click/fill), smoke testing, arch(), Livewire component tests, RefreshDatabase, Tia (--tia), sharding, and all Pest 5 features. Do not use for factories, seeders, migrations, controllers, models, or non-test PHP code."
license: MIT
metadata:
  author: laravel
---

# Pest Testing 5

## Documentation

Use `search-docs` for detailed Pest 5 patterns and documentation.

## Basic Usage

### Creating Tests

All tests must be written using Pest. Use `php artisan make:test --pest {name}`.

The `{name}` argument should include only the path and test name, but should not include the test suite.
- Incorrect: `php artisan make:test --pest Feature/SomeFeatureTest` will generate `tests/Feature/Feature/SomeFeatureTest.php`
- Correct: `php artisan make:test --pest SomeControllerTest` will generate `tests/Feature/SomeControllerTest.php`
- Incorrect: `php artisan make:test --pest --unit Unit/SomeServiceTest` will generate `tests/Unit/Unit/SomeServiceTest.php`
- Correct: `php artisan make:test --pest --unit SomeServiceTest` will generate `tests/Unit/SomeServiceTest.php`

### Test Organization

- Unit/Feature tests: `tests/Feature` and `tests/Unit` directories.
- Browser tests: `tests/Browser/` directory.
- Do NOT remove tests without approval - these are core application code.

### Basic Test Structure

Pest supports both `test()` and `it()` functions. Before writing new tests, check existing test files in the same directory to match the project's convention. Use `test()` if existing tests use `test()`, or `it()` if they use `it()`.

<!-- Basic Pest Test Example -->
```php
it('is true', function () {
    expect(true)->toBeTrue();
});
```

### Running Tests

- Run minimal tests with filter before finalizing: `php artisan test --compact --filter=testName`.
- Run all tests: `php artisan test --compact`.
- Run file: `php artisan test --compact tests/Feature/ExampleTest.php`.
- Run only tests affected by recent changes (Tia): `./vendor/bin/pest --parallel --tia`.

## Assertions

Use specific assertions (`assertSuccessful()`, `assertNotFound()`) instead of `assertStatus()`:

<!-- Pest Response Assertion -->
```php
it('returns all', function () {
    $this->postJson('/api/docs', [])->assertSuccessful();
});
```

| Use | Instead of |
|-----|------------|
| `assertSuccessful()` | `assertStatus(200)` |
| `assertNotFound()` | `assertStatus(404)` |
| `assertForbidden()` | `assertStatus(403)` |

## Mocking

Import mock function before use: `use function Pest\Laravel\mock;`

## Datasets

Use datasets for repetitive tests (validation rules, etc.):

<!-- Pest Dataset Example -->
```php
it('has emails', function (string $email) {
    expect($email)->not->toBeEmpty();
})->with([
    'james' => 'james@laravel.com',
    'taylor' => 'taylor@laravel.com',
]);
```

## Pest 5 Features

| Feature | Purpose |
|---------|---------|
| Tia (Test Impact Analysis) | Rerun only tests affected by recent changes |
| Time-Balanced Sharding | Split tests across CI shards by execution time |
| New Validation Expectations | `toBeEmail()`, `toBeUlid()`, `toBeIpAddress()`, and more |
| Browser Testing | Full integration tests in real browsers |
| Smoke Testing | Validate multiple pages quickly |
| Visual Regression | Compare screenshots for visual changes |
| Architecture Testing | Enforce code conventions |

### Tia (Test Impact Analysis)

Tia reruns only tests affected by recent changes and replays cached results for the rest, dramatically reducing suite duration:

<!-- Tia Example -->
```shell
./vendor/bin/pest --parallel --tia
```

- Replayed tests are not skipped — cached tests store everything they produced, including covered lines and branches.
- Detects Laravel, Symfony, Livewire, and Inertia automatically.

### New Validation Expectations

Pest 5 ships eight new validation matchers, all supporting `.not` negation:

<!-- Pest 5 Validation Expectations -->
```php
expect('nuno@pestphp.com')->toBeEmail();
expect('01ARZ3NDEKTSV4RRFFQ69G5FAV')->toBeUlid();
expect('192.168.1.1')->toBeIpAddress();
expect('00:1a:2b:3c:4d:5e')->toBeMacAddress();
expect('example.com')->toBeHostname();
expect('example.co.uk')->toBeDomain();
expect('Zm9vYmFy')->toBeBase64();
expect('deadbeef')->toBeHexadecimal();
```

### Time-Balanced Sharding

Distribute tests across CI shards by execution time rather than count:

<!-- Pest Sharding Example -->
```shell
./vendor/bin/pest --update-shards
./vendor/bin/pest --shard=1/4
```

Commit `tests/.pest/shards.json` to the repository so CI shards stay consistent.

### Browser Test Example

Browser tests run in real browsers for full integration testing:

- Browser tests live in `tests/Browser/`.
- Use Laravel features like `Event::fake()`, `assertAuthenticated()`, and model factories.
- Use `RefreshDatabase` for clean state per test.
- **Automated Visual Snapshots & Audit Reports (`eldinbiz/laravel-rubberstamp`):** Browser tests and documentation reports are powered by the `eldinbiz/laravel-rubberstamp` package (`composer require --dev eldinbiz/laravel-rubberstamp`). When executed via `php artisan rubberstamp:browser`, every test case automatically captures an end-of-test visual snapshot into `doctest-reports/browser-test-log/browser_test_{timestamp}/<Suite-Name>/<test_slug>.png` and correlates it to the corporate HTML audit report with 📷 camera links. Do not write manual `$page->screenshot()` calls at test end.
- **Playwright Auto-Waiting (No Dusk `waitFor`):** Never call `->waitFor($selector)`. Pest 5 Browser testing runs on Microsoft Playwright, which automatically waits for elements to attach, become visible, and actionable before executing actions (`type()`, `click()`, `fill()`). Calling `waitFor($selector)` causes a fatal `Call to undefined method` error.
- **Session Flash Redirect Anti-Pattern (Deterministic Invariants):** In headless Chromium / Playwright with `SESSION_DRIVER=array`, HTTP 302 redirects back to a form (such as non-Livewire validation errors) consume/clear ephemeral session flash messages before Playwright assertions run. **Never assert transient flash messages like `assertSee('The email has already been taken.')` across redirects.** Instead, assert deterministic invariants:
  1. Route destination: `$page->assertPathIs('/users/create')`
  2. Form context: `$page->assertSee('Create New User')`
  3. Database invariant: `expect(User::where('name', 'Attempted User')->exists())->toBeFalse()`
- **Livewire v4 & Alpine Reactive Settling:** When typing into debounced live search filters, filter dropdowns, or Alpine type-to-confirm inputs, Livewire/Alpine performs asynchronous network roundtrips and DOM morphing. Always introduce a brief settle time (`->wait(0.5)` or `->waitForText(...)`) before asserting filtered DOM state or clicking confirmation buttons.
- **Targeting Elements with `data-testid` (Strict Locators):** Playwright enforces strict mode. Generic text or class queries fail when multiple items appear in tables or modals. Always attach `data-testid="..."` to interactive Blade elements and query using Pest's `@` prefix shorthand (e.g. `->click('@submit-button')`, `->type('@email-input', '...')`). For table rows, append IDs (e.g. `@delete-user-{$id}`).
- **SPA Navigation (`wire:navigate`):** Internal links using `wire:navigate` morph the DOM without a full page reload lifecycle. Assert on target DOM elements and text rather than waiting for page unload.
- **Post-Navigation Settle Time:** For multi-step forms or SPA / auth redirects, use a brief settle time (e.g. `->wait(2)` or `->waitForText(...)`) before the final assertion so the auto-captured snapshot captures the fully hydrated destination view.
- Interact with page: click, type, scroll, select, submit, drag-and-drop, touch gestures.
- Test on multiple browsers (Chrome, Firefox, Safari) if requested.
- Test on different devices/viewports (iPhone 14 Pro, tablets) if requested.
- Switch color schemes (light/dark mode) when appropriate.

<!-- Pest Browser Test Example -->
```php
it('may reset the password', function () {
    Notification::fake();

    $this->actingAs(User::factory()->create());

    $page = visit('/sign-in');

    $page->assertSee('Sign In')
        ->assertNoJavaScriptErrors()
        ->click('Forgot Password?')
        ->fill('email', 'nuno@laravel.com')
        ->click('Send Reset Link')
        ->assertSee('We have emailed your password reset link!');

    Notification::assertSent(ResetPassword::class);
});
```

### Smoke Testing

Quickly validate multiple pages have no JavaScript errors:

<!-- Pest Smoke Testing Example -->
```php
$pages = visit(['/', '/about', '/contact']);

$pages->assertNoJavaScriptErrors()->assertNoConsoleLogs();
```

### Visual Regression Testing

Capture and compare screenshots to detect visual changes.

### Architecture Testing

<!-- Architecture Test Example -->
```php
arch('controllers')
    ->expect('App\Http\Controllers')
    ->toExtendNothing()
    ->toHaveSuffix('Controller');
```
## Test Failure Diagnostics

When investigating test failures generated by Pest, **ALWAYS read the latest `*.log` file from the dedicated directory** using file tools:
- **Unit & Feature tests (`rubberstamp:features`)**: Read the latest `test_*.log` from `doctest-reports/test-log/`.
- **Browser tests (`rubberstamp:browser`)**: Read the latest `browser_test_*.log` from `doctest-reports/browser-test-log/` (and check visual snapshots in `doctest-reports/browser-test-log/browser_test_{timestamp}/`).

Do not rely exclusively on terminal output snippets provided by the user, as they may be truncated, mixed with TTY escape sequences, or out of date.

## Common Pitfalls

- Not importing `use function Pest\Laravel\mock;` before using mock
- Using `assertStatus(200)` instead of `assertSuccessful()`
- Forgetting datasets for repetitive validation tests
- Deleting tests without approval
- Forgetting `assertNoJavaScriptErrors()` in browser tests
- Prefixing `Feature/` or `Unit/` in `{name}` when using `make:test`
- Calling Laravel Dusk methods like `waitFor($selector)` in Pest 5 Browser tests (Playwright auto-waits on interaction methods; use `waitForText()` or `wait()` if explicit pause is needed)
- Using ambiguous text or generic CSS selectors in multi-step browser flows instead of `data-testid` and the `@` shorthand
- Asserting transient session flash messages across HTTP 302 redirects in Pest Browser (headless Chromium drops flash strings under array session driver; assert `assertPathIs()`, form context, and database existence instead)
- Forgetting settle delays (`wait(0.5)`) after typing in debounced Livewire search inputs or Alpine type-to-confirm modal inputs before asserting or clicking submit
