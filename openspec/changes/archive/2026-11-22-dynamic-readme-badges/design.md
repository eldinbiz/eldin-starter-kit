# Design

## Context

- **Current State:** [README.md](README.md) (lines 3–10) renders six static `img.shields.io/badge/...` badges (`Laravel`, `PHP`, `Tests`, `Pint`, `Livewire`, `Security`). No `.github/workflows/` or CI pipelines currently exist. `.gitignore` no longer ignores `.github/`, so workflows and local composite actions are tracked natively in Git (`.gitattributes` retains `/.github export-ignore` so downloaded archives stay clean).
- **Dependency & Runtime Constraints:**
  - `composer.json` declares `"eldinbiz/laravel-rubberstamp": "*@dev"` via `"type": "path", "url": "../laravel-rubberstamp"`. Both `eldinbiz/eldin-starter-kit` and `eldinbiz/laravel-rubberstamp` are public GitHub repositories.
  - Although `composer.json` states `"php": "^8.3"`, `composer.lock` pins `symfony/console` `v8.1.2` (`php >= 8.4.1`), and `Dockerfile`, `.agents/environment.json`, and `.agents/rules/php84.md` standardize on PHP 8.4.
  - `phpunit.xml` runs `Unit` (`tests/Unit`), `Feature` (`tests/Feature`), and `Modules` (`app-modules/*/tests`) against SQLite `:memory:` and excludes Playwright browser tests in `tests/Browser/`.
  - Both application layouts (`resources/views/themes/tailadmin/layouts/app.blade.php` and `resources/views/themes/shadcn/layouts/app.blade.php`) invoke `@vite(['resources/css/app.css', 'resources/js/app.js'])`, and `.env.example` ships with an empty `APP_KEY=`, so Feature tests require `.env`, `php artisan key:generate`, and `npm run build` (`public/build/manifest.json`) prior to `php artisan test`.
  - Both `composer.lock` (`laravel/framework` `v13.19.0`, `livewire/livewire` `v4.4.2`) and `package-lock.json` are committed.

```
+-------------------------------------------------------------------+
|  User manually triggers workflow (Actions -> Run workflow)        |
|  on: workflow_dispatch (+ optional input: publish_badges=false)   |
+---------------------------------+---------------------------------+
                                  |
     +-------------------+--------+--------+-------------------+
     |                   |                 |                   |
     v                   v                 v                   v
[tests.yml]          [pint.yml]      [security.yml]     [versions.yml]
PHP 8.4 +            PHP 8.4 +       composer audit     PHP 8.4 runtime
Vite + SQLite        Pint --test     + npm audit        + composer.lock
     |                   |                 |                   |
     v                   v                 v                   v
tests.json           pint.json       security.json      laravel.json
                                                        php.json
                                                        livewire.json
     |                   |                 |                   |
     +-------------------+--------+--------+-------------------+
                                  |
   if: always() && (github.ref == 'refs/heads/main' || inputs.publish_badges)
                                  v
            +------------------------------------------+
            | .github/actions/publish-badge/action.yml |
            | - Isolated orphan `badges` branch clone  |
            | - No-op guard if JSON unchanged          |
            | - git pull --rebase retry loop (up to 5) |
            +---------------------+--------------------+
                                  |
                                  v
   https://raw.githubusercontent.com/eldinbiz/eldin-starter-kit/badges/*.json
                                  |
                                  v
              https://img.shields.io/endpoint?url=... --> README.md
```

## Goals / Non-Goals

**Goals:**
- Implement Layout 2: four standalone `workflow_dispatch` workflows (`tests.yml`, `pint.yml`, `security.yml`, `versions.yml`) plus one reusable composite action (`.github/actions/publish-badge/action.yml`).
- Keep `main`, `staging`, and `development` commit history free of automated badge commits by storing badge JSON endpoints on an orphan `badges` branch.
- Publish accurate badge states even when verification fails (e.g., `Tests: N failed`, `Pint: failing`, `Security: N issues` / `audit error`) while still exiting the workflow run non-zero on failures.
- Support safe concurrency when multiple workflows finish near-simultaneously.

