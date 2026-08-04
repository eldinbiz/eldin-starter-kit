---
name: pest-testing
description: "Use this skill for Pest PHP testing in Laravel projects only. Trigger whenever any test is being written, edited, fixed, or refactored — including fixing tests that broke after a code change, adding assertions, converting PHPUnit to Pest, adding datasets, and TDD workflows. Always activate when the user asks how to write something in Pest, mentions test files or directories (tests/Feature, tests/Unit, tests/Browser), or needs browser testing, smoke testing multiple pages for JS errors, architecture tests, Agent verification probes, TIA optimization, or AI evals. Covers: test()/it()/expect() syntax, datasets, mocking, browser testing (visit/click/fill), arch(), Livewire component tests, RefreshDatabase, Pest 5 Agent plugin, Tia Engine, Evals, and all Pest 5 features built on PHPUnit 13. Do not use for factories, seeders, migrations, controllers, models, or non-test PHP code."
license: MIT
metadata:
  author: laravel
  version: pest-5
---

# Pest Testing (Pest 5 + PHPUnit 13)

## Online Documentation

Pest provides LLM-friendly documentation at these URLs. Use the Playwright MCP tools (`playwright_navigate` + `playwright_get_visible_text`) to fetch them when you need API specifics beyond what this skill covers:

| Resource | URL |
|---|---|
| Master index | `https://pestphp.com/llms.txt` |
| Full docs (single file) | `https://pestphp.com/llms-full.txt` |
| Per-page docs | `https://pestphp.com/docs/{page}/llms.txt` (see feature map below) |

> **Research rule:** For detailed API specifics (expectation signatures, CLI flags, advanced mocking patterns) not covered in this skill, navigate to the relevant `llms.txt` URL using the Playwright MCP server. This aligns with [.agents/rules/web-research.md](file:///C:/Users/eldin.akbar/Documents/Other%20Scripts/scaffolding-app/.agents/rules/web-research.md).

## Pest 5 Feature Map

Each page has its own `llms.txt` at the URL shown:

| Feature | Doc URL |
|---|---|
| Installation | `https://pestphp.com/docs/installation/llms.txt` |
| Writing Tests | `https://pestphp.com/docs/writing-tests/llms.txt` |
| Expectations | `https://pestphp.com/docs/expectations/llms.txt` |
| Hooks | `https://pestphp.com/docs/hooks/llms.txt` |
| Datasets | `https://pestphp.com/docs/datasets/llms.txt` |
| Exceptions | `https://pestphp.com/docs/exceptions/llms.txt` |
| Filtering Tests | `https://pestphp.com/docs/filtering-tests/llms.txt` |
| Skipping Tests | `https://pestphp.com/docs/skipping-tests/llms.txt` |
| Optimizing Tests | `https://pestphp.com/docs/optimizing-tests/llms.txt` |
| Configuring Tests | `https://pestphp.com/docs/configuring-tests/llms.txt` |
| Grouping Tests | `https://pestphp.com/docs/grouping-tests/llms.txt` |
| Global Hooks | `https://pestphp.com/docs/global-hooks/llms.txt` |
| Custom Helpers | `https://pestphp.com/docs/custom-helpers/llms.txt` |
| Custom Expectations | `https://pestphp.com/docs/custom-expectations/llms.txt` |
| Mocking | `https://pestphp.com/docs/mocking/llms.txt` |
| Snapshot Testing | `https://pestphp.com/docs/snapshot-testing/llms.txt` |
| Browser Testing | `https://pestphp.com/docs/browser-testing/llms.txt` |
| **Agent** (Pest 5 new) | `https://pestphp.com/docs/agent/llms.txt` |
| Architecture Testing | `https://pestphp.com/docs/arch-testing/llms.txt` |
| Stress Testing | `https://pestphp.com/docs/stress-testing/llms.txt` |
| **Evals** (Pest 5 new) | `https://pestphp.com/docs/evals/llms.txt` |
| Test Coverage | `https://pestphp.com/docs/test-coverage/llms.txt` |
| Type Coverage | `https://pestphp.com/docs/type-coverage/llms.txt` |
| Mutation Testing | `https://pestphp.com/docs/mutation-testing/llms.txt` |
| **Tia Engine** (Pest 5 new) | `https://pestphp.com/docs/tia/llms.txt` |
| Rector | `https://pestphp.com/docs/rector/llms.txt` |
| PHPStan | `https://pestphp.com/docs/phpstan/llms.txt` |
| CLI API Reference | `https://pestphp.com/docs/cli-api-reference/llms.txt` |
| Test Dependencies | `https://pestphp.com/docs/test-dependencies/llms.txt` |
| Higher Order Testing | `https://pestphp.com/docs/higher-order-testing/llms.txt` |
| Upgrade Guide | `https://pestphp.com/docs/upgrade-guide/llms.txt` |

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

```php
it('is true', function () {
    expect(true)->toBeTrue();
});
```

### Running Tests

- Run minimal tests with filter before finalizing: `php artisan test --compact --filter=testName`.
- Run all tests (non-compact for verbose WARN output): `php artisan test`.
- Run file: `php artisan test tests/Feature/ExampleTest.php`.

> **Note:** This project intentionally runs tests without `--compact`. The non-compact formatter displays PHPUnit's `WARN` messages with full context, making framework-level notices (e.g., exception handler manipulation by Laravel's bootstrap) visible and explained rather than showing opaque `!` symbols.

