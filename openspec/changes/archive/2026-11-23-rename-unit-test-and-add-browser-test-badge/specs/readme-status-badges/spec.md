# Spec Delta

## Purpose

Renames the `tests.json` badge label to `"Unit Test"`, defines the `browser-tests.json` (`"Browser Test"`) Shields.io Endpoint v1 schema, and integrates the seventh `Browser Test` badge into `README.md`.

## MODIFIED Requirements

### Requirement: Verification Badge Payload Schemas and Color Semantics
The `tests.json`, `browser-tests.json`, `pint.json`, and `security.json` files MUST follow Shields.io Endpoint v1 (`schemaVersion: 1`) with deterministic message text, status colors, and `isError` flags reflecting check outcomes even when the workflow run fails.

#### Scenario: Unit Test badge reflects passing and failing counts
- **GIVEN** the `tests.yml` workflow finishes executing the `Unit`, `Feature`, and `Modules` suites with `N` passed tests and zero failures
- **WHEN** `tests.json` is generated
- **THEN** `label` is `"Unit Test"`, `message` is `"<N> passed"` (or `"passing"` if `N == 0`), `color` is `"#22c55e"`, and `isError` is `false`
- **AND** when any test fails or errors, `message` reports `"<F> failed"` (or `"failing"` if unavailable) with `color: "#ef4444"` and `isError: true`.

#### Scenario: Browser Test badge reflects passing and failing counts
- **GIVEN** the `browser-tests.yml` workflow finishes executing `tests/Browser/` with `N` passed tests and zero failures
- **WHEN** `browser-tests.json` is generated
- **THEN** `label` is `"Browser Test"`, `message` is `"<N> passed"` (or `"passing"` if `N == 0`), `color` is `"#22c55e"`, and `isError` is `false`
- **AND** when any browser test fails or errors, `message` reports `"<F> failed"` (or `"failing"` if unavailable) with `color: "#ef4444"` and `isError: true`.

#### Scenario: Pint badge reflects formatting status
- **GIVEN** `pint --test` exits `0`
- **WHEN** `pint.json` is generated
- **THEN** `label` is `"Pint"`, `message` is `"passing"`, `color` is `"#22c55e"`, and `isError` is `false`
- **AND** when `pint --test` exits non-zero, `message` is `"failing"`, `color` is `"#ef4444"`, and `isError: true`.

#### Scenario: Security badge reflects aggregate audit severity tiers
- **GIVEN** the security workflow parses `composer audit` and `npm audit` JSON outputs
- **WHEN** total advisories equal `0` without audit errors
- **THEN** `security.json` sets `message: "0 issues"`, `color: "#22c55e"`, and `isError: false`
- **AND** when advisories exist with highest severity `low` or `moderate`/`medium`, `message` is `"1 issue"` (or `"<N> issues"` when `N > 1`) with `color: "#eab308"` and `isError: true`
- **AND** when any advisory is `high` or `critical`, `message` is `"1 issue"` (or `"<N> issues"` when `N > 1`) with `color: "#ef4444"` and `isError: true`
- **AND** when either audit tool fails to produce valid audit data, `message` is `"audit error"` with `color: "#ef4444"` and `isError: true`.

### Requirement: Dynamic README Badge Endpoint URLs
`README.md` MUST render all seven header badges via `https://img.shields.io/endpoint` pointing to `raw.githubusercontent.com/eldinbiz/eldin-starter-kit/badges/<name>.json` while preserving `flat-square` style, logos, and accessible alt text.

#### Scenario: README header embeds seven dynamic endpoint badges
- **GIVEN** a viewer loads `README.md` on GitHub
- **WHEN** the markdown renders the header badge block
- **THEN** `Laravel` (`laravel.json`), `PHP` (`php.json`), `Unit Test` (`tests.json`, `alt="Unit Test Status"`, `logo=pest`), `Browser Test` (`browser-tests.json`, `alt="Browser Test Status"`, `logo=googlechrome`), `Pint` (`pint.json`), `Livewire` (`livewire.json`), and `Security` (`security.json`) each render an `<img>` whose `src` uses `https://img.shields.io/endpoint?url=https://raw.githubusercontent.com/eldinbiz/eldin-starter-kit/badges/<file>.json`
- **AND** keeps `style=flat-square`, `logoColor=white`, and `cacheSeconds=300` query parameters.