**Non-Goals:**
- Triggering workflows automatically on `push`, `pull_request`, or `schedule` (can be layered later without altering the badge schema or publishing action).
- Running Playwright end-to-end browser tests (`tests/Browser/`) inside `tests.yml`.
- Modifying `composer.json` PHP constraint (`^8.3`) or altering `composer.json` repository definitions.

## Decisions

### 1. Orphan `badges` Branch + Shields.io Endpoint v1 JSON for All Six Badges
- **Choice:** Every workflow generates Shields.io Endpoint v1 JSON files (`tests.json`, `pint.json`, `security.json`, `laravel.json`, `php.json`, `livewire.json`) and pushes them to an orphan `badges` branch read via `raw.githubusercontent.com/eldinbiz/eldin-starter-kit/badges/<name>.json`.
- **Alternatives Considered:** Using Shields' built-in GitHub Workflow Status badge for `Tests`/`Pint` (`passing`/`failing`) and `badges` JSON only for `Security`/`Versions`; or committing badge files directly to `main`.
- **Rationale:** Storing all six badges as endpoint JSONs on an orphan `badges` branch provides uniform styling, richer metrics (`374 passed`, `0 issues`, exact `v13.19.0`), zero commit noise on development/release branches, and decouples `workflow_dispatch` parameter differences that affect native workflow badges.

### 2. Shared Composite Action with Explicit Exit-Code Capture and Rebase Retries
- **Choice:** Encapsulate `badges` branch publishing in `.github/actions/publish-badge/action.yml`. In each checking workflow:
  1. The check step captures its exit code with `set +e` into `GITHUB_OUTPUT` (instead of immediately aborting the job) and writes the resulting badge JSON into a temp staging directory.
  2. The `publish-badge` composite action runs with `if: always() && (github.ref == 'refs/heads/main' || inputs.publish_badges == true)` using `permissions: contents: write`. It clones/fetches `origin/badges` into `${{ runner.temp }}/badges-repo` (or initializes `git checkout --orphan badges` if absent on first run), copies the staged JSON file(s), skips committing if `git diff --staged --quiet` passes, and retries `git pull --rebase` + `git push` up to 5 times on non-fast-forward rejection.
  3. A final `Verify check outcome` step exits non-zero if the captured check exit code was non-zero.
- **Alternatives Considered:** Duplicating git push bash blocks across four workflows, or using third-party GitHub Marketplace badge actions (`schneegans/dynamic-badges-action`, Gist tokens).
- **Rationale:** A local composite action avoids external PAT secrets (`GITHUB_TOKEN` suffices on public repos with `contents: write`), eliminates shell duplication, guarantees badge updates even on failed checks, and keeps GitHub Actions' red/green run status accurate. Branch ref gating prevents dry-run dispatches on `development` from overwriting `main`'s README badges unless `publish_badges: true` is intentionally checked to test on `development`.

### 3. Sibling Workspace Directory Checkout for `laravel-rubberstamp`
- **Choice:** Workflows requiring Composer (`tests.yml`, `pint.yml`, `security.yml`) check out `eldinbiz/eldin-starter-kit` into `path: starter-kit` and `eldinbiz/laravel-rubberstamp` into `path: laravel-rubberstamp`, with `defaults.run.working-directory: starter-kit`. `versions.yml` checks out only the starter kit repo (`path: starter-kit`) because it reads `composer.lock` directly without `composer install`.
- **Alternatives Considered:** Cloning `laravel-rubberstamp` via raw `git clone` step inside bash.
- **Rationale:** `actions/checkout` cleanly isolates both checkouts as siblings inside `${{ github.workspace }}`, resolves `../laravel-rubberstamp` identically to local developer setups and `.agents/environment.json`, and leverages standard GitHub checkout caching/cleanup.

