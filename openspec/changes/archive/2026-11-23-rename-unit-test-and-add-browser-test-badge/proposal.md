# Proposal

## Why

The [README.md](../../../README.md) header currently displays a single `Tests` badge (`badges/tests.json`) backed by [.github/workflows/tests.yml](../../../.github/workflows/tests.yml). Because `phpunit.xml` includes only `Unit`, `Feature`, and `Modules` suites while excluding `tests/Browser/`, the `Tests` badge neither distinguishes fast in-memory suites from Playwright browser tests nor surfaces end-to-end browser test health.

Renaming the existing badge label to `Unit Test` (while keeping its existing `tests.json` endpoint and `Unit` + `Feature` + `Modules` test scope) and adding a dedicated `Browser Test` GitHub Actions workflow and `browser-tests.json` badge makes both test layers visible and independently verifiable on demand. At the same time, aligning `playwright` in `package.json` / `package-lock.json` to `^1.63.0` resolves a version mismatch with `pestphp/pest-plugin-browser` `v5.2.0` (`PlaywrightNpmServer::PLAYWRIGHT_VERSION = '1.63.0'`).

## What Changes

- **Rename `"Tests"` badge label to `"Unit Test"`:**
  - Update [.github/workflows/tests.yml](../../../.github/workflows/tests.yml) so the generated `tests.json` payload sets `"label": "Unit Test"` (retaining the `tests.json` filename, `workflow_dispatch` + `publish_badges` trigger pattern, and full `Unit` + `Feature` + `Modules` test scope).
  - Update [README.md](../../../README.md) badge metadata for `tests.json` (`alt="Unit Test Status"`).
- **Add Browser Test workflow and `"Browser Test"` badge:**
  - Create `.github/workflows/browser-tests.yml` triggered solely via `workflow_dispatch` with:
    - `publish_badges` boolean input (default `false`), matching existing workflows.
    - `runner` choice input (`rubberstamp` or `pest`, default `rubberstamp`) so maintainers can pick between `php artisan rubberstamp:browser` and `php artisan test` directly from the GitHub Actions UI.
  - Configure PHP 8.4 with `sockets` enabled, sibling `eldinbiz/laravel-rubberstamp` checkout, Vite asset build, and Playwright Chromium + system dependency installation (`npx playwright install chromium --with-deps`) on `ubuntu-latest`.
  - Parse `/tmp/browser-junit.xml` into Shields.io Endpoint v1 JSON (`browser-tests.json` with `"label": "Browser Test"`), publish to the orphan `badges` branch via `.github/actions/publish-badge` when on `main` or when `publish_badges` is `true`, and exit non-zero on failures.
  - Add the `Browser Test Status` badge in [README.md](../../../README.md) pointing to `badges/browser-tests.json`.
- **Upgrade Playwright NPM dependency:**
  - Update `playwright` in `package.json` and `package-lock.json` from `^1.62.1` (`1.62.1`) to `^1.63.0` to match `pestphp/pest-plugin-browser` `v5.2.0`.

## Capabilities

### New Capabilities

<!-- None: extends existing ci-quality-workflows and readme-status-badges specifications. -->

### Modified Capabilities

- `ci-quality-workflows`: Extend the user-triggered workflow layout to five independent workflows by adding `browser-tests.yml` with selectable `rubberstamp` / `pest` execution, `ext-sockets` + Playwright Chromium provisioning, and `playwright` `^1.63.0` dependency alignment.
- `readme-status-badges`: Rename the `tests.json` badge label to `"Unit Test"`, define the `browser-tests.json` (`"Browser Test"`) endpoint schema, and add the seventh `Browser Test` badge to [README.md](../../../README.md).

## Impact

- **Files added/modified (upon apply):**
  - `.github/workflows/tests.yml` (modified: `"label" => "Unit Test"` and descriptive step/commit message text)
  - `.github/workflows/browser-tests.yml` (new)
  - `README.md` (modified: update `tests.json` alt text and add `browser-tests.json` badge)
  - `package.json` & `package-lock.json` (modified: `playwright` updated to `^1.63.0`)
- **Remote `badges` orphan branch:**
  - `tests.json` updated in place to `"label": "Unit Test"` on next publishing run.
  - `browser-tests.json` created on first authorized browser workflow dispatch.
- **No application runtime or schema impact:** No changes to `app/`, `app-modules/`, `config/`, `phpunit.xml`, `tests/`, or database migrations.
