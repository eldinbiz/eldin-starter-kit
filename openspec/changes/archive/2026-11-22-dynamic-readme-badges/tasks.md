# Tasks

## 1. Shared Badge Publishing Composite Action

- [x] 1.1 Create `.github/actions/publish-badge/action.yml` accepting a staged JSON directory path, commit message, and target branch (`badges`), initializing an isolated orphan `badges` branch clone on first run, skipping empty commits via `git diff --staged --quiet`, and pushing with an up-to-5-attempt `git pull --rebase` retry loop; verify YAML syntax parses cleanly and all composite action inputs/outputs are declared.

## 2. User-Triggered Verification and Metadata Workflows (Layout 2)

- [x] 2.1 Create `.github/workflows/tests.yml` triggered on `workflow_dispatch` (with `publish_badges` boolean input defaulting to `false` and `permissions: contents: write`), checking out sibling `starter-kit` and `laravel-rubberstamp`, setting up PHP 8.4 and Node.js 24, copying `.env.example` to `.env`, generating `APP_KEY`, building Vite assets, running `php artisan test --log-junit /tmp/junit.xml` on SQLite `:memory:` (excluding `tests/Browser/`), generating Shields Endpoint v1 `tests.json` (`<N> passed` `#22c55e` or `<F> failed` / `failing` `#ef4444` with `isError: true`), invoking `.github/actions/publish-badge` when `github.ref == 'refs/heads/main' || inputs.publish_badges`, and exiting non-zero on failure; verify workflow YAML validity and confirm JUnit XML test count parsing locally.
- [x] 2.2 Create `.github/workflows/pint.yml` triggered on `workflow_dispatch` (`publish_badges` input, `permissions: contents: write`), checking out sibling `starter-kit` and `laravel-rubberstamp`, setting up PHP 8.4 + Composer install, running `./vendor/bin/pint --test`, generating Shields Endpoint v1 `pint.json` (`passing` `#22c55e` or `failing` `#ef4444` with `isError: true`), publishing via `.github/actions/publish-badge` on gated refs, and failing the job on non-zero Pint exit; verify workflow YAML validity and confirm `./vendor/bin/pint --test` exit handling.
- [x] 2.3 Create `.github/workflows/security.yml` triggered on `workflow_dispatch` (`publish_badges` input, `permissions: contents: write`), checking out sibling `starter-kit` and `laravel-rubberstamp`, running `composer audit --locked --abandoned=report --format=json` and `npm audit --json` across all runtime and development dependencies, verifying valid JSON payloads (publishing `audit error` on registry/parse errors), generating Shields Endpoint v1 `security.json` (`0 issues` `#22c55e`, `1 issue` / `<N> issues` `#eab308` for low/moderate/medium, `#ef4444` for high/critical with `isError: true`), publishing on gated refs, and exiting non-zero on advisories or errors; verify workflow YAML validity and test the parser locally against `0`-issue, warning, critical, and malformed-JSON fixtures.
- [x] 2.4 Create `.github/workflows/versions.yml` triggered on `workflow_dispatch` (`publish_badges` input, `permissions: contents: write`), checking out `starter-kit`, setting up PHP 8.4, extracting the PHP minor version (`8.4`) and exact `laravel/framework` and `livewire/livewire` versions from `composer.lock`, failing safely if any required version is missing, generating `laravel.json` (`#FF2D20`), `php.json` (`#777BB4`), and `livewire.json` (`#FB70A9`), and publishing atomically via `.github/actions/publish-badge` on gated refs; verify workflow YAML validity and test the extraction script against `composer.lock` locally.

## 3. README Dynamic Badge Integration

- [x] 3.1 Update `README.md` (lines 3–10) to replace all six static `img.shields.io/badge/...` URLs with `https://img.shields.io/endpoint?url=https://raw.githubusercontent.com/eldinbiz/eldin-starter-kit/badges/<file>.json&style=flat-square&logo=<logo>&logoColor=white&cacheSeconds=300` while preserving badge order, logos, and accessible `alt` attributes; verify zero remaining static badge URLs in `README.md`.

## 4. End-to-End Integration & Bootstrap Verification

- [x] 4.1 Validate all YAML files in `.github/` (YAML syntax check and workflow trigger/permission/path checks) and confirm `.gitignore` / `.gitattributes` allow tracking `.github/` cleanly in Git.
- [x] 4.2 After pushing to GitHub (via `development` with `publish_badges=true` or after promoting to `main`), manually dispatch all four workflows (`tests.yml`, `pint.yml`, `security.yml`, `versions.yml`) from GitHub Actions, verify the orphan `badges` branch serves valid JSON at `raw.githubusercontent.com/eldinbiz/eldin-starter-kit/badges/{laravel,php,tests,pint,livewire,security}.json`, and confirm all six badges render accurately in `README.md`.

## Workflow follow-up

- Run `/openspec-archive-change` for `dynamic-readme-badges` once all four workflows have executed cleanly on GitHub and the rendered `README.md` badges are confirmed.
