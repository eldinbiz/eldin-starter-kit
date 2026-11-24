# Spec Delta

## Purpose

Extends the user-triggered GitHub Actions verification suite to include an independent Playwright browser testing workflow with selectable runner (`rubberstamp` or `pest`) and aligns the locked Playwright NPM dependency with `pestphp/pest-plugin-browser`.

## MODIFIED Requirements

### Requirement: User-Triggered Verification Workflow Layout
The repository MUST expose separate GitHub Actions workflows for automated Unit/Feature/Module tests (`tests.yml`), end-to-end browser tests (`browser-tests.yml`), Laravel Pint formatting (`pint.yml`), security auditing (`security.yml`), and stack version reporting (`versions.yml`), each triggered solely via manual `workflow_dispatch` with an optional boolean `publish_badges` input defaulting to `false`. The `browser-tests.yml` workflow MUST additionally expose a `runner` `choice` input (`rubberstamp`, `pest`) defaulting to `rubberstamp`.

#### Scenario: Independent manual workflow execution
- **GIVEN** the repository contains separate workflow files for Unit/Feature/Module tests, browser tests, Pint, security, and versions
- **WHEN** a maintainer triggers one workflow via `workflow_dispatch`
- **THEN** only the selected check executes without running the remaining four workflows
- **AND** no workflows trigger automatically on `push` or `pull_request` events.

#### Scenario: Dispatch with default inputs on non-default branches
- **GIVEN** a maintainer triggers a workflow manually on the `development` branch without overriding inputs
- **WHEN** `publish_badges` retains its default value of `false`
- **THEN** the workflow executes its full check suite and logs results without publishing changes to the `badges` branch.

#### Scenario: Browser test runner selection via GitHub Actions UI
- **GIVEN** a maintainer opens the `Browser Tests` workflow dispatch dialog in the GitHub Actions UI
- **WHEN** they select either `rubberstamp` or `pest` in the `runner` dropdown and dispatch the workflow
- **THEN** the workflow executes `tests/Browser/` using the selected runner without modifying repository files.

## ADDED Requirements

### Requirement: PHP 8.4 End-to-End Browser Test Execution and Selectable Runner
The browser tests workflow (`.github/workflows/browser-tests.yml`) MUST run on `ubuntu-latest` with PHP 8.4 and the `sockets` extension enabled, resolve the sibling `eldinbiz/laravel-rubberstamp` path repository, prepare `.env`, `APP_KEY`, and built Vite manifest assets, install Playwright Chromium and Linux OS dependencies (`npx playwright install chromium --with-deps`), execute `tests/Browser/` against SQLite `:memory:` using the selected `runner` (`rubberstamp` or `pest`) while writing JUnit XML to `/tmp/browser-junit.xml`, and exit non-zero on failure.

#### Scenario: Browser test suite passes using RubberStamp runner
- **GIVEN** PHP 8.4 with `ext-sockets`, Composer dependencies, compiled Vite assets, and Playwright Chromium with OS dependencies are prepared
- **WHEN** `browser-tests.yml` executes with `runner` set to `rubberstamp`
- **THEN** it runs `php artisan rubberstamp:browser tests/Browser --no-doc --skip-orphan-check --log-junit=/tmp/browser-junit.xml`
- **AND** `BrowserEnvironmentDoctor` passes, all `tests/Browser/` cases execute against SQLite `:memory:`, total passing tests are parsed from `/tmp/browser-junit.xml`, and the workflow exits `0`.

#### Scenario: Browser test suite passes using direct Pest runner
- **GIVEN** PHP 8.4 with `ext-sockets`, Composer dependencies, compiled Vite assets, and Playwright Chromium with OS dependencies are prepared
- **WHEN** `browser-tests.yml` executes with `runner` set to `pest`
- **THEN** it runs `php artisan test tests/Browser --log-junit=/tmp/browser-junit.xml`
- **AND** all `tests/Browser/` cases execute against SQLite `:memory:`, total passing tests are parsed from `/tmp/browser-junit.xml`, and the workflow exits `0`.

#### Scenario: Browser test or pre-flight failure reports failure status and fails the run
- **GIVEN** one or more browser tests fail or `rubberstamp:browser` pre-flight diagnostics fail
- **WHEN** the browser execution step finishes
- **THEN** the workflow records the failure status and failing count (or `"failing"` if `/tmp/browser-junit.xml` was not generated) for badge publishing
- **AND** exits with a non-zero status after the badge generation and publishing steps.

### Requirement: Playwright Dependency Version Alignment
The repository's NPM manifest (`package.json`) and lockfile (`package-lock.json`) MUST require and lock `playwright` at `^1.63.0` (`>= 1.63.0`) so CI (`npm ci`) and local installations satisfy `pestphp/pest-plugin-browser`'s minimum `PLAYWRIGHT_VERSION` (`1.63.0`).

#### Scenario: Locked Playwright version satisfies pest-plugin-browser minimum
- **GIVEN** `composer.lock` pins `pestphp/pest-plugin-browser` requiring Playwright `>= 1.63.0`
- **WHEN** `npm ci --ignore-scripts` installs dependencies from `package-lock.json`
- **THEN** the installed `playwright` package version is `>= 1.63.0` without triggering `PlaywrightOutdatedException`.
