# Capability: Approval Workflows - Trash Filtering and Template Restoration

## Purpose
Enable administrators to discover, inspect, and reliably restore soft-deleted workflow templates through both the Livewire table UI and HTTP controller endpoints without triggering unresolvable dependency exceptions, permission rejections, or state desynchronization.

---

## Requirements & Behavioral Scenarios

### Requirement: Trashed Workflow Template Filtering in UI
The Workflow Templates index table (`/admin/approval-workflows`) MUST provide an accessible "Trash Filter" control in the slide-down filter drawer, allowing administrators to toggle visibility between active/disabled templates and soft-deleted (trashed) templates.

#### Scenario: Revealing Trashed Templates via Filter Drawer Checkbox
- **GIVEN** an authorized administrator is viewing the Workflow Templates index page (`/admin/approval-workflows`)
- **AND** at least one workflow template is currently soft-deleted (trashed)
- **WHEN** the administrator opens the filter drawer and checks "Show deleted templates only" (`filterValues.trashed = true`)
- **THEN** the Livewire table dynamically refreshes to display only soft-deleted templates (`onlyTrashed()`)
- **AND** each row renders a "Restore" button and a "Delete Permanently" button in the actions column.

#### Scenario: Active Filter Chip Display and Dismissal
- **GIVEN** the administrator has enabled the trash filter
- **WHEN** the table renders
- **THEN** an active filter chip displays "Trash Filter: Show deleted only" (or equivalent label) with a dismiss button
- **AND** clicking the dismiss button unchecks the trash filter and restores the default non-trashed view.

#### Scenario: Status Filter Neutralization when Trashed is Active
- **GIVEN** the filter drawer previously had a status filter selected (e.g. `is_active = 1` or `is_active = 0`)
- **WHEN** the administrator activates the trash filter
- **THEN** the system ignores or bypasses the `is_active` status constraint
- **AND** all trashed templates for the authorized company scope are displayed regardless of their prior `is_active` value.

---

### Requirement: Robust Workflow Template Restoration
The system MUST provide seamless restoration of soft-deleted workflow templates via both the Livewire component action (`restore`) and the HTTP controller endpoint (`ApprovalWorkflowController@restore`) without raising `BindingResolutionException`, 404, or 403 errors for authorized users.

#### Scenario: Restoring a Trashed Template via Livewire Table Action
- **GIVEN** a soft-deleted workflow template is displayed in the table
- **WHEN** the administrator clicks the "Restore" button on the template row
- **THEN** the Livewire component delegates to `ToggleWorkflowTemplateAction::enable($template)`
- **AND** the template is un-trashed (`deleted_at` becomes `null`)
- **AND** the template is marked active (`is_active` becomes `true`)
- **AND** a success notification toast announces "Workflow template restored successfully."
- **AND** the table refreshes without encountering a Laravel exception modal.

#### Scenario: Restoring a Trashed Template via HTTP POST Controller Endpoint
- **GIVEN** a soft-deleted workflow template with ID 10 exists
- **WHEN** an authorized administrator sends an HTTP POST request to `/admin/approval-workflows/10/restore`
- **THEN** Laravel's route model binding resolves the soft-deleted model via `->withTrashed()`
- **AND** the controller delegates to `ToggleWorkflowTemplateAction::enable($workflow)`
- **AND** the user is redirected to `/admin/approval-workflows` with a success flash message.

#### Scenario: Multi-Tenant and Global Template Restoration Authorization
- **GIVEN** a soft-deleted workflow template with scope `global` (`company_id = null`) or belonging to the administrator's active company
- **WHEN** an authorized platform administrator or company administrator initiates a restore action
- **THEN** the authorization check permits the operation in alignment with `canManageWorkflow` rules
- **AND** no spurious `abort(403)` is triggered.

---

### Requirement: Graceful Feedback and Protection for Trashing Active Templates
The system MUST protect active workflow templates by requiring them to be disabled prior to soft-deletion, while communicating this requirement gracefully via toast notifications, modal warnings, and flash messages without raising unhandled `HttpException (400)` error pages.

#### Scenario: Attempting to Trash an Active Template via Livewire Table
- **GIVEN** an active workflow template (`is_active = true`) is displayed in the table
- **WHEN** the administrator clicks the "Trash" button and confirms deletion
- **THEN** the Livewire component prevents deletion
- **AND** dispatches an error notification toast: "Only disabled templates can be moved to trash. Please disable this template first."
- **AND** the template remains active and untouched in the database
- **AND** no `HttpException (400)` error modal is displayed.

#### Scenario: Informational Notice Modal for Active Template Trashing
- **GIVEN** an active workflow template is selected for trashing
- **WHEN** the trash dialog opens
- **THEN** the modal displays an informational notice with title "Template is Currently Active"
- **AND** explains that the template must be disabled prior to trashing
- **AND** hides the "Confirm" action button and provides a single "Close" button to prevent confusing UX.

#### Scenario: Attempting to Trash an Active Template via HTTP Controller Endpoint
- **GIVEN** an active workflow template exists
- **WHEN** an administrator sends an HTTP DELETE request to `/admin/approval-workflows/{workflow}/trash`
- **THEN** the controller detects the active state
- **AND** redirects back with an error flash message: "Only disabled templates can be deleted. Please disable the template first."
- **AND** no raw 400 Bad Request exception page is rendered.
