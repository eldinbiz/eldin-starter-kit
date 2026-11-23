# Spec Delta

## Purpose

Defines orphan-branch JSON badge publishing, concurrency handling, Shields.io endpoint schemas, severity color mapping, and dynamic badge rendering in README.md.

## ADDED Requirements

### Requirement: Orphan Badges Branch Publishing and Ref Gating
Workflows MUST publish badge JSON payloads to an isolated orphan `badges` branch using least-privilege `contents: write` permissions, updating the branch only when executed on `refs/heads/main` or when `publish_badges` is explicitly enabled.

#### Scenario: Publishing on main creates or updates orphan badges branch
- **GIVEN** a workflow completes on `refs/heads/main`
- **WHEN** the badge publishing step executes
- **THEN** it checks out or initializes the orphan `badges` branch containing only badge JSON artifacts
- **AND** commits no changes to `main`, `staging`, or `development`.

#### Scenario: Non-main branch run without publish override skips branch push
- **GIVEN** a workflow is manually dispatched on `development` with `publish_badges` set to `false`
- **WHEN** the check finishes
- **THEN** the workflow skips writing or pushing to the `badges` branch.

#### Scenario: Unchanged badge JSON skips redundant commit
- **GIVEN** the newly generated badge JSON matches the file already committed on `badges`
- **WHEN** the publishing step stages the file
- **THEN** it detects no diff and skips committing and pushing.

### Requirement: Concurrency-Safe Badge Branch Synchronization
Publishing to the `badges` branch MUST handle concurrent updates from multiple independent workflows via pull-rebase retries so no workflow overwrites another badge JSON file.

#### Scenario: Simultaneous workflow completions preserve all badge files
- **GIVEN** `tests.yml` and `pint.yml` complete near-simultaneously on `main`
- **WHEN** the second workflow encounters a non-fast-forward push rejection on `badges`
- **THEN** it fetches the latest `badges` commit, rebases its own badge JSON file commit on top, and retries the push
- **AND** both `tests.json` and `pint.json` persist on `badges`.

### Requirement: Verification Badge Payload Schemas and Color Semantics
The `tests.json`, `pint.json`, and `security.json` files MUST follow Shields.io Endpoint v1 (`schemaVersion: 1`) with deterministic message text, status colors, and `isError` flags reflecting check outcomes even when the workflow run fails.

#### Scenario: Tests badge reflects passing and failing counts
- **GIVEN** the tests workflow finishes with `N` passed tests and zero failures
- **WHEN** `tests.json` is generated
- **THEN** `label` is `"Tests"`, `message` is `"<N> passed"`, `color` is `"#22c55e"`, and `isError` is `false`
- **AND** when any test fails, `message` reports `"<F> failed"` (or `"failing"` if unavailable) with `color: "#ef4444"` and `isError: true`.

#### Scenario: Pint badge reflects formatting status
- **GIVEN** `pint --test` exits `0`
- **WHEN** `pint.json` is generated
- **THEN** `label` is `"Pint"`, `message` is `"passing"`, `color` is `"#22c55e"`, and `isError` is `false`
- **AND** when `pint --test` exits non-zero, `message` is `"failing"`, `color` is `"#ef4444"`, and `isError` is `true`.

#### Scenario: Security badge reflects aggregate audit severity tiers
- **GIVEN** the security workflow parses `composer audit` and `npm audit` JSON outputs
- **WHEN** total advisories equal `0` without audit errors
- **THEN** `security.json` sets `message: "0 issues"`, `color: "#22c55e"`, and `isError: false`
- **AND** when advisories exist with highest severity `low` or `moderate`/`medium`, `message` is `"1 issue"` (or `"<N> issues"` when `N > 1`) with `color: "#eab308"` and `isError: true`
- **AND** when any advisory is `high` or `critical`, `message` is `"1 issue"` (or `"<N> issues"` when `N > 1`) with `color: "#ef4444"` and `isError: true`
- **AND** when either audit tool fails to produce valid audit data, `message` is `"audit error"` with `color: "#ef4444"` and `isError: true`.

### Requirement: Stack Version Badge Payload Schemas
The versions workflow MUST update `laravel.json`, `php.json`, and `livewire.json` using Shields.io Endpoint v1 with exact extracted versions and the starter kit's brand accent colors.

#### Scenario: Stack version JSON files reflect exact versions
- **GIVEN** PHP runtime is `8.4`, locked `laravel/framework` is `v13.19.0`, and locked `livewire/livewire` is `v4.4.2`
- **WHEN** the versions workflow publishes badge payloads
- **THEN** `laravel.json` outputs `label: "Laravel"`, `message: "v13.19.0"`, `color: "#FF2D20"`
- **AND** `php.json` outputs `label: "PHP"`, `message: "8.4"`, `color: "#777BB4"`
- **AND** `livewire.json` outputs `label: "Livewire"`, `message: "v4.4.2"`, `color: "#FB70A9"`.

### Requirement: Dynamic README Badge Endpoint URLs
`README.md` MUST render all six header badges via `https://img.shields.io/endpoint` pointing to `raw.githubusercontent.com/eldinbiz/eldin-starter-kit/badges/<name>.json` while preserving existing badge ordering, `flat-square` style, logos, and accessible alt text.

#### Scenario: README header embeds six dynamic endpoint badges
- **GIVEN** a viewer loads `README.md` on GitHub
- **WHEN** the markdown renders the header badge block
- **THEN** each of `Laravel`, `PHP`, `Tests`, `Pint`, `Livewire`, and `Security` renders an `<img>` whose `src` uses `https://img.shields.io/endpoint?url=https://raw.githubusercontent.com/eldinbiz/eldin-starter-kit/badges/<file>.json`
- **AND** keeps `style=flat-square`, matching `logo`, `logoColor=white`, and `cacheSeconds=300` query parameters.
