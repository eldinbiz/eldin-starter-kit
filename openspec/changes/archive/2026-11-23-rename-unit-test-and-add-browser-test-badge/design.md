# Design

## Context

- **Current State:**
  - [README.md](../../../README.md) (lines 3–10) displays six dynamic Shields.io endpoint badges (`laravel.json`, `php.json`, `tests.json`, `pint.json`, `livewire.json`, `security.json`) served from the orphan `badges` branch.
  - [.github/workflows/tests.yml](../../../.github/workflows/tests.yml) executes `php artisan test --log-junit /tmp/junit.xml` on PHP 8.4 (`ubuntu-latest`) against SQLite `:memory:` and publishes `tests.json` with `"label": "Tests"`.
  - [phpunit.xml](../../../phpunit.xml) defines `Unit` (`tests/Unit`), `Feature` (`tests/Feature`), and `Modules` (`app-modules/*/tests`) suites while excluding `tests/Browser/`.
  - [tests/Pest.php](../../../tests/Pest.php) binds `Tests\TestCase` in `'Feature'`, `'Browser'`, and `'../app-modules/*/tests'`. Four Pest browser test suites already exist under `tests/Browser/` (`LoginTest.php`, `UserManagementTest.php`, `CompanyManagementTest.php`, `RoleManagementTest.php`), all tagged `->group('browser')`.
- **Browser Testing Stack & CI Constraints:**
  - [composer.json](../../../composer.json) requires `pestphp/pest-plugin-browser: ^5.0` (locked at `v5.2.0`) and `eldinbiz/laravel-rubberstamp: *@dev` via relative path `../laravel-rubberstamp`.
  - `pestphp/pest-plugin-browser` requires PHP extension `ext-sockets` (`vendor/pestphp/pest-plugin-browser/composer.json`) and validates `PlaywrightNpmServer::PLAYWRIGHT_VERSION = '1.63.0'` (`vendor/pestphp/pest-plugin-browser/src/Playwright/Servers/PlaywrightNpmServer.php`).
  - [package.json](../../../package.json) and [package-lock.json](../../../package-lock.json) currently pin `playwright` at `1.62.1`, which is below `1.63.0`.
  - `eldinbiz/laravel-rubberstamp`'s `TestBrowserCommand` (`php artisan rubberstamp:browser`) accepts positional `target` (`tests/Browser`), forwards unknown Pest flags such as `--log-junit=/tmp/browser-junit.xml` via `resolveForwardedArguments()`, suppresses interactive prompts when `--skip-orphan-check` (or non-interactive mode) is used, skips corporate HTML generation with `--no-doc`, and validates Node.js, local `node_modules/playwright`, cached Playwright Chromium in `$HOME/.cache/ms-playwright`, `vendor/bin/pest`, and `tests/Browser` via `BrowserEnvironmentDoctor`.
  - Laravel's `TestCommand` (`php artisan test tests/Browser --log-junit=/tmp/browser-junit.xml`) forwards `tests/Browser` and `--log-junit=/tmp/browser-junit.xml` directly to `vendor/pestphp/pest/bin/pest --configuration=phpunit.xml`, executing `tests/Browser/` with `phpunit.xml`'s SQLite `:memory:` and `APP_ENV=testing` overrides.

```
+-----------------------------------------------------------------------+
|  Maintainer manually dispatches workflow (Actions -> Run workflow)    |
|  on: workflow_dispatch                                                |
|  - tests.yml:         publish_badges (boolean, default: false)        |
|  - browser-tests.yml: publish_badges (boolean, default: false)        |
|                       runner (choice: rubberstamp | pest)             |
+----------------------------+----------------------+-------------------+
                             |                      |
                             v                      v
                  [tests.yml]                [browser-tests.yml]
                  PHP 8.4 + Vite +           PHP 8.4 (ext-sockets) +
                  SQLite :memory:            Vite + Playwright Chromium
                  (Unit/Feature/Modules)     (tests/Browser/)
                             |                      |
                             v                      +--> runner == rubberstamp:
                  php artisan test           |      php artisan rubberstamp:browser tests/Browser
                  --log-junit /tmp/junit.xml |        --no-doc --skip-orphan-check
                             |               |        --log-junit=/tmp/browser-junit.xml
                             |               |
                             |               +--> runner == pest:
                             |                      php artisan test tests/Browser
                             |                        --log-junit=/tmp/browser-junit.xml
                             |                      |
                             v                      v
                  tests.json                 browser-tests.json
                  label: "Unit Test"         label: "Browser Test"
                             |                      |
                             +----------+-----------+
                                        |
         if: always() && (github.ref == 'refs/heads/main' || inputs.publish_badges == true)
                                        v
                        .github/actions/publish-badge/action.yml
                        (orphan `badges` branch + rebase retry)
                                        |
                                        v
                  README.md (7 dynamic Shields.io endpoint badges)
```

## Goals / Non-Goals