### 4. PHP 8.4 Runtime Standardization Across All Workflows
- **Choice:** Use `shivammathur/setup-php@v2` pinned to `php-version: '8.4'` (no PHP 8.3 matrix).
- **Rationale:** `composer.lock` already locks packages requiring `php >= 8.4.1` (`symfony/console`), making PHP 8.3 non-installable/unsupported against locked dependencies and aligned with `Dockerfile`, `.agents/environment.json`, and `php84.md`.

### 5. Deterministic Test Count Extraction (`tests.yml`)
- **Choice:** Install Composer dependencies (`composer install --no-interaction --no-progress`), copy `.env.example` to `.env`, run `php artisan key:generate --force`, install/build frontend assets (`npm ci --ignore-scripts --no-audit --no-fund && npm run build`), clear caches (`php artisan config:clear && php artisan view:clear`), and run `php artisan test --log-junit /tmp/junit.xml`. Parse `/tmp/junit.xml` via PHP `SimpleXMLElement` to extract `tests`, `failures`, `errors`, and `skipped`, computing `passed = tests - failures - errors - skipped`.
- **Fallback:** If `/tmp/junit.xml` is absent or `tests == 0`, publish `"passing"` (`#22c55e`) on exit `0` or `"failing"` (`#ef4444`, `isError: true`) on non-zero exit.
- **Rationale:** JUnit XML output avoids fragile regex parsing over ANSI-colored Pest terminal output. Note `phpunit.xml` already configures `DB_CONNECTION=sqlite` (`:memory:`), `Unit`, `Feature`, and `Modules` suites while excluding `tests/Browser/`.

### 6. Full-Scope Security Audit Aggregation (`security.yml`)
- **Choice:** Run `composer audit --locked --abandoned=report --format=json` (checking all `packages` + `packages-dev` in `composer.lock`) and `npm audit --json` (checking all `dependencies` + `devDependencies` in `package-lock.json`).
- **Details & Edge Cases:**
  - Passing `--abandoned=report` prevents Composer 2.7+ abandoned-package exit codes from being misclassified as CVE advisories while still logging abandoned packages in workflow output.
  - Explicitly validate that `composer audit` returned valid JSON containing `.advisories` and `npm audit` returned valid JSON without a top-level `.error` object. If either tool fails to run or errors on network/registry access, publish `security.json` with `message: "audit error"`, `color: "#ef4444"`, `isError: true`, and fail the job.
  - Combine counts from `.advisories` in Composer (supporting both `[]` empty list and `{ pkg: [...] }` object maps) and `.metadata.vulnerabilities.{low,moderate,high,critical}` in NPM.
  - Badge mapping:
    - `0` total issues -> `"0 issues"`, `#22c55e`, `isError: false`
    - `> 0` with max severity `low` or `moderate`/`medium` -> `"1 issue"` / `"<N> issues"`, `#eab308`, `isError: true`
    - `> 0` with any `high` or `critical` severity -> `"1 issue"` / `"<N> issues"`, `#ef4444`, `isError: true`
  - Any `total > 0` or audit error exits the workflow non-zero after publishing `security.json`.

