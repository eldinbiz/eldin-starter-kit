# Capability: Approval Workflow Templates - WYSIWYG Canvas Mode

## Purpose
Provide administrators with a graphical, drag-and-drop WYSIWYG interface to construct, visualize, and validate multi-tiered approval workflow templates alongside the existing form editor.

---

## Requirements & Behavioral Scenarios

### Requirement: Canvas Mode as Default Interface with Form View Fallback
The system MUST render Canvas Mode as the primary, default interface on both the workflow creation (`/admin/approval-workflows/create`) and workflow editing (`/admin/approval-workflows/{workflow}/edit`) endpoints. An optional Form View is accessible via `?mode=form`. Both views allow administrators to toggle between modes using full browser HTTP reloads without SPA navigation hijacking.

#### Scenario: Opening Workflow Creation or Edit by Default
- **GIVEN** an authorized administrator navigates to `/admin/approval-workflows/create` or `/admin/approval-workflows/{id}/edit` without a mode query parameter
- **WHEN** the request is processed
- **THEN** the system renders the Canvas Mode interface by default
- **AND** all canvas components, building blocks, and viewport controls are initialized.

#### Scenario: Switching from Canvas Mode to Form View
- **GIVEN** an administrator is in Canvas Mode
- **WHEN** the administrator clicks the "Switch to Form View" button
- **THEN** the browser performs a full-page HTTP GET to `/admin/approval-workflows/create?mode=form` (or edit endpoint with `?mode=form`)
- **AND** the standard Livewire form editor loads cleanly.

#### Scenario: Switching from Form View back to Canvas Mode
- **GIVEN** an administrator is viewing the workflow page in Form View (`?mode=form`)
- **WHEN** the administrator clicks the "Switch to Canvas Mode" button
- **THEN** the browser performs a full-page HTTP GET to `/admin/approval-workflows/create` (or edit endpoint)
- **AND** Canvas Mode is restored.

---

### Requirement: Structured Stage Pipeline Canvas
The Canvas Mode MUST render approval workflow templates as an ordered sequence of Stage Groups connected by visual connector lines, starting from a Requester node and terminating at an Approved node.

#### Scenario: Adding a new Stage Group via drag-and-drop
- **GIVEN** an administrator has the canvas open with at least one stage
- **WHEN** the administrator drags a "Step Group" block from the palette and drops it into the pipeline
- **THEN** a new Stage Group container is created at the dropped position with a default sequential execution mode
- **AND** connector lines dynamically update to link preceding and succeeding stages in proper order.

#### Scenario: Deleting a Stage Group
- **GIVEN** an administrator has multiple stages on the canvas
- **WHEN** the administrator clicks the delete button on an empty or existing stage and confirms
- **THEN** the stage is removed from the canvas
- **AND** the pipeline automatically bridges the remaining stages with updated connector lines.

---

### Requirement: Approver Step Drag-and-Drop & Reordering
The system MUST allow administrators to drag approver types from the palette into any Stage Group, and reorder steps within or across stages.

#### Scenario: Adding a Direct Manager (N+1) step to a stage
- **GIVEN** a Stage Group exists on the canvas
- **WHEN** the administrator drags the "Direct Manager (N+1)" block into the stage
- **THEN** an approver step card is created inside that stage with resolver type `N+1`
- **AND** the card displays "Direct Line Manager" as its default label.

#### Scenario: Adding a Manager of Previous Approver step
- **GIVEN** a Stage Group exists following a prior approval step
- **WHEN** the administrator drags "Manager of Previous Approver" into the stage
- **THEN** an approver step card is added with resolver type `N+1_of_previous`.

#### Scenario: Reordering steps within a stage
- **GIVEN** a stage has two or more approver steps
- **WHEN** the administrator drags a step card above another step within the same stage
- **THEN** the step order updates immediately in the stage view and underlying template state.

---

### Requirement: Searchable Approver User Directory in Palette
The palette MUST provide a live search input allowing administrators to search users from the active company and ancestor companies, and drag individual user accounts directly into stages.