**Goals:**
- Relabel the existing `tests.json` badge in `.github/workflows/tests.yml` from `"Tests"` to `"Unit Test"` without changing the `tests.json` filename, the `Unit` + `Feature` + `Modules` test scope, or the `workflow_dispatch` trigger pattern.
- Add `.github/workflows/browser-tests.yml` as an independent fifth `workflow_dispatch` workflow with both `publish_badges` (`false` default) and a UI `runner` selector (`rubberstamp` | `pest`, defaulting to `rubberstamp`).
- Ensure `browser-tests.yml` provisions `ext-sockets`, sibling `eldinbiz/laravel-rubberstamp`, compiled Vite assets, and Playwright Chromium + OS shared libraries reliably on `ubuntu-latest`.
- Upgrade `playwright` to `^1.63.0` in `package.json` and `package-lock.json` to satisfy `pestphp/pest-plugin-browser` `v5.2.0`.
- Publish `browser-tests.json` (`"label": "Browser Test"`) to the orphan `badges` branch and render it alongside `Unit Test` in [README.md](../../../README.md).

**Non-Goals:**
- Filtering `.github/workflows/tests.yml` down to `--testsuite=Unit` (per maintainer decision, `tests.yml` continues running `Unit` + `Feature` + `Modules`).
- Renaming `tests.json` or `.github/workflows/tests.yml` on disk (preserves existing `badges/tests.json` links).
- Adding automatic `push`, `pull_request`, or `schedule` workflow triggers.
- Modifying `phpunit.xml` to include `tests/Browser` (browser tests remain decoupled from `php artisan test` default runs).
- Uploading `doctest-reports/` HTML reports or screenshot artifacts from GitHub Actions (can be layered later if desired).

## Decisions

### 1. Keep `tests.json` and `.github/workflows/tests.yml` Filenames; Rename Only Badge Label & Metadata
- **Choice:** In `.github/workflows/tests.yml`, change `"label" => "Tests"` to `"label" => "Unit Test"` (plus update step/commit text to refer to the Unit Test badge) while keeping `tests.json` as the filename and `php artisan test --log-junit /tmp/junit.xml` as the command. In [README.md](../../../README.md), update `alt="Tests Status"` to `alt="Unit Test Status"` while keeping the `badges/tests.json` URL.
- **Alternatives Considered:** Renaming `tests.json` -> `unit-tests.json` and `tests.yml` -> `unit-tests.yml`.
- **Rationale:** Matches the maintainer's explicit preference, avoids orphaned `tests.json` files on the `badges` branch, and keeps links on prior commits/branches intact.

### 2. Dual-Runner Selection via `workflow_dispatch` `choice` Input (`rubberstamp` | `pest`)
- **Choice:** Define `inputs.runner` on `.github/workflows/browser-tests.yml` as:
  ```yaml
  runner:
    description: 'Browser test runner to execute'
    type: choice
    required: true
    default: 'rubberstamp'
    options:
      - rubberstamp
      - pest
  ```
  The execution step branches on `${{ inputs.runner }}`:
  - `rubberstamp`: `php artisan rubberstamp:browser tests/Browser --no-doc --skip-orphan-check --log-junit=/tmp/browser-junit.xml`
  - `pest`: `php artisan test tests/Browser --log-junit=/tmp/browser-junit.xml`
- **Details & Verification Against Package Internals:**
  - `TestBrowserCommand::resolveForwardedArguments()` strips RubberStamp internal options (`--no-doc`, `--skip-orphan-check`) and the positional `tests/Browser` target, passing `--log-junit=/tmp/browser-junit.xml` directly through to Pest. Using `=` syntax (`--log-junit=/tmp/browser-junit.xml`) ensures the file path token cannot interact with positional argument parsing.
  - Because `npx playwright install chromium --with-deps` populates `$HOME/.cache/ms-playwright/chromium-<rev>` (matching `node_modules/playwright-core/browsers.json`), `BrowserEnvironmentDoctor::checkChromiumBrowser()` succeeds cleanly without setting `PLAYWRIGHT_CHROMIUM_EXECUTABLE_PATH` and without invoking `fixLinuxContainerSymlinks()`.
  - If `rubberstamp:browser` aborts during `runDoctor()` (or hits idle timeout `124`), `/tmp/browser-junit.xml` will not exist and `exit_code` is non-zero; the badge step falls back to `"failing"` (`#ef4444`, `isError: true`), publishes `browser-tests.json` if gated, and fails the job in the verification step.
- **Alternatives Considered:** Hardcoding only `php artisan test tests/Browser` or creating two separate workflow files.
- **Rationale:** A single `workflow_dispatch` choice input lets the maintainer pick either runner from the GitHub UI while sharing setup, Chromium provisioning, JUnit XML parsing, and `browser-tests.json` publishing logic.

