# Proposal: Workflow Template Trash Filter UI and Restoration Fix

## 1. Problem Statement
In `Workflow Templates` (`/admin/approval-workflows`), administrators encounter two related issues when attempting to view and recover soft-deleted workflow templates:

1. **Missing Trash Filter UI Regression**:
   The workflow templates index table is powered by a Livewire v4 Single-File Component (`resources/views/components/admin/approvals/workflow-template-table.blade.php`). While the component's underlying Eloquent query supports `$this->filterValues['trashed']` and row-level restore/permanent-delete actions exist in the table body, the slide-down filter drawer (`filterPanelOpen`) completely omits the "Trash Filter" control (`Show deleted templates only`). Active filter chips also lack mapping for the `'trashed'` filter state. As a result, administrators cannot navigate to or view soft-deleted workflow templates through normal UI interaction.

2. **Laravel Exception on Restore Execution**:
   When an administrator attempts to restore a trashed workflow template:
   - **Controller Parameter & Route Model Binding Mismatch**: The HTTP route in `routes/web.php` defines `Route::post('approval-workflows/{workflow}/restore')` with route parameter `{workflow}`, but `ApprovalWorkflowController::restore(string $id, ...)` declares method parameter `$id`. Because Laravel's `ControllerDispatcher` fails to match the required parameter name, it throws a fatal `BindingResolutionException: Unresolvable dependency resolving [Parameter #0 [ <required> string $id ]]`. Furthermore, the route lacks `->withTrashed()`, causing standard route model binding to 404 on soft-deleted records. The identical mismatch exists on `softDelete(string $id, ...)`.
   - **Livewire Authorization Abort(403)**: In `WorkflowTemplateTable`, the `authorizeTemplate()` method enforces `$template->company_id !== $company->id` strictly, causing an `abort(403)` when attempting to restore templates scoped with `global` (where `company_id` is `null`) or created within parent/ancestor scopes.
   - **State Reactivation Inconsistency**: In `WorkflowTemplateTable::restore()`, `$template->restore()` is called directly without invoking `ToggleWorkflowTemplateAction::enable()`, leaving the restored template in a disabled state (`is_active = false`), which remains hidden if active-status filtering is applied.

3. **Unhandled Exception When Trashing Active Templates**:
   When an administrator clicks the "Trash" action on an active workflow template in the table, confirming the dialog triggers `$wire.trash(targetId)`. In `WorkflowTemplateTable`, the method directly delegates to `TrashWorkflowTemplateAction::execute()`, which calls `abort(400, 'Only disabled templates can be deleted.')`. Because Livewire does not trap `abort(400)` as user validation, it surfaces as an unhandled HTTP 400 Bad Request exception modal rather than a clean alert or notification explaining that the template must be disabled first.

## 2. Proposed Solution

1. **Add Trash Filter Checkbox to the Filter Drawer**:
   - In `resources/views/components/admin/approvals/workflow-template-table.blade.php`, add a "Trash Filter" toggle section inside the slide-down filter drawer (`filterPanelOpen`), styled with standard Tailwind and Dark Mode classes and bound to `wire:model.live="filterValues.trashed"`, mirroring `role-table.blade.php`.
   - Add active filter chips support for the trashed state to allow visual confirmation and one-click removal.

2. **Query Handling & Status Isolation**:
   - In `WorkflowTemplateTable::applyFilters()`, ensure that when `filterValues['trashed']` is active, the `is_active` status filter is bypassed so disabled soft-deleted templates are not filtered out by default active filters.
   - Ensure `query()` permits tenant administrators to view trashed templates belonging to their company and sysadmins to view all trashed templates.

3. **Controller Route & Model Binding Alignment**:
   - In `routes/web.php`, append `->withTrashed()` to `approval-workflows/{workflow}/restore` and `approval-workflows/{workflow}/soft-delete`.
   - In `ApprovalWorkflowController`, update `restore` and `softDelete` signatures to accept `WorkflowTemplate $workflow` matching the `{workflow}` route parameter, allowing Laravel's model binding to seamlessly resolve soft-deleted records.
   - Call `ToggleWorkflowTemplateAction::enable($workflow)` on restore so that the template is un-trashed and its `is_active` flag is set to `true` simultaneously.

4. **Livewire Table Action Restoration & Authorization Alignment**:
   - In `WorkflowTemplateTable::restore()`, delegate restoration to `ToggleWorkflowTemplateAction::enable($template)` to guarantee state consistency.
   - In `WorkflowTemplateTable::authorizeTemplate()`, permit platform administrators or authorized company admins managing company or global templates in accordance with `canManageWorkflow` rules.

5. **Graceful UI Notification & Informational Modal on Trashing Active Templates**:
   - Preserve existing domain logic in `TrashWorkflowTemplateAction`: templates must be disabled before deletion.
   - In `WorkflowTemplateTable::trash()`, verify whether the template is active. If `$template->is_active` is true, halt deletion gracefully and dispatch a notification toast: `Only disabled templates can be moved to trash. Please disable this template first.` with type `error`.
   - In `workflow-template-table.blade.php`, when clicking "Trash" on an active template, render the modal as an informational notice: display title "Template is Currently Active", explain that the workflow must be disabled first, and show a single "Close" button while hiding the "Confirm" button to avoid confusing UX.
   - In `ApprovalWorkflowController::trash()`, redirect back with a flash error message if `$workflow->is_active` is true, preventing raw HTTP 400 exceptions during controller form submissions.

5. **Automated Test Coverage**:
   - Add comprehensive Pest tests in `tests/Feature/ApprovalWorkflowAdminTest.php` and `tests/Feature/Livewire/Admin/Approvals/WorkflowTemplateTableTest.php` covering:
     - Toggling the trash filter in the Livewire table drawer.
     - Viewing soft-deleted templates in the table.
     - Restoring a soft-deleted template via Livewire `wire:click="restore"`.
     - Restoring a soft-deleted template via HTTP POST `/admin/approval-workflows/{workflow}/restore`.
     - Asserting database status (`deleted_at` is null, `is_active` is true) and absence of exceptions.

## 3. Scope & Non-Goals

### In Scope
- Filter drawer markup addition (`Show deleted templates only` checkbox) and active filter chip handling in `WorkflowTemplateTable`.
- Fixing `applyFilters` and `query` in `WorkflowTemplateTable` for trashed items.
- Fixing route parameter `{workflow}` and adding `->withTrashed()` in `routes/web.php`.
- Aligning `ApprovalWorkflowController::restore` and `softDelete` method signatures and actions.
- Delegating to `ToggleWorkflowTemplateAction::enable()` on restore.
- Full automated test suite verification.

### Out of Scope / Non-Goals
- Modifying runtime approval request instances (`ApprovalRequest`).
- Changing database schema or migrations (soft deletes are already in place on `workflow_templates`).
- Modifying the visual canvas editor (canvas operates on active templates).

## 4. Risks & Mitigations
- **Risk**: Restored template remains disabled and hidden from default list view.
  - *Mitigation*: Calling `ToggleWorkflowTemplateAction::enable()` sets `is_active = true` and restores the soft-deleted model in a single atomic operation.
- **Risk**: Tenant data isolation leak when viewing trashed records.
  - *Mitigation*: Retain strict company scoping in `query()` and `canManageWorkflow()` authorization checks so tenant administrators cannot view or restore templates of other companies.
