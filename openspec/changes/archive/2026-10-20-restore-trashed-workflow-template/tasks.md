# Tasks: Workflow Template Trash Filter UI and Restoration Fix

## Phase 1: HTTP Routes & Controller Parameter Remediation
- [x] 1.1 Append `->withTrashed()` to `approval-workflows/{workflow}/restore` and `approval-workflows/{workflow}/soft-delete` routes in `routes/web.php`.
- [x] 1.2 Update `ApprovalWorkflowController::restore` signature from `string $id` to `WorkflowTemplate $workflow` and delegate to `ToggleWorkflowTemplateAction::enable($workflow)`.
- [x] 1.3 Update `ApprovalWorkflowController::softDelete` signature from `string $id` to `WorkflowTemplate $workflow` and delegate to `ForceDeleteWorkflowTemplateAction::execute($workflow)`.

## Phase 2: Livewire Filter Drawer & Active Chips UI
- [x] 2.1 Add the "Trash Filter" section with checkbox (`wire:model.live="filterValues.trashed"`) to the slide-down filter drawer in `resources/views/components/admin/approvals/workflow-template-table.blade.php`.
- [x] 2.2 Add mapping for `trashed` in active filter chips to render "Trash Filter: Show deleted only" with a working dismiss button.

## Phase 3: Livewire Query Handling, Authorization, and Action Delegation
- [x] 3.1 Update `applyFilters()` in `workflow-template-table.blade.php` to neutralize the `is_active` status filter when viewing trashed templates.
- [x] 3.2 Update `WorkflowTemplateTable::restore()` to call `ToggleWorkflowTemplateAction::enable($template)` ensuring both un-trashing and reactivation (`is_active = true`).
- [x] 3.3 Update `WorkflowTemplateTable::authorizeTemplate()` to align with `canManageWorkflow` rules.

## Phase 4: Automated Testing & Verification
- [x] 4.1 Add feature tests in `tests/Feature/ApprovalWorkflowAdminTest.php` asserting HTTP POST restore resolves trashed templates, checks company authorization, and re-enables active state.
- [x] 4.2 Create/update Livewire component tests in `tests/Feature/Livewire/Admin/Approvals/WorkflowTemplateTableTest.php` asserting:
  - Toggling `filterValues.trashed` filters rows to only soft-deleted templates.
  - Clicking "Restore" row action calls `ToggleWorkflowTemplateAction::enable` without exceptions.
  - Active filter chip removal resets the trash filter.
- [x] 4.3 Run test suite verification (`composer test`) to ensure all tests pass without regressions.

## Phase 5: Graceful Alert & Notification on Trashing Active Templates
- [x] 5.1 Update `WorkflowTemplateTable::trash()` in `workflow-template-table.blade.php` to detect active templates and dispatch an error notification toast (`Only disabled templates can be moved to trash. Please disable this template first.`) instead of triggering an unhandled exception.
- [x] 5.2 Add `targetIsActive` state and an inline warning alert box inside the confirmation modal in `workflow-template-table.blade.php` alerting the user that the template is active and must be disabled first.
- [x] 5.3 Update `ApprovalWorkflowController::trash()` to check if `$workflow->is_active` and redirect back with an error flash message instead of throwing an unhandled HTTP 400.
- [x] 5.4 Add automated feature tests in `WorkflowTemplateTableTest.php` asserting that trashing an active template dispatches an error notification toast and preserves the template, and in `ApprovalWorkflowAdminTest.php` asserting that HTTP delete redirects with an error flash.
- [x] 5.5 Re-run automated tests via Docker to verify fix.

## Phase 6: Active Template Informational Notice Modal UX Refinement
- [x] 6.1 Update the confirmation modal in `workflow-template-table.blade.php`:
  - Show title "Template is Currently Active" and amber icon when `actionType === 'trash' && targetIsActive`.
  - Hide the "Confirm" button (`x-show="!(actionType === 'trash' && targetIsActive)"`).
  - Render a single primary "Close" button instead of "Cancel", eliminating the confusing dual-button UX.
- [x] 6.2 Update Livewire tests in `WorkflowTemplateTableTest.php` to verify modal markup for active template notice.
- [x] 6.3 Re-run automated tests via Docker to verify fix.
