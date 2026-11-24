# Tasks

## 1. Playwright Dependency Alignment

- [x] 1.1 Update `package.json` (`dependencies.playwright`) from `^1.62.1` to `^1.63.0` and refresh `package-lock.json` via `npm install playwright@^1.63.0 --ignore-scripts`; verify `package-lock.json` locks `playwright` and `playwright-core` at `>= 1.63.0` matching `pestphp/pest-plugin-browser` (`PlaywrightNpmServer::PLAYWRIGHT_VERSION`).

## 2. Unit Test Badge Relabeling

- [x] 2.1 Update `.github/workflows/tests.yml` to change `"label" => "Tests"` in the `tests.json` payload generator to `"label" => "Unit Test"` (keeping `tests.json` filename, `workflow_dispatch` + `publish_badges` trigger pattern, and `php artisan test --log-junit /tmp/junit.xml` test scope unchanged), and update step/commit description strings accordingly.

## 3. Browser Tests GitHub Workflow

- [x] 3.1 Create `.github/workflows/browser-tests.yml` (`name: Browser Tests`) triggered solely via `workflow_dispatch` with `publish_badges` (boolean, default `false`) and `runner` (`choice`: `['rubberstamp', 'pest']`, default `'rubberstamp'`), `permissions: contents: write`, and `defaults.run.working-directory: starter-kit`.
- [x] 3.2 Configure job steps in `.github/workflows/browser-tests.yml`: check out `starter-kit` and sibling `eldinbiz/laravel-rubberstamp` into `laravel-rubberstamp`, set up PHP 8.4 with `mbstring, xml, dom, curl, openssl, zip, gd, intl, bcmath, pdo, pdo_sqlite, sockets`, set up Node.js 24, install Composer dependencies, copy `.env.example` to `.env`, generate `APP_KEY`, run `npm ci --ignore-scripts --no-audit --no-fund && npm run build`, clear config/view caches, and install Chromium + Linux system dependencies (`npx playwright install chromium --with-deps`).
- [x] 3.3 Add the browser test execution step in `.github/workflows/browser-tests.yml` using `set +e` to capture `exit_code`: run `php artisan rubberstamp:browser tests/Browser --no-doc --skip-orphan-check --log-junit=/tmp/browser-junit.xml` when `inputs.runner == 'rubberstamp'`, or `php artisan test tests/Browser --log-junit=/tmp/browser-junit.xml` when `inputs.runner == 'pest'`.
- [x] 3.4 Add the badge generation, `publish-badge` invocation (`browser-tests.json` with `"label" => "Browser Test"`, `#22c55e` / `#ef4444`, `isError`, gated on `github.ref == 'refs/heads/main' || inputs.publish_badges == true`, `commit_message: 'chore(badges): update browser tests badge'`), and final `exit_code` verification step in `.github/workflows/browser-tests.yml`.

## 4. README Badge Updates

- [x] 4.1 Update `README.md` lines 3–10 to change `alt="Tests Status"` on the `badges/tests.json` `<img>` tag to `alt="Unit Test Status"`, and insert `<img src="https://img.shields.io/endpoint?url=https://raw.githubusercontent.com/eldinbiz/eldin-starter-kit/badges/browser-tests.json&style=flat-square&logo=googlechrome&logoColor=white&cacheSeconds=300" alt="Browser Test Status">` immediately after it.

## 5. Validation & Rollout Verification

- [x] 5.1 Validate YAML syntax for `.github/workflows/tests.yml` and `.github/workflows/browser-tests.yml`, verify `php artisan test` passes locally, and verify both `php artisan test tests/Browser --log-junit=/tmp/browser-junit.xml` and `php artisan rubberstamp:browser tests/Browser --no-doc --skip-orphan-check --log-junit=/tmp/browser-junit.xml` produce valid JUnit XML when Chromium is installed.
- [ ] 5.2 After pushing to GitHub, manually dispatch `tests.yml` and `browser-tests.yml` (verifying both `runner=rubberstamp` and `runner=pest`, with `publish_badges=true` on `development` or after promoting to `main`), confirm `raw.githubusercontent.com/eldinbiz/eldin-starter-kit/badges/tests.json` has `"label": "Unit Test"` and `browser-tests.json` has `"label": "Browser Test"`, and confirm all seven badges render cleanly in `README.md`.
