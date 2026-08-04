# Approval Module Test Suite — Implementation Plan

## Prerequisites

Before any test file can be written, these artifacts must exist:
1. 5 factories for the approval models
2. A test-only `FakeApprovable` model (Eloquent model implementing `Approvable`)

---

## Phase 1 — Create Factories

Run `php artisan make:model --factory` equivalents or write the factory classes directly.

### 1.1 `database/factories/Approval/WorkflowTemplateFactory.php`

Default state:
- `company_id` → `Company::factory()`
- `name` → `fake()->unique()->words(3, true)`
- `description` → `fake()->optional()->sentence()`
- `is_active` → `true`
- `scope` → `TemplateScope::ThisCompany`
- `approvable_type` → `null` (applies to any module)

States:
- `inactive()` → `['is_active' => false]`
- `scopeGlobal()` → `['scope' => TemplateScope::Global]`
- `scopeSubsidiaries()` → `['scope' => TemplateScope::ThisCompanyAndSubsidiaries]`

### 1.2 `database/factories/Approval/WorkflowGroupFactory.php`

Default state:
- `template_id` → `WorkflowTemplate::factory()`
- `name` → `fake()->unique()->words(2, true)`
- `order` → auto-increment via state callback or pass manually
- `type` → `GroupType::Sequential`

States:
- `sequential()` → `['type' => GroupType::Sequential]`
- `parallel()` → `['type' => GroupType::Parallel]`

Has-many macro: `hasSteps(int $count, array|callable $attributes = [])`

### 1.3 `database/factories/Approval/WorkflowStepFactory.php`

Default state:
- `group_id` → `WorkflowGroup::factory()`
- `label` → `fake()->words(2, true)`
- `order` → auto-increment or pass manually
- `resolver_type` → `ResolverType::NPlus1`
- `resolver_payload` → `[]`

States:
- `nPlus1()` → `['resolver_type' => ResolverType::NPlus1, 'resolver_payload' => []]`
- `specificUser(int $userId)` → `['resolver_type' => ResolverType::SpecificUser, 'resolver_payload' => ['user_id' => $userId]]`
- `nPlus1OfPrevious()` → `['resolver_type' => ResolverType::NPlus1OfPrevious, 'resolver_payload' => []]`

### 1.4 `database/factories/Approval/ApprovalRequestFactory.php`

Default state:
- `company_id` → `Company::factory()`
- `requestor_id` → `User::factory()`
- `workflow_template_id` → `WorkflowTemplate::factory()`
- `workflow_snapshot` → `null` (populated by ApprovalEngine, not factory)
- `status` → `ApprovalStatus::Pending`
- `submitted_at` → `now()`

States:
- `approved()` → `['status' => ApprovalStatus::Approved, 'completed_at' => now()]`
- `rejected()` → `['status' => ApprovalStatus::Rejected, 'completed_at' => now()]`
- `canceled()` → `['status' => ApprovalStatus::Canceled, 'canceled_at' => now()]`

### 1.5 `database/factories/Approval/ApprovalRequestStepFactory.php`

Default state:
- `approval_request_id` → `ApprovalRequest::factory()`
- `group_name` → `'Default Group'`
- `step_label` → `fake()->words(2, true)`
- `order` → auto-increment
- `resolver_type` → `ResolverType::NPlus1`
- `resolver_payload` → `[]`
- `approver_id` → `User::factory()`
- `state` → `StepState::Pending`
- `notes` → `null`
- `action_at` → `null`

States:
- `pending()` → `['state' => StepState::Pending]`
- `approved()` → `['state' => StepState::Approved, 'action_at' => now()]`
- `rejected()` → `['state' => StepState::Rejected, 'action_at' => now(), 'notes' => 'Rejected']`
- `skipped()` → `['state' => StepState::Skipped]`
- `awaitingPrevious()` → `['state' => StepState::AwaitingPrevious, 'approver_id' => null]`

---

## Phase 2 — Create FakeApprovable Test Support Model

### 2.1 `tests/Support/FakeApprovable.php`

An Eloquent model that implements `Approvable`. Uses a temporary table created in `setUp()` via `Schema::create()`.

```
tests/Support/
  FakeApprovable.php
```

Key design:
- Stores `$requester` and `$company` as PHP properties (not DB columns)
- The DB table only needs `id` (auto-increment)
- Table created via `Schema::create('fake_approvables', ...)` in the test's `beforeEach()` or a static helper
- All 9 `Approvable` methods delegate to the stored properties
- Constructor accepts `User $requester` and `Company $company`
- `$this->exists = true` set after `save()` so Eloquent treats it as persisted

Make sure the table creation happens before `RefreshDatabase` migrations — use `beforeEach()` in the test file that needs it, or register the table in a dedicated test helper trait.

---

## Phase 3 — Test Files

### 3.1 `tests/Unit/ApprovalEngineTest.php`