## Assertions

Use specific assertions (`assertSuccessful()`, `assertNotFound()`) instead of `assertStatus()`:

```php
it('returns all', function () {
    $this->postJson('/api/docs', [])->assertSuccessful();
});
```

| Use | Instead of |
|---|---|
| `assertSuccessful()` | `assertStatus(200)` |
| `assertNotFound()` | `assertStatus(404)` |
| `assertForbidden()` | `assertStatus(403)` |

## Mocking

Import mock function before use: `use function Pest\Laravel\mock;`

For full mocking API (partial mocks, spies, `shouldReceive`, `shouldNotReceive`), consult `https://pestphp.com/docs/mocking/llms.txt`.

## Datasets

Use datasets for repetitive tests (validation rules, etc.):

```php
it('has emails', function (string $email) {
    expect($email)->not->toBeEmpty();
})->with([
    'james' => 'james@laravel.com',
    'taylor' => 'taylor@laravel.com',
]);
```

For bound datasets, scoped datasets, and `dataset()` function, consult `https://pestphp.com/docs/datasets/llms.txt`.

## Pest 5 Features

| Feature | Purpose | Pest 5? |
|---|---|---|
| Browser Testing | Full integration tests in real browsers | Enhanced |
| Architecture Testing | Enforce code conventions via `arch()` | ✅ |
| Stress Testing | Inspect stability under load | ✅ |
| Snapshot Testing | Compare values against stored snapshots | ✅ |
| Mutation Testing | Evaluate test suite quality via code changes | ✅ |
| Test Coverage | Measure code coverage percentage | ✅ |
| Type Coverage | Measure type declaration coverage | ✅ |
| **Agent Plugin** | AI agent verification probes via `--agent` | 🆕 Pest 5 |
| **Tia Engine** | Test Impact Analysis — re-runs only affected tests | 🆕 Pest 5 |
| **Evals** | Evaluate LLM/AI output quality from tests | 🆕 Pest 5 |

### Agent Plugin (Pest 5)

The Agent plugin lets AI coding agents verify their own work with a single CLI command. The snippet runs inside a real Pest test with your project's full configuration (factories, `RefreshDatabase`, mail fakes, etc.).

**Installation:**
```bash
composer require pestphp/pest-plugin-agent --dev
php artisan boost:install   # install agent guidelines/skills
```

**Syntax:**
```bash
./vendor/bin/pest --agent='<code snippet>'
```