### 3. `ubuntu-latest` Browser Provisioning, `ext-sockets`, and Playwright `^1.63.0`
- **Choice:**
  - Enable `sockets` in `shivammathur/setup-php@v2` `extensions:` (`mbstring, xml, dom, curl, openssl, zip, gd, intl, bcmath, pdo, pdo_sqlite, sockets`).
  - Upgrade `playwright` in `package.json` and `package-lock.json` from `^1.62.1` (`1.62.1`) to `^1.63.0` via `npm install playwright@^1.63.0` so `npm ci --ignore-scripts` installs a version `>= 1.63.0` matching `PlaywrightNpmServer::PLAYWRIGHT_VERSION`.
  - Run `npx playwright install chromium --with-deps` after `npm ci --ignore-scripts --no-audit --no-fund` and `npm run build`.
- **Alternatives Considered:** Caching `~/.cache/ms-playwright` keyed on `package-lock.json` and calling `npx playwright install-deps chromium` separately on cache hit.
- **Rationale:** `npx playwright install chromium --with-deps` is straightforward and self-contained; if desired, adding `actions/cache@v4` on `~/.cache/ms-playwright` keyed by `hashFiles('starter-kit/package-lock.json')` can be paired with `npx playwright install-deps chromium` so shared OS libraries (`libnss3`, `libgbm1`, etc.) are still installed on fresh runners. Using `npx playwright install chromium --with-deps` unconditionally (or `install --with-deps` on cache miss + `install-deps` on cache hit) guarantees Chromium always launches cleanly.

### 4. Browser Test Badge Payload & README Integration
- **Choice:**
  - Generate `browser-tests.json` using the same PHP JUnit XML parser pattern as `tests.yml`, setting `"label" => "Browser Test"`.
  - Publish via `./starter-kit/.github/actions/publish-badge` with `commit_message: 'chore(badges): update browser tests badge'` under `if: always() && (github.ref == 'refs/heads/main' || inputs.publish_badges == true)`.
  - In [README.md](../../../README.md), place the `Browser Test` badge immediately after `Unit Test`:
    - `Unit Test`: `<img src="https://img.shields.io/endpoint?url=https://raw.githubusercontent.com/eldinbiz/eldin-starter-kit/badges/tests.json&style=flat-square&logo=pest&logoColor=white&cacheSeconds=300" alt="Unit Test Status">`
    - `Browser Test`: `<img src="https://img.shields.io/endpoint?url=https://raw.githubusercontent.com/eldinbiz/eldin-starter-kit/badges/browser-tests.json&style=flat-square&logo=googlechrome&logoColor=white&cacheSeconds=300" alt="Browser Test Status">`
- **Rationale:** Simple Icons does not include a `playwright` slug (removed due to trademark restrictions), whereas `googlechrome` is an active Simple Icons slug on Shields.io and visually distinguishes browser E2E tests from `logo=pest` on `Unit Test`.

## Risks / Trade-offs

- **[Risk] Initial `browser-tests.json` missing until first authorized workflow run** -> Immediately after merging to `main` (or before, via `development`), `badges/browser-tests.json` does not exist until `browser-tests.yml` runs with `publish_badges=true` or on `main`, and `badges/tests.json` still shows `"Tests"` until `tests.yml` runs again. **Mitigation:** Include post-merge/post-push workflow dispatch verification steps in `tasks.md`.
- **[Risk] Headless browser timing sensitivity on shared GitHub runners** -> Browser tests (especially `CompanyManagementTest.php` D3 waits or Livewire debounced searches) run slightly slower on 2-vCPU GitHub Actions runners than local machines. **Mitigation:** `TestBrowserCommand` defaults to a 90s per-test idle timeout and `RubberStamp`'s `browser_timeout` defaults to `10000ms`; if any individual test flakes on CI, `runner: pest` and `runner: rubberstamp` both provide full stdout logs in GitHub Actions for immediate diagnosis.
- **[Risk] `composer.json` / `package-lock.json` Playwright upgrade side effects** -> Upgrading `playwright` in `package-lock.json` may require re-running `npx playwright install chromium` on developer workstations that already cached `1.62.1`'s Chromium revision. **Mitigation:** Document running `npm install && npx playwright install chromium` in the tasks/rollout notes.

## Migration Plan

1. On `development`, update `package.json` and `package-lock.json` (`playwright: ^1.63.0`).
2. Update `.github/workflows/tests.yml` (`"label" => "Unit Test"`), create `.github/workflows/browser-tests.yml`, and update [README.md](../../../README.md).
3. Validate YAML syntax and run local test checks (`php artisan test` and browser test command verification where Chromium is available).
4. Push `development` and manually dispatch both `tests.yml` and `browser-tests.yml` (testing both `rubberstamp` and `pest` runners, with `publish_badges: true` if verifying on `development`, or after promoting `development` -> `staging` -> `main`).
5. Confirm `raw.githubusercontent.com/eldinbiz/eldin-starter-kit/badges/tests.json` shows `"label": "Unit Test"` and `browser-tests.json` shows `"label": "Browser Test"`, and verify all seven badges render cleanly in [README.md](../../../README.md).
6. **Rollback:** Revert the commit on `development`, re-run `tests.yml` with `publish_badges: true` to restore `"label": "Tests"` on `tests.json`, and remove `browser-tests.json` from the `badges` branch if needed.