**Setup:** Create a test helper function `makeFakeApprovable(User $requester, Company $company): FakeApprovable` that creates + persists the model.

**Test cases:**

#### Group: submit()
1. `it('creates an approval request with pending status and submitted_at')`
2. `it('assigns approver via N+1 resolver — uses requestor manager')` — create user with manager via `company_user.manager_id` pivot
3. `it('assigns approver via specific_user resolver')` — step with resolver_payload `{user_id: X}`
4. `it('creates step in AwaitingPrevious state for N+1_of_previous resolver')`
5. `it('throws RuntimeException when N+1 resolver finds no manager')`
6. `it('throws RuntimeException when specific_user resolver has no user_id in payload')`
7. `it('throws RuntimeException when specific_user does not belong to company')` — user not attached to company
8. `it('builds correct workflow_snapshot on the request')` — snapshot matches template groups/steps structure
9. `it('dispatches ApprovalRequested event')` — `Event::fake()`
10. `it('runs inside a database transaction')` — verify rollback on failure

#### Group: approve()
11. `it('marks step as Approved and records action_at and notes')`
12. `it('dispatches ApprovalStepCompleted event')`
13. `it('advances state machine after approval — final step completes request')`
14. `it('throws RuntimeException when step is not pending (already approved)')`
15. `it('throws RuntimeException when step is not pending (already rejected)')`
16. `it('throws RuntimeException when parent request is not pending (rejected)')`
17. `it('throws RuntimeException when parent request is not pending (canceled)')`

#### Group: reject()
18. `it('marks step as Rejected with reason in notes and sets action_at')`
19. `it('sets request status to Rejected with completed_at')`
20. `it('marks remaining Pending steps as Skipped')`
21. `it('marks remaining AwaitingPrevious steps as Skipped')`
22. `it('does NOT modify already Approved steps in the same group')`
23. `it('dispatches ApprovalRejected event')`

#### Group: cancel()
24. `it('cancels pending request and records canceled_at')`
25. `it('dispatches ApprovalCanceled event')`
26. `it('throws RuntimeException when request is already approved')`
27. `it('throws RuntimeException when request is already rejected')`

#### Group: delegate()
28. `it('reassigns approver_id and records delegated_from_id')`
29. `it('dispatches ApprovalDelegated event')`
30. `it('throws RuntimeException when step is not pending')`
31. `it('throws RuntimeException when parent request is not pending')`

#### Group: resolvePreviousManager()
32. `it('resolves AwaitingPrevious step to Pending with new approver')`
33. `it('records delegated_from_id on resolved step')`
34. `it('dispatches ApprovalDelegated event')`
35. `it('throws RuntimeException when step is not AwaitingPrevious')`

#### Group: advanceStateMachine (state machine)
36. `it('completes request when last step in single sequential group is approved')`
37. `it('completes request only when ALL steps in parallel group are approved')`
38. `it('advances to next group when current sequential group is fully approved')`
39. `it('resolves N+1_of_previous step when its predecessor in sequential group is approved')`
40. `it('auto-approves N+1_of_previous step when previous approver has no manager')`
41. `it('stops advancement when any step in group is rejected')`
42. `it('dispatches ApprovalCompleted when final group completes')`

---

### 3.2 `tests/Feature/ApprovalUserRoutesTest.php`

**Setup:** Helper function `createTestSetup()` that creates company, admin user, regular user, manager user, workflow template with steps, submits an approval request, and returns all entities.

**Test cases:**

#### Group: inbox
1. `it('shows pending steps where authenticated user is the approver')`
2. `it('shows empty state when user has no pending steps')`
3. `it('requires authentication — redirects to login when guest')`

#### Group: history
4. `it('shows completed, rejected, and canceled requests where user was approver')`
5. `it('shows requests where user was delegate')`

#### Group: requests (my requests)
6. `it('shows requests submitted by the authenticated user')`

#### Group: show
7. `it('displays approval request detail with workflow progress table')`
8. `it('returns 403 when request belongs to a different company')`
9. `it('shows approve and reject buttons when user is the current approver')`
10. `it('shows cancel button when user is the requestor and request is pending')`
11. `it('does NOT show action buttons when request is already finalized')`

#### Group: approve
12. `it('approves step successfully and redirects back with success message')`
13. `it('returns 403 when authenticated user is not the step approver')`
14. `it('redirects back with error when step is already processed')`

#### Group: reject
15. `it('rejects request successfully and redirects back with success message')`
16. `it('returns 403 when authenticated user is not the step approver')`
17. `it('returns validation error when reason is missing')`

#### Group: cancel
18. `it('cancels request successfully and redirects to requests index')`
19. `it('returns 403 when authenticated user is not the requestor')`

#### Group: delegate
20. `it('delegates step successfully and redirects back')`
21. `it('returns 403 when authenticated user is not the step approver')`