#### Scenario: Searching for a specific user in the palette
- **GIVEN** the administrator opens the User Directory tab or section in the palette
- **WHEN** the administrator types a query into the user search field (e.g., "CFO" or "Jane")
- **THEN** the list filters in real time to display matching users with their name, email, and avatar
- **AND** each user item has a drag handle.

#### Scenario: Dragging a specific user account into a stage
- **GIVEN** a user item appears in the search results
- **WHEN** the administrator drags that user item into a stage container
- **THEN** a new step is created inside that stage with resolver type `specific_user`
- **AND** the step payload automatically records `user_id` set to that user's ID
- **AND** the step card displays the user's name, email, and avatar
- **AND** the user item remains visible and intact in the User Directory palette without leaving an empty placeholder.

#### Scenario: Preventing duplicate user approver assignments
- **GIVEN** a user account is already assigned as an approver in any stage on the canvas
- **WHEN** the administrator attempts to drag or add the same user into any stage
- **THEN** the system rejects the duplicate addition
- **AND** displays an alert warning notification indicating that the user is already added to the workflow
- **AND** the user card in the palette displays an "Added" visual badge.

#### Scenario: Dragging user outside of stage drop zones (Strict Stage Snapping)
- **GIVEN** the administrator drags a user card onto the open canvas background
- **WHEN** the mouse is released outside of any stage drop zone
- **THEN** the drag operation cleanly cancels and snaps back to the palette
- **AND** no ghost DOM nodes or empty placeholder elements are left on the canvas.

---

### Requirement: Contextual Inspector Panel
The system MUST provide an Inspector Panel that displays and updates the settings of whichever node or stage is currently selected on the canvas.

#### Scenario: Editing Stage Group properties
- **GIVEN** an administrator clicks on a Stage Group header
- **WHEN** the Inspector Panel displays the group settings
- **THEN** the administrator can change the stage name, toggle between Sequential and Parallel modes, and configure `min_approvals` threshold when in Parallel mode
- **AND** the canvas updates the stage appearance and badges in real time.

#### Scenario: Editing Step properties
- **GIVEN** an administrator clicks on an Approver Step card
- **WHEN** the Inspector Panel displays the step settings
- **THEN** the administrator can modify the custom label and reassign the designated user account if the resolver is `specific_user`.

---

### Requirement: Canvas Pan & Zoom Controls
The canvas MUST support fluid panning and zooming across multiple stages.

#### Scenario: Zooming in and out
- **GIVEN** a workflow with multiple stages
- **WHEN** the administrator clicks `[ + ]` or `[ - ]` or uses `Ctrl + Mouse Wheel`
- **THEN** the canvas scales smoothly between 50% and 150% without clipping cards.

#### Scenario: Centering and fitting to screen
- **GIVEN** a workflow scaled or panned away from center
- **WHEN** the administrator clicks `[ ⛶ Fit to Screen ]`
- **THEN** the canvas calculates stage boundaries and repositions the pipeline centered in the viewport.

---

### Requirement: Dual Validation & Persistence
The system MUST provide real-time visual health feedback on the canvas and enforce server-side validation upon template creation and update.

#### Scenario: Real-time visual warning on empty stage
- **GIVEN** a stage has been created with zero approver steps
- **WHEN** the canvas evaluates its state
- **THEN** an inline warning badge appears on the stage: "Group must have at least 1 step"
- **AND** the top bar status badge switches to warning status.

#### Scenario: Successful save of a new template from canvas
- **GIVEN** an administrator has configured a valid template with name, scope, stages, and approvers on the canvas
- **WHEN** the administrator clicks "Save Template"
- **THEN** the client validates all required fields, serializes the JSON structure, and submits to `ApprovalWorkflowController@store`
- **AND** the server validates via `StoreWorkflowTemplateRequest` and executes `CreateWorkflowTemplateAction`
- **AND** the user is redirected to the template list with a success flash message.

#### Scenario: Loading existing template into Canvas Mode
- **GIVEN** a template with ID 5 exists with 2 groups and 3 steps
- **WHEN** the administrator visits `/admin/approval-workflows/5/edit?mode=canvas`
- **THEN** the canvas renders with all 2 groups and 3 steps pre-populated in exact order
- **AND** saving calls `ApprovalWorkflowController@update` via `UpdateWorkflowTemplateAction`.