**Key rules:**
- Wrap the snippet in **single quotes** (shell passthrough); use double quotes inside for PHP strings
- All classes **must be fully qualified** (`\App\Models\User`, not `User`) — no `use` imports in snippets
- Each `--agent` flag = one isolated `verify` test; pass multiple for multiple checks
- Directory-scoped `beforeEach()` hooks do NOT run — inline any required setup

**Backend verification:**
```bash
./vendor/bin/pest --agent='$user = \App\Models\User::factory()->create(); $this->actingAs($user)->get("/dashboard")->assertOk();'
```

**Frontend verification (requires Browser plugin):**
```bash
./vendor/bin/pest --agent='visit("/")->screenshot(filename: "homepage");'
./vendor/bin/pest --agent='visit("/")->assertSee("Welcome");'
./vendor/bin/pest --agent='visit("/")->on()->mobile()->screenshot(filename: "home-mobile");'
```

**Combined frontend + backend (killer feature):**
```bash
./vendor/bin/pest --agent='\Illuminate\Support\Facades\Mail::fake(); visit("/contact")->type("email", "test@example.com")->type("message", "Hello")->press("Send")->assertSee("Message sent"); \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\ContactForm::class);'
```

Assert frontend signals first (`assertSee`, `assertPathIs`), then check backend side effects.

**When to use `--agent`** (quick probe) vs. writing a permanent test:
- Agent: one-off verification after a code change
- Permanent test: behavior that deserves a lasting regression guard

For full Agent docs, consult `https://pestphp.com/docs/agent/llms.txt`.

### Tia Engine (Pest 5)

Test Impact Analysis maps your test suite to the code each test depends on. When you change a file, only the affected tests re-run — the rest replay from cache.

```bash
./vendor/bin/pest --tia
```

For full TIA docs, consult `https://pestphp.com/docs/tia/llms.txt`.

### Evals (Pest 5)

Evaluate LLM agent output quality directly from your test suite:

```php
it('answers support questions', function () {
    expect(SupportAgent::class)
        ->prompt('How do I reset my password?')
        ->repeat(50)
        ->toBeCorrect()
        ->toBeSafe(0.9)
        ->toSatisfy('Gives the password reset steps');
});
```

Run with: `./vendor/bin/pest --evals`

For full Evals docs, consult `https://pestphp.com/docs/evals/llms.txt`.

### Browser Testing

Browser tests run in real browsers for full integration testing:

- Browser tests live in `tests/Browser/`.
- Use Laravel features like `Event::fake()`, `assertAuthenticated()`, and model factories.
- Use `RefreshDatabase` for clean state per test.
- Interact with page: click, type, scroll, select, submit, drag-and-drop, touch gestures.
- Test on multiple browsers (Chrome, Firefox, Safari) if requested.
- Test on different devices/viewports (iPhone 14 Pro, tablets) if requested.
- Switch color schemes (light/dark mode) when appropriate.
- Take screenshots or pause tests for debugging.

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

For full Browser Testing API, consult `https://pestphp.com/docs/browser-testing/llms.txt`.

### Architecture Testing

```php
arch('controllers')
    ->expect('App\Http\Controllers')
    ->toExtendNothing()
    ->toHaveSuffix('Controller');
```

For full Architecture Testing API, consult `https://pestphp.com/docs/arch-testing/llms.txt`.

## Common Pitfalls

- Not importing `use function Pest\Laravel\mock;` before using mock
- Using `assertStatus(200)` instead of `assertSuccessful()`
- Forgetting datasets for repetitive validation tests
- Deleting tests without approval
- Forgetting `assertNoJavaScriptErrors()` in browser tests
- Prefixing `Feature/` or `Unit/` in `{name}` when using `make:test`
- In `--agent` snippets: using unqualified class names instead of fully-qualified (`\App\Models\User`)
- In `--agent` snippets: wrapping in double quotes (shell interpolates `$variables`) instead of single quotes
