# CI Quality Workflows Specification

## Purpose

Defines user-triggered GitHub Actions verification and metadata workflows for testing, formatting, dependency security auditing, and stack version reporting in the starter kit repository.

## Requirements

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

### Requirement: Sibling RubberStamp Path Repository Resolution
Workflows that execute Composer installation MUST resolve the public `eldinbiz/laravel-rubberstamp` repository into the sibling `../laravel-rubberstamp` directory relative to the starter kit root before invoking Composer.

#### Scenario: Composer resolves path dependency cleanly in GitHub Actions
- **GIVEN** `composer.json` declares `eldinbiz/laravel-rubberstamp` via the relative path `../laravel-rubberstamp`
- **WHEN** a verification workflow checks out the starter kit and `eldinbiz/laravel-rubberstamp`
- **THEN** `composer install` resolves `eldinbiz/laravel-rubberstamp` without missing-path or repository errors.

### Requirement: PHP 8.4 Automated Test Suite Execution
The tests workflow MUST run on PHP 8.4 only, prepare `.env`, `APP_KEY`, and built Vite manifest assets, and execute the configured `Unit`, `Feature`, and `Modules` suites against SQLite `:memory:` while excluding Playwright browser tests.

#### Scenario: Non-browser test suite passes on PHP 8.4
- **GIVEN** PHP 8.4, Composer dependencies, `.env` with `APP_KEY`, and compiled Vite assets are prepared
- **WHEN** the tests workflow executes the configured Pest/PHPUnit suites excluding `tests/Browser/`
- **THEN** all Unit, Feature, and Module tests run against in-memory SQLite
- **AND** the workflow records the total passing test count and exits successfully.

#### Scenario: Test failure reports failure status and fails the run
- **GIVEN** one or more assertions fail or error during the tests workflow run
- **WHEN** test execution finishes
- **THEN** the workflow records the failure status and failing count for badge publishing
- **AND** exits with a non-zero status so GitHub marks the run as failed.

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

### Requirement: Code Style Verification via Pint
The Pint workflow MUST run Laravel Pint in non-mutating check mode (`--test`) on PHP 8.4 against the repository working tree and fail whenever formatting violations are detected.

#### Scenario: Compliant codebase passes Pint check
- **GIVEN** all PHP files comply with the project's Pint style rules
- **WHEN** the Pint workflow executes `vendor/bin/pint --test`
- **THEN** the workflow records a passing status and completes with exit code `0`.

#### Scenario: Style violation fails Pint check
- **GIVEN** one or more PHP files contain formatting issues flagged by Pint
- **WHEN** the Pint workflow executes `vendor/bin/pint --test`
- **THEN** the workflow records a failing status without modifying source files
- **AND** completes with a non-zero exit code after badge evaluation.

### Requirement: Full-Scope Dependency Security Auditing
The security workflow MUST run `composer audit` and `npm audit` across all locked runtime and development dependencies, excluding abandoned-package exit codes from vulnerability counts, and MUST fail the workflow run if any advisory or audit execution error occurs.

#### Scenario: Zero advisories across Composer and NPM dependencies
- **GIVEN** locked dependencies in `composer.lock` and `package-lock.json` have zero known security advisories
- **WHEN** the security workflow executes `composer audit` (covering require and require-dev) and `npm audit` (covering dependencies and devDependencies)
- **THEN** the aggregate advisory count is `0`
- **AND** the workflow completes with exit code `0`.

#### Scenario: Advisories present in Composer or NPM dependencies
- **GIVEN** one or more locked Composer or NPM packages (including dev dependencies) have published advisories
- **WHEN** the security workflow parses both JSON audit reports
- **THEN** the workflow sums the vulnerabilities and tracks the highest severity tier
- **AND** exits with a non-zero status after badge evaluation.

#### Scenario: Audit tool or registry failure
- **GIVEN** Composer Packagist or the NPM registry returns an error or invalid JSON output
- **WHEN** the security workflow inspects the audit outputs
- **THEN** the workflow treats the run as an audit error rather than `0 issues`
- **AND** exits with a non-zero status.

### Requirement: Stack Version Metadata Extraction
The versions workflow MUST extract the active PHP minor runtime version (`8.4`) and the exact locked versions of `laravel/framework` and `livewire/livewire` from `composer.lock`.

#### Scenario: Extracting runtime and locked package versions
- **GIVEN** the runner uses PHP 8.4 and `composer.lock` locks `laravel/framework` and `livewire/livewire`
- **WHEN** the versions workflow inspects PHP and `composer.lock`
- **THEN** it produces normalized version strings (`8.4` for PHP and exact `v<major>.<minor>.<patch>` versions for Laravel and Livewire).

#### Scenario: Missing locked package entry fails safely
- **GIVEN** `composer.lock` is missing an entry for `laravel/framework` or `livewire/livewire`
- **WHEN** the versions workflow attempts extraction
- **THEN** the workflow fails with a descriptive error without publishing empty badge messages.
