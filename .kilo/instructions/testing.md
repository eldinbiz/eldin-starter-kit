# Test-Driven Development Rule

This rule strictly governs all feature and module changes, ensuring that every code change is accompanied by appropriate test coverage.

## 1. Mandatory Test Coverage

When the agent **adds, modifies, or refactors** any of the following, it **MUST** create or update corresponding tests:

| Change Type | Test Requirement |
|---|---|
| New route/controller method | Feature test covering the endpoint |
| New or modified validation rules | Test for valid + invalid input |
| New or modified business logic | Unit or feature test verifying behavior |
| New or modified authorization (policies/middleware) | Test for authorized + unauthorized access |
| New model relationship or accessor | Unit test verifying the relationship/accessor |
| Bug fix | Regression test reproducing the bug first |

## 2. Test Location

| Code Changed | Test Goes In |
|---|---|
| `app/Http/Controllers/` | `tests/Feature/` |
| `routes/web.php` or `routes/api.php` | `tests/Feature/` |
| `app/Models/` | `tests/Feature/` or `tests/Unit/` |
| `app/Services/` | `tests/Unit/` |
| `app/Middleware/` | `tests/Feature/` |
| `app/Events/` / `app/Listeners/` | `tests/Feature/` |

## 3. Test Quality Rules

- Use the existing project conventions (Pest, `RefreshDatabase`, factories, session priming for CSRF).
- Run `composer test` after creating/modifying tests to verify they pass.
- Do **NOT** delete or skip existing tests without explicit user approval.

## 4. Reporting Test Results

After creating or modifying tests, report the results:

```
composer test
```

If a test fails for a **pre-existing reason** (e.g., DataTable rendering bug), note it explicitly and explain why it's not caused by the current change.

## 5. Exception

Trivial changes that do not affect behavior (e.g., formatting, PHPDoc updates, type hints that don't change logic) do not require new tests. The agent must note when invoking this exception.
