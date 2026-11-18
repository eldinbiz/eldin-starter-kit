# Tasks: Approval Workflow Templates - WYSIWYG Canvas Mode

## Phase 1: Controller Routing & Query Mode Support
- [x] 1.1 Update `ApprovalWorkflowController@create` to check `$request->query('mode') === 'canvas'` and pass initial empty template data with available users.
- [x] 1.2 Update `ApprovalWorkflowController@edit` to load existing template groups/steps as structured initial data when `mode === 'canvas'`.
- [x] 1.3 Add a "Switch to Canvas Mode" button in `resources/views/components/admin/approvals/workflow-template-editor.blade.php` with standard HTTP navigation (omitting `wire:navigate`).

## Phase 2: Master Canvas Shell & Viewport Infrastructure
- [x] 2.1 Create master canvas view at `resources/views/admin/approval-workflows/canvas.blade.php` extending `layouts.app`.
- [x] 2.2 Build the top navigation bar with template name, scope radio/selector, active checkbox, validation status badge, and "Save Template" action.
- [x] 2.3 Implement the center canvas viewport with CSS dot-grid background, transform wrapper (`scale`, `translate`), and mouse/wheel gesture event bindings.
- [x] 2.4 Create `resources/views/admin/approval-workflows/canvas/controls.blade.php` with zoom in, zoom out, 100% reset, fit-to-screen, and pan mode toggle buttons.

## Phase 3: Palette Sidebar & Searchable User Directory
- [x] 3.1 Create `resources/views/admin/approval-workflows/canvas/palette.blade.php` with draggable building blocks for Step Groups, Direct Manager (N+1), and Manager of Previous Approver (N+1 of previous).
- [x] 3.2 Add the reserved visual block for future Conditional Rules.
- [x] 3.3 Implement the live Searchable User Directory in the palette with debounce search, user avatar/name display, and draggable user items.

## Phase 4: Stage Pipeline & Dynamic SVG Connectors
- [x] 4.1 Create `resources/views/admin/approval-workflows/canvas/stage-container.blade.php` with group headers, execution mode indicators, delete action, and step drop zones.
- [x] 4.2 Create `resources/views/admin/approval-workflows/canvas/step-card.blade.php` displaying approver type, custom label, assigned user details, and quick remove action.
- [x] 4.3 Integrate SortableJS via official CDN in the layout/view:
  - Configure palette clone behavior into stage containers.
  - Configure intra-stage step sorting and drag reordering.
  - Wire drag events to update Alpine state.
- [x] 4.4 Implement dynamic SVG connector line calculations connecting the Start Node $\rightarrow$ Stages $\rightarrow$ Completion Node.

## Phase 5: Contextual Property Inspector Panel
- [x] 5.1 Create `resources/views/admin/approval-workflows/canvas/inspector.blade.php` for contextual property editing.
- [x] 5.2 Implement stage properties form: Stage Name, Execution Mode (Sequential vs Parallel), and Min Approvals threshold.
- [x] 5.3 Implement step properties form: Step Label, Resolver Type selector, and Specific User assignment picker.

## Phase 6: Real-time Inline Validation & Form Submission
- [x] 6.1 Implement real-time Alpine validation checks for empty stages, unassigned specific users, and invalid minimum approval counts.
- [x] 6.2 Render inline visual warning badges directly on affected stage containers and step cards on the canvas.
- [x] 6.3 Wire top-bar validation indicator with click-to-focus jump to the offending stage or step card.
- [x] 6.4 Implement form payload serialization and submit to `ApprovalWorkflowController@store` and `@update` using standard HTTP POST/PUT.

## Phase 7: Automated Testing & Verification
- [x] 7.1 Create Pest Feature test `tests/Feature/ApprovalWorkflowCanvasModeTest.php` testing `create` and `edit` in Canvas Mode with full user and company authorization.
- [x] 7.2 Create Pest Browser test `tests/Browser/ApprovalWorkflowCanvasTest.php` testing canvas drag-and-drop, inspector property edits, and template saving.
- [x] 7.3 Run full test validation (`composer test`) and code style check (`composer lint:check` / `vendor/bin/pint`).

## Phase 8: Default Mode Transition (Canvas as Primary Interface)
- [x] 8.1 Update `ApprovalWorkflowController@create` and `@edit` to default to Canvas Mode when `mode` query parameter is omitted, rendering Form Mode only when `mode === 'form'`.
- [x] 8.2 Update mode switch links in `workflow-template-editor.blade.php` and `canvas.blade.php`.
- [x] 8.3 Update `tests/Feature/ApprovalWorkflowCanvasModeTest.php` and `tests/Browser/ApprovalWorkflowCanvasTest.php` to assert Canvas Mode by default and verify `?mode=form` navigation.
- [x] 8.4 Remove `wire:navigate` from the "Create Workflow Template" button in `index.blade.php` and the "Edit" action link in `workflow-template-table.blade.php` to enforce full-page browser navigation.

## Phase 9: Canvas Drag-and-Drop Reliability & User Directory Palette Fixes
- [x] 9.1 Configure SortableJS on `#palette-users` with `revertOnSpill: true` and proper clone/item DOM restoration in `onEnd` so the dragged user card remains intact in the palette without creating empty placeholder DOM artifacts.
- [x] 9.2 Implement `isUserInWorkflow(userId)` helper in `canvas.blade.php` to track assigned approvers across all stages in real time.
- [x] 9.3 Add an "Added" visual indicator badge to user cards in `palette.blade.php` when already assigned to any stage on the canvas.
- [x] 9.4 Implement duplicate approver prevention: if a user already present on the canvas is dragged or added into a stage, prevent duplicate addition and show a dismissible warning alert notification.
- [x] 9.5 Enforce strict stage snapping with drag-over visual highlights on valid stage drop zones so dragging onto the open canvas cleanly cancels and snaps back to the palette.
- [x] 9.6 Verify behavior with updated automated tests.