#### Group: resolve-previous-manager
22. `it('resolves previous manager step successfully for requestor')`
23. `it('resolves previous manager step successfully for platform admin')`
24. `it('returns 403 for unauthorized users (non-requestor, non-admin)')`
25. `it('returns 403 when step is not AwaitingPrevious')`

---

### 3.3 `tests/Feature/ApprovalWorkflowAdminTest.php`

**Setup:** Helper `createAdminUser()` following existing pattern from `UserManagementTest.php`.

**Test cases:**

#### Group: index
1. `it('shows list of workflow templates')`
2. `it('requires authentication')`
3. `it('returns 403 for non-admin users')`

#### Group: create
4. `it('shows create form with approvable type dropdown')`
5. `it('returns 403 for non-admin users')`

#### Group: store
6. `it('creates template with groups and steps')` — POST with nested group/step data
7. `it('returns validation error when name is missing')`
8. `it('returns validation error when no groups are provided')`
9. `it('returns validation error when group has no steps')`
10. `it('returns 403 for non-admin users')`

#### Group: edit
11. `it('shows edit form pre-populated with template data')`
12. `it('returns 404 for non-existent template')`

#### Group: update
13. `it('updates template name, description, and scope')`
14. `it('replaces groups and steps on update')`
15. `it('returns 403 for non-admin users')`

#### Group: destroy
16. `it('soft-deletes template')`
17. `it('returns 403 for non-admin users')`

#### Group: restore
18. `it('restores soft-deleted template')`

#### Group: trash
19. `it('force-deletes template with all groups and steps')` — cascade verify

---

### 3.4 `tests/Feature/ApprovalEventsTest.php`

**Setup:** Use `Event::fake()` for dispatch assertions, `Event::assertDispatched()`.

**Test cases:**

#### Group: event dispatch
1. `it('dispatches ApprovalRequested when submitting a request')`
2. `it('dispatches ApprovalStepCompleted when approving a step')`
3. `it('dispatches ApprovalCompleted when final group finishes')`
4. `it('dispatches ApprovalRejected when rejecting — passes the rejecting step')`
5. `it('dispatches ApprovalCanceled when canceling — passes request and user')`
6. `it('dispatches ApprovalDelegated when delegating — passes step, from, to')`
7. `it('dispatches ApprovalDelegated when resolving previous manager')`

#### Group: UpdateApprovableStatus listener
8. `it('calls onApprovalCompleted on the approvable model when ApprovalCompleted fires')`
9. `it('calls onApprovalRejected on the approvable model when ApprovalRejected fires')`
10. `it('calls onApprovalCanceled on the approvable model when ApprovalCanceled fires')`
11. `it('does nothing when approvable morph resolves to a non-Approvable model')`
12. `it('implements ShouldHandleEventsAfterCommit — listener fires after DB commit')`

---

## Phase 4 — Post-Implementation Validation

After all tests are written, run:

```
php artisan test --compact
```

Expected: all tests pass. If any pre-existing tests (Login, UserManagement) break, note them but do not modify them.

---

## File Inventory

| File | Action |
|---|---|
| `database/factories/Approval/WorkflowTemplateFactory.php` | Create |
| `database/factories/Approval/WorkflowGroupFactory.php` | Create |
| `database/factories/Approval/WorkflowStepFactory.php` | Create |
| `database/factories/Approval/ApprovalRequestFactory.php` | Create |
| `database/factories/Approval/ApprovalRequestStepFactory.php` | Create |
| `tests/Support/FakeApprovable.php` | Create |
| `tests/Unit/ApprovalEngineTest.php` | Create (~42 tests) |
| `tests/Feature/ApprovalUserRoutesTest.php` | Create (~25 tests) |
| `tests/Feature/ApprovalWorkflowAdminTest.php` | Create (~19 tests) |
| `tests/Feature/ApprovalEventsTest.php` | Create (~12 tests) |

Total: ~98 tests across 4 test files.

---

## Key Design Decisions

1. **FakeApprovable uses `Schema::create()` in `beforeEach()`** — Avoids a permanent migration for test-only artifact. The in-memory SQLite database handles this cleanly.

2. **ApprovalEngine tests go in `tests/Unit/`** — Despite touching the database, they test service logic in isolation (no HTTP layer). They still use `RefreshDatabase` since the engine persists data.

3. **Route tests use CSRF priming** — Following existing convention: `$this->get($url)` before `$this->post()` to obtain a session token.

4. **Manager relationship setup** — The `N+1` resolver relies on `User::getManager(Company)`, which reads `company_user.manager_id`. Tests set this via direct pivot attach: `$company->users()->attach($employeeId, ['manager_id' => $managerId])`.

5. **No browser tests** — All approval tests are HTTP feature tests. Browser tests would duplicate the logic and require a running server.

6. **Skip template validation tests for complex nested form** — The `StoreWorkflowTemplateRequest` validator is complex. Test basic required-field validation only; deep nested validation is lower priority.