### 7. Version Extraction & Shields Endpoint URLs (`versions.yml` & `README.md`)
- **Choice:** `versions.yml` runs PHP 8.4 (`PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION` -> `"8.4"`) and parses `composer.lock` `packages[]` for exact locked versions of `laravel/framework` (e.g., `"v13.19.0"`) and `livewire/livewire` (e.g., `"v4.4.2"`), failing if either package is missing or empty, and publishes `laravel.json`, `php.json`, and `livewire.json` in one commit.
- **`README.md` Endpoint URLs:** Update lines 3–10 to keep the exact visual order, `flat-square` style, logos (`laravel`, `php`, `pest`, `livewire`, `shield`), and `logoColor=white` with `cacheSeconds=300`:
  - `https://img.shields.io/endpoint?url=https://raw.githubusercontent.com/eldinbiz/eldin-starter-kit/badges/laravel.json&style=flat-square&logo=laravel&logoColor=white&cacheSeconds=300`
  - `https://img.shields.io/endpoint?url=https://raw.githubusercontent.com/eldinbiz/eldin-starter-kit/badges/php.json&style=flat-square&logo=php&logoColor=white&cacheSeconds=300`
  - `https://img.shields.io/endpoint?url=https://raw.githubusercontent.com/eldinbiz/eldin-starter-kit/badges/tests.json&style=flat-square&logo=pest&logoColor=white&cacheSeconds=300`
  - `https://img.shields.io/endpoint?url=https://raw.githubusercontent.com/eldinbiz/eldin-starter-kit/badges/pint.json&style=flat-square&logo=laravel&logoColor=white&cacheSeconds=300`
  - `https://img.shields.io/endpoint?url=https://raw.githubusercontent.com/eldinbiz/eldin-starter-kit/badges/livewire.json&style=flat-square&logo=livewire&logoColor=white&cacheSeconds=300`
  - `https://img.shields.io/endpoint?url=https://raw.githubusercontent.com/eldinbiz/eldin-starter-kit/badges/security.json&style=flat-square&logo=shield&logoColor=white&cacheSeconds=300`

## Risks / Trade-offs

- **[Risk] Uninitialized `badges` branch immediately after merge to `main`** -> Until all four workflows are manually executed once, Shields endpoints pointing at `raw.githubusercontent.com/.../badges/*.json` will display `invalid`/`inaccessible`. **Mitigation:** Include explicit bootstrap verification steps in the rollout plan (run each `workflow_dispatch` on `development` with `publish_badges=true` or immediately upon merging to `main`).
- **[Risk] Shields.io & GitHub Camo caching delay (up to 5 minutes)** -> After a workflow updates the `badges` branch, `README.md` may continue displaying cached SVG output for up to `cacheSeconds=300` (5 minutes). **Mitigation:** Document the 300s floor imposed by Shields.io endpoint badges and verify raw JSON files directly at `raw.githubusercontent.com` during verification.
- **[Risk] Repository Actions `GITHUB_TOKEN` default permission setting** -> If the GitHub repository/organization restricts Actions `Workflow permissions` strictly to read-only even with job-level `permissions: contents: write`, pushing to `badges` will fail with 403. **Mitigation:** Explicitly declare minimal `permissions: contents: write` on each workflow/job and verify Actions token settings allow write permissions for repository contents.
- **[Risk] External advisory API rate limits or transient outage during `security.yml`** -> Packagist or NPM advisory endpoints could temporarily fail during manual dispatch. **Mitigation:** Explicitly detect malformed/error responses, publish `audit error` instead of falsely claiming `0 issues`, and retry or re-trigger cleanly via `workflow_dispatch`.

## Migration Plan

1. Implement `.github/actions/publish-badge/action.yml`, `.github/workflows/{tests,pint,security,versions}.yml`, and [README.md](README.md) badge updates on `development`.
2. Push `development` and optionally run each workflow via `workflow_dispatch` with `publish_badges: true` (or promote `development` -> `staging` -> `main` per [git_branching.md](.agents/rules/git_branching.md)).
3. Once workflows are present on `main`, trigger `tests.yml`, `pint.yml`, `security.yml`, and `versions.yml` from the GitHub Actions UI to populate the orphan `badges` branch (`laravel.json`, `php.json`, `tests.json`, `pint.json`, `livewire.json`, `security.json`) and verify all six dynamic badges render cleanly in [README.md](README.md).
4. **Rollback:** Restore the static `img.shields.io/badge/...` URLs in [README.md](README.md) and delete `.github/workflows/` + `.github/actions/` (and optionally delete the `badges` branch on origin).
