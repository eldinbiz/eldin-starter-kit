# Design Specification: Approval Workflow Templates - WYSIWYG Canvas Mode

## 1. Architectural Overview

The WYSIWYG Canvas Mode is designed as an interactive, graphical pipeline builder that coexists with the existing Livewire form view. It uses a **Zero-Build, CDN-first, Alpine-reactive architecture** (complying strictly with `.agents/rules/cdn-libraries.md`).

```
┌────────────────────────────────────────────────────────────────────────────────────────┐
│ BROWSER VIEWPORT (Full-Page HTTP Load: /admin/approval-workflows/create?mode=canvas)    │
│                                                                                        │
│ ┌────────────────────────────────────────────────────────────────────────────────────┐ │
│ │ TOP BAR: Template Meta, Scope, Mode Toggle [Form | Canvas], Save Action            │ │
│ └────────────────────────────────────────────────────────────────────────────────────┘ │
│ ┌─────────────────────┬────────────────────────────────────────┬─────────────────────┐ │
│ │ PALETTE & DIRECTORY │ WORKFLOW CANVAS VIEWPORT               │ INSPECTOR PANEL     │ │
│ │                     │                                        │                     │ │
│ │ - Group Containers  │ - Infinite dot-grid background         │ - Stage Properties  │ │
│ │ - N+1 Manager       │ - CSS Transform: translate() scale()   │   (Name, Mode, Min) │ │
│ │ - N+1 of Prev       │ - SVG Dynamic Connector Paths          │ - Step Properties   │ │
│ │ - User Directory    │ - SortableJS Drop-Zones (Stages/Steps) │   (Label, Resolver) │ │
│ │   (Live Filter)     │ - Floating Zoom/Pan Controls           │ - Validation Hints  │ │
│ └─────────────────────┴────────────────────────────────────────┴─────────────────────┘ │
│                                                                                        │
│                                       │ (On Save Form Submission)                      │
│                                       v                                                │
│                 POST /admin/approval-workflows (Standard Form Request)                │
│                 Validated by StoreWorkflowTemplateRequest                              │
│                 Executed by CreateWorkflowTemplateAction / UpdateAction                │
└────────────────────────────────────────────────────────────────────────────────────────┘
```

---

## 2. HTTP Routing & Controller Integration

### Controller Adjustments (`ApprovalWorkflowController.php`)
Both `create()` and `edit()` endpoints will inspect the query parameter `$request->query('mode')`:

```php
public function create(Request $request): View
{
    $company = auth()->user()?->getActiveCompany();
    if (! $company) {
        abort(403);
    }

    $users = $this->getCompanyAndAncestorUsers($company);
    $company->loadMissing('children');
    $mode = $request->query('mode', 'form');

    if ($mode === 'canvas') {
        return view('admin.approval-workflows.canvas', [
            'mode' => 'canvas',
            'company' => $company,
            'users' => $users,
            'approvableTypes' => ApprovableTypes::options(),
            'initialData' => [
                'name' => '',
                'description' => '',
                'approvable_type' => null,
                'scope' => 'this_company',
                'is_active' => true,
                'groups' => [
                    [
                        'name' => 'Department Review',
                        'type' => 'sequential',
                        'min_approvals' => null,
                        'steps' => [
                            [
                                'label' => 'Direct Line Manager (N+1)',
                                'resolver_type' => 'N+1',
                                'resolver_payload' => null,
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    return view('admin.approval-workflows.create', [ ... ]);
}
```

Similarly, in `edit()`, when `mode === 'canvas'`, the existing template, its groups, and steps are eager-loaded and passed as `$initialData` to `admin.approval-workflows.canvas`.

### Full-Page Mode Toggle Links
In both `resources/views/components/admin/approvals/workflow-template-editor.blade.php` and the new canvas view, mode toggle buttons omit `wire:navigate`:

```blade
{{-- In Form View: Standard HTTP link forcing full-page load into Canvas --}}
<a 
    href="{{ route('admin.approval-workflows.create', ['mode' => 'canvas']) }}" 
    class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
>
    🎨 Switch to Canvas Mode
</a>
```

---

## 3. Frontend Architecture & Component Decomposition

### View File Structure
```
resources/views/admin/approval-workflows/
├── canvas.blade.php                # Master canvas wrapper
└── canvas/
    ├── palette.blade.php           # Left palette & user search directory
    ├── viewport.blade.php          # Center scrollable / zoomable SVG canvas
    ├── stage-container.blade.php   # Render template for stage groups
    ├── step-card.blade.php         # Render template for approver step nodes
    ├── inspector.blade.php         # Right-hand contextual property editor
    └── controls.blade.php          # Floating pan & zoom toolbar
```

### Alpine.js Component State: `workflowCanvas()`
The canvas runs on an encapsulated Alpine component:

```javascript
function workflowCanvas(config) {
    return {
        // Template Core Metadata
        templateId: config.templateId || null,
        name: config.initialData.name || '',
        description: config.initialData.description || '',
        approvableType: config.initialData.approvable_type || '',
        scope: config.initialData.scope || 'this_company',
        isActive: config.initialData.is_active ?? true,

        // Pipeline Stages & Steps Tree
        groups: config.initialData.groups || [],

        // User Directory Data & Filter
        users: config.availableUsers || [],
        userSearch: '',

        // Viewport Pan & Zoom State
        zoom: 1.0,
        panX: 0,
        panY: 0,
        isPanning: false,
        startX: 0,
        startY: 0,

        // Selection & Inspector State
        selected: null, // { type: 'group' | 'step', gIdx: number, sIdx?: number }

        // Validation Issues
        validationIssues: [],

        init() {
            this.initSortable();
            this.updateConnectors();
            this.validateWorkflow();
        },

        // Methods: addGroup, removeGroup, addStep, removeStep, selectNode, zoomIn, zoomOut, fitScreen, save()
    };
}
```

---

## 4. Drag-and-Drop via SortableJS (CDN Integration)

Per `.agents/rules/cdn-libraries.md`, SortableJS is loaded via CDN:
```blade
@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
@endpush
```

### Drag-and-Drop Interaction Rules:
1. **Palette Items (Clone Source & Strict Snapping)**:
   - Palette blocks and users have `group: { name: 'approver-items', pull: 'clone', put: false }` with `revertOnSpill: true` and `draggable: '.palette-user-item, .palette-item'`.
   - In `onEnd(evt)`, when `evt.pullMode === 'clone'`, SortableJS's cloned placeholder is cleanly restored so the original Alpine DOM node in `#palette-users` remains 100% intact, preventing empty card placeholders in the palette.
   - Dropping outside valid stage drop zones triggers clean cancellation and snaps back to the sidebar palette.
2. **Duplicate Approver Prevention**:
   - `isUserInWorkflow(userId)` checks across all stages whether a specific user is already assigned.
   - User cards in `palette.blade.php` display an `"Added"` status badge if already present in the workflow.
   - When an administrator attempts to drop or add a user already present in the workflow, the addition is rejected and an alert warning notification informs the administrator: *"User [Name] has already been added as an approver in this workflow."*
3. **Stage Containers (Drop Targets & Intra-Stage Sorting)**:
   - Each stage's step list is initialized with SortableJS `group: 'approver-items'` and `animation: 150`.
   - Intra-stage step sorting updates the Alpine `groups[gIdx].steps` array in place with unique step IDs (`step.uid`) to ensure deterministic Alpine DOM reconciliation.
4. **Pipeline Stages (Inter-Stage Reordering)**:
   - The stage container strip is sortable along the primary pipeline axis using a designated drag handle `[data-stage-drag-handle]`.

---

## 5. Pipeline Flow Connectors & Stage Spacing

Linear flow connectors link the Start Node $\rightarrow$ Stages $\rightarrow$ Finish Node:
- **Guaranteed Vertical Gap**: Each stage is separated by an explicit 52px height connector container with a centered vertical guide line (`pipeline-connector-line`).
- **Directional Flow**: A downward arrowhead (`pipeline-connector-arrow`) indicates sequential execution without awkward hardware-style circular ports protruding from the cards.
- **Inline Stage Insertion**: Each intermediate connector contains an interactive `(+) Add Stage` button (`pipeline-connector-btn`) allowing one-click stage insertion at that exact point in the pipeline.
- **Zero Runtime Coordinate Jitter**: Implemented via reactive DOM elements with CSS styles in `@push('styles')`, ensuring 100% stability across zoom, pan, and window resize operations without SVG coordinate clipping or Tailwind cache misses.

---

## 6. Viewport Pan & Zoom Implementation

The canvas uses hardware-accelerated CSS transforms:

```html
<div class="relative overflow-hidden w-full h-[750px] bg-dot-grid"
     @mousedown="startPan($event)"
     @mousemove="doPan($event)"
     @mouseup="endPan()"
     @wheel.ctrl.prevent="wheelZoom($event)">
    <div :style="`transform: translate(${panX}px, ${panY}px) scale(${zoom}); transform-origin: 0 0;`"
         class="inline-flex flex-col items-center gap-8 p-12 transition-transform duration-75">
        <!-- Start Node, Stage Containers, Connector Lines, End Node -->
    </div>
</div>
```

---

## 7. Dual Validation & Submission Flow

1. **Continuous Client Validation**:
   - Empty group: `groups[i].steps.length === 0` $\rightarrow$ triggers visual amber dashed outline and badge.
   - Specific user missing: `resolver_type === 'specific_user' && !resolver_payload.user_id` $\rightarrow$ red indicator.
   - Parallel threshold: `type === 'parallel' && min_approvals > steps.length` $\rightarrow$ warning.
2. **Form Submission**:
   - Hidden input fields encode the payload:
     - `name`, `description`, `approvable_type`, `scope`, `is_active`
     - `groups` formatted identically to `StoreWorkflowTemplateRequest`
   - Submits to `route('admin.approval-workflows.store')` or `update` via standard HTTP POST/PUT.
