# Proposal

## Why

The six status and stack badges below the title in [README.md](README.md) (`Laravel`, `PHP`, `Tests`, `Pint`, `Livewire`, and `Security`) currently point to static `img.shields.io/badge/...` URLs that can drift from the actual state of the codebase (for example, `Laravel` still mentions `12.x | 13.x`, `PHP` lists `8.3 | 8.4`, and `Security` permanently shows `pending audit`). Replacing these static badges with dynamic Shields.io endpoint badges backed by user-triggered (`workflow_dispatch`) GitHub Actions workflows keeps the repository landing page truthful, verifiable on demand, and aligned with the automated `composer audit` and `npm audit` checks.

## What Changes

- Add four separate, user-triggered (`on: workflow_dispatch`) GitHub Actions workflows (Layout 2) under `.github/workflows/`:
  - `tests.yml`: Executes the Pest 5 test suite (`Unit`, `Feature`, and `Modules` suites from `phpunit.xml` on PHP 8.4 and SQLite `:memory:`, excluding Playwright browser tests) and reports pass/fail status and test counts.
  - `pint.yml`: Executes `vendor/bin/pint --test` on PHP 8.4 and reports code-style compliance.
  - `security.yml`: Executes automated security vulnerability audits across all locked dependencies including dev packages (`composer audit` + `npm audit`) and reports aggregate findings and workflow pass/fail status.
  - `versions.yml`: Extracts the active PHP 8.4 runtime minor version (`8.4`) plus exact locked versions of `laravel/framework` and `livewire/livewire` from `composer.lock`.
- Add a shared composite action (`.github/actions/publish-badge/action.yml`) to commit and push Shields.io v1 endpoint JSON files to an orphan `badges` branch with concurrency-safe `git pull --rebase` retries, no-op skip logic when values are unchanged, and branch ref gating (`refs/heads/main` by default, with an optional manual `publish_badges` override input).
- Update [README.md](README.md) lines 3–10 to replace the six static badge URLs with dynamic `https://img.shields.io/endpoint` URLs pointing to the raw `badges` branch JSON files (`laravel.json`, `php.json`, `tests.json`, `pint.json`, `livewire.json`, and `security.json`), preserving existing visual styling (`flat-square` and logos).

## Capabilities

### New Capabilities

- `ci-quality-workflows`: User-triggered GitHub Actions verification and metadata workflows (`tests.yml`, `pint.yml`, `security.yml`, `versions.yml`), including sibling `eldinbiz/laravel-rubberstamp` path dependency checkout, PHP 8.4 / Vite asset preparation, full Composer + NPM security auditing, and stack version extraction.
- `readme-status-badges`: Dynamic Shields.io endpoint JSON generation, status/severity color mapping, orphan `badges` branch publishing with concurrency retry and branch gating, and [README.md](README.md) badge URL integration.

### Modified Capabilities

<!-- None: no existing specs in openspec/specs/ are affected by CI workflow or README badge automation. -->

## Impact

- **Repository files added/modified (upon apply):**
  - `.github/actions/publish-badge/action.yml` (new shared composite action)
  - `.github/workflows/tests.yml` (new)
  - `.github/workflows/pint.yml` (new)
  - `.github/workflows/security.yml` (new)
  - `.github/workflows/versions.yml` (new)
  - `README.md` (lines 3–10 updated to dynamic `img.shields.io/endpoint` URLs)
  - Remote orphan Git branch `badges` created on first authorized workflow run containing `*.json` badge payloads
- **External dependencies / GitHub integrations:**
  - Checks out sibling public repository `eldinbiz/laravel-rubberstamp` into `../laravel-rubberstamp` to satisfy the path repository entry in `composer.json`.
  - Uses `GITHUB_TOKEN` with `permissions: contents: write` to update the `badges` branch and relies on `img.shields.io` reading `raw.githubusercontent.com/eldinbiz/eldin-starter-kit/badges/*.json` over public HTTPS.
- **No application runtime or database impact:** No changes to `app/`, `app-modules/`, `config/`, database schemas, or PHP/JS runtime dependencies.
