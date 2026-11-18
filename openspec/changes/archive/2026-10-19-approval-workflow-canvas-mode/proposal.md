# Proposal: WYSIWYG Canvas Mode for Approval Workflow Templates

## 1. Problem Statement
In `eldin-starter-kit`, approval workflow templates are currently created and managed exclusively through a tabular, form-based interface (`resources/views/components/admin/approvals/workflow-template-editor.blade.php`). While functional, non-technical administrators often find it challenging to visualize complex, multi-tiered approval chains—especially when combining sequential review stages, parallel multi-party sign-offs, and relative managerial resolvers (`N+1` direct line managers and `N+1_of_previous` hierarchical escalations).

A visual, flowchart-style WYSIWYG builder (similar to Draw.io, Zapier, or Jira Workflow Builder) makes designing approval flows intuitive, reduces configuration errors, and provides instant visual clarity.

## 2. Proposed Solution
Introduce a dedicated **WYSIWYG Canvas Mode** for creating and editing approval workflow templates as an alternative view toggle alongside the existing Form View.

Key aspects of the proposed solution:
1. **Structured Stage Pipeline Canvas**:
   - Workflows are represented as a visual pipeline: `[Requester]` $\rightarrow$ `[Stage Groups]` $\rightarrow$ `[Approved/Done]`.
   - Groups function as Stage Containers connected by dynamic SVG connector lines.
   - Inside each Stage, approver steps are stacked sequentially or tiled in parallel (with optional `min_approvals` threshold).
   - Prevents invalid graph states (no orphaned floating nodes or circular infinite loops) while providing a drag-and-drop WYSIWYG experience.
2. **Full-Page Load Delivery (Zero SPA Conflicts)**:
   - Toggling between Form View and Canvas Mode navigates via standard full browser HTTP requests (omitting `wire:navigate`).
   - Ensures drag-and-drop libraries (SortableJS via CDN), SVG connector math, canvas viewport bounding boxes, and zoom transform matrices initialize cleanly without Livewire SPA DOM-morphing conflicts.
3. **Palette Sidebar with Direct Searchable Directory**:
   - Building blocks: *Step Group (Sequential/Parallel)*, *Direct Manager (N+1)*, *Manager of Previous Approver (N+1 of previous)*, and a reserved slot for future *Conditional Rules*.
   - Live Searchable User Directory: Search across current company and ancestor tenant hierarchy by name, email, or role, and drag specific approver cards directly into stages.
4. **Interactive Canvas Viewport**:
   - Pan & Zoom controls (`[ - ]`, `[ Zoom % ]`, `[ + ]`, `[ ⛶ Fit / Center ]`, `Spacebar + Drag` panning, mouse-wheel zooming).
   - Dot-grid visual background with crisp TailAdmin / Shadcn dark-mode support.
5. **Contextual Inspector Panel**:
   - Selecting a stage or step card opens a right-hand property inspector for fine-tuned configuration (stage names, execution modes, minimum thresholds, step labels, and assigned user accounts).
6. **Dual Validation Feedback**:
   - Real-time inline badges on the canvas (e.g., highlighting empty stages, unassigned specific users, or threshold mismatches) with jump-to-issue navigation.
   - Strict validation on save via the existing `StoreWorkflowTemplateRequest`.
7. **100% Core Engine Continuity**:
   - Outputs the exact structured JSON payload consumed by existing `CreateWorkflowTemplateAction` and `UpdateWorkflowTemplateAction`.
   - Requires zero database schema alterations.

## 3. Scope & Non-Goals

### In Scope
- View toggle on create and edit pages (`admin.approval-workflows.create` and `admin.approval-workflows.edit`) supporting `?mode=canvas` and `?mode=form`.
- Full-page canvas interface with Palette, Canvas Viewport, and Inspector Panel.
- Drag-and-drop stage and step reordering via SortableJS (loaded via CDN per `.agents/rules/cdn-libraries.md`).
- Dynamic SVG connector rendering between sequential stages.
- Viewport pan/zoom transformations and control buttons.
- Real-time inline validation indicators and save guards.
- Form serialization submitting to existing `ApprovalWorkflowController@store` and `@update`.
- Feature and browser test coverage.

### Out of Scope / Non-Goals
- Real-time multi-user collaboration (no WebSockets/CRDT canvas synchronization).
- Backend conditional branch evaluation (the visual palette component is reserved, but runtime engine condition evaluation is deferred to a future dedicated change).
- Replacing the existing form view (the form view remains fully supported and accessible).

## 4. Risks & Mitigations
- **Risk**: Complex drag-and-drop DOM manipulations could desynchronize from the underlying state.
  - *Mitigation*: SortableJS events dispatch directly to a central Alpine.js state tree, which serves as the single source of truth for all canvas rendering and form serialization.
- **Risk**: Canvas usability on smaller tablet or mobile touchscreens.
  - *Mitigation*: Fallback click-to-add action buttons inside each stage container, mobile drawer/bottom-sheet inspector, and clear toggle back to Form View.
