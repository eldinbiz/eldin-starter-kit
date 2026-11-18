# Design Specification: Workflow Template Trash Filter UI and Restoration Fix

## 1. Architectural Overview

This design resolves the UI regression where trashed workflow templates could not be filtered in the administrator interface, as well as the runtime exceptions encountered when restoring soft-deleted workflow templates.

```
┌────────────────────────────────────────────────────────────────────────────────────────┐
│ WORKFLOW TEMPLATES TABLE INTERFACE (/admin/approval-workflows)                         │
│                                                                                        │
│ ┌────────────────────────────────────────────────────────────────────────────────────┐ │
│ │ Header & Filter Drawer Toggle                                                      │ │
│ │ └─► Filter Drawer (x-show="filterPanelOpen")                                       │ │
│ │     ├── Applies To (Module) [filterValues.type]                                    │ │
│ │     ├── Status (Active / Disabled) [filterValues.is_active]                         │ │
│ │     ├── Scope (This / Subsidiaries / Global) [filterValues.scope]                  │ │
│ │     ├── Company [filterValues.company_id]                                          │ │
│ │     └── Trash Filter [filterValues.trashed] <── [NEW UI CHECKBOX]                  │ │
│ └────────────────────────────────────────────────────────────────────────────────────┘ │
│                                                                                        │
│ ┌────────────────────────────────────────────────────────────────────────────────────┐ │
│ │ Active Filter Chips                                                                │ │
│ │ └─► Trash Chip: "Trash Filter: Show deleted only" [wire:click="removeFilter"]      │ │
│ └────────────────────────────────────────────────────────────────────────────────────┘ │
│                                                                                        │
│ ┌────────────────────────────────────────────────────────────────────────────────────┐ │
│ │ Table Body Rows                                                                    │ │
│ │ └─► If $template->trashed():                                                        │ │
│ │     ├── [ Restore ] Button ──► wire:click="restore({{ $template->id }})"           │ │
│ │     └── [ Delete Permanently ] ──► Open modal ──► wire:click="forceDelete(...)"   │ │
│ └────────────────────────────────────────────────────────────────────────────────────┘ │
└────────────────────────────────────────────────────────────────────────────────────────┘

RESTORE INVOCATION PATHS:

Path A: Livewire Component Action (Primary UI Flow)
┌────────────────────────────────────────┐
│ wire:click="restore({{ $template->id }})" │
└───────────────────┬────────────────────┘
                    │
                    ▼
┌────────────────────────────────────────────────────────────────────────┐
│ WorkflowTemplateTable::restore(int $templateId,                        │
│                                ToggleWorkflowTemplateAction $action)   │
│ ├── 1. WorkflowTemplate::onlyTrashed()->findOrFail($templateId)        │
│ ├── 2. $this->authorizeTemplate($template)                             │
│ ├── 3. $action->enable($template) [Atomic: restore() + is_active=true] │
│ └── 4. $this->dispatch('notify', 'Workflow template restored...')      │
└────────────────────────────────────────────────────────────────────────┘

Path B: HTTP POST Controller Route (Form/API Fallback)
┌────────────────────────────────────────────────────────────────────────┐
│ POST /admin/approval-workflows/{workflow}/restore                      │
│ Route::post(...)->name('approval-workflows.restore')->withTrashed()     │
└───────────────────┬────────────────────────────────────────────────────┘
                    │
                    ▼
┌────────────────────────────────────────────────────────────────────────┐
│ ApprovalWorkflowController::restore(                                   │
│     WorkflowTemplate $workflow,                                        │
│     ToggleWorkflowTemplateAction $action                               │
│ )                                                                      │
│ ├── 1. Implicit Route Model Binding resolves $workflow with trashed    │
│ ├── 2. $this->canManageWorkflow($user, $company, $workflow)            │
│ ├── 3. $action->enable($workflow)                                      │
│ └── 4. return redirect()->route('admin.approval-workflows.index')      │
└────────────────────────────────────────────────────────────────────────┘
```

---

## 2. Component Adjustments (`workflow-template-table.blade.php`)

### 2.1 Filter Drawer UI Markup
Inside the filter drawer grid (`x-show="filterPanelOpen"`), add the trash toggle container:

```blade
<div>
    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">
        Trash Filter
    </label>
    <label class="inline-flex items-center gap-2 pt-2 text-xs text-gray-700 dark:text-gray-300 cursor-pointer">
        <input
            type="checkbox"
            value="1"
            wire:model.live="filterValues.trashed"
            data-testid="workflow-trashed-filter-checkbox"
            class="rounded border-gray-300 text-brand-600 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800"
        />
        <span>Show deleted templates only</span>
    </label>
</div>
```

### 2.2 Active Filter Chips
Add custom label resolution for the `'trashed'` filter key in the component's `filterLabels` configuration or computed mapping:
```php
'trashed' => 'Trash Filter',
```
So that `active-chips.blade.php` displays:
`Trash Filter: Show deleted only`

### 2.3 Query & Filter Scoping Logic
In `applyFilters(Builder $query)`:
```php
if (! empty($this->filterValues['trashed'])) {
    // When inspecting trash, neutralize the is_active filter so disabled trashed items are visible
} else {
    if (isset($this->filterValues['is_active']) && $this->filterValues['is_active'] !== '') {
        $query->where('is_active', (bool) $this->filterValues['is_active']);
    }
}
```

In `query()`:
```php
$showTrashed = ! empty($this->filterValues['trashed']);

$query = WorkflowTemplate::query()
    ->withCount('groups')
    ->with(['company', 'groups.steps']);

if ($showTrashed) {
    $query->onlyTrashed();
} else {
    $query->withoutTrashed();
}

if (! $isSysAdmin) {
    if ($company && ! $showTrashed) {
        $query->availableFor($company);
    } elseif ($company) {
        $query->where(function (Builder $q) use ($company): void {
            $q->where('company_id', $company->id)
                ->orWhereNull('company_id');
        });
    }
}
```

### 2.4 Action Delegation in `restore()`
Update `WorkflowTemplateTable::restore()` to invoke `ToggleWorkflowTemplateAction::enable()`:
```php
public function restore(int $templateId, ToggleWorkflowTemplateAction $action): void
{
    $template = WorkflowTemplate::onlyTrashed()->findOrFail($templateId);
    $this->authorizeTemplate($template);

    $action->enable($template);
    $this->dispatch('notify', message: 'Workflow template restored successfully.', type: 'success');
}
```

### 2.5 Authorization Checks in `authorizeTemplate()`
Update `authorizeTemplate(WorkflowTemplate $template)` to allow management if the user is authorized for the company and template scope:
```php
private function authorizeTemplate(WorkflowTemplate $template): void
{
    /** @var User|null $user */
    $user = auth()->user();
    if ($user?->hasPlatformAccess()) {
        return;
    }

    $company = $user?->getActiveCompany();
    if (! $company) {
        abort(403, 'Unauthorized: No active company context.');
    }

    if ($template->company_id !== null && $template->company_id !== $company->id) {
        abort(403, 'Unauthorized to modify this workflow template.');
    }
}
```

---

## 3. HTTP Routing & Controller Alignment

### 3.1 Route Definition (`routes/web.php`)
Add `->withTrashed()` to route parameter bindings:
```php
Route::post('approval-workflows/{workflow}/restore', [ApprovalWorkflowController::class, 'restore'])
    ->name('approval-workflows.restore')
    ->withTrashed();

Route::delete('approval-workflows/{workflow}/soft-delete', [ApprovalWorkflowController::class, 'softDelete'])
    ->name('approval-workflows.soft-delete')
    ->withTrashed();
```

### 3.2 Controller Method Signature (`ApprovalWorkflowController.php`)
Align method parameters to match route parameter `{workflow}` with implicit model binding:
```php
public function restore(
    WorkflowTemplate $workflow,
    ToggleWorkflowTemplateAction $action
): RedirectResponse {
    /** @var User|null $user */
    $user = auth()->user();
    $company = $user?->getActiveCompany();

    if (! $this->canManageWorkflow($user, $company, $workflow)) {
        abort(403);
    }

    $action->enable($workflow);

    return redirect()->route('admin.approval-workflows.index')
        ->with('success', 'Workflow template enabled and restored.');
}

public function softDelete(
    WorkflowTemplate $workflow,
    ForceDeleteWorkflowTemplateAction $action
): RedirectResponse {
    /** @var User|null $user */
    $user = auth()->user();
    $company = $user?->getActiveCompany();

    if (! $this->canManageWorkflow($user, $company, $workflow)) {
        abort(403);
    }

    $action->execute($workflow);

    return redirect()->route('admin.approval-workflows.index', ['trashed' => 1])
        ->with('success', 'Workflow template permanently deleted.');
}
```

### 3.3 Graceful Validation & Feedback for Trashing Active Templates
1. **Preserve Domain Action Invariant**:
   `TrashWorkflowTemplateAction::execute()` retains its domain business rule ensuring only disabled templates can be deleted:
   ```php
   if ($workflow->is_active) {
       abort(400, 'Only disabled templates can be deleted.');
   }
   ```

2. **Livewire Table Component Prevention**:
   In `WorkflowTemplateTable::trash()`:
   ```php
   $template = WorkflowTemplate::findOrFail($templateId);
   $this->authorizeTemplate($template);

   if ($template->is_active) {
       $this->dispatch('notify', message: 'Only disabled templates can be moved to trash. Please disable this template first.', type: 'error');

       return;
   }

   $action->execute($template);
   $this->dispatch('notify', message: 'Workflow template moved to trash.', type: 'warning');
   ```

3. **Informational Notice Modal for Active Templates**:
   In `workflow-template-table.blade.php`:
   - Bind `targetIsActive = {{ $template->is_active ? 'true' : 'false' }}` when clicking the row's "Trash" button.
   - When `actionType === 'trash' && targetIsActive`:
     - Render modal title: `"Template is Currently Active"` and style icon with amber warning tone.
     - Explain clearly that active templates cannot be moved to trash and must be disabled first.
     - Hide the `"Confirm"` button (`x-show="!(actionType === 'trash' && targetIsActive)"`).
     - Render a single `"Close"` button (`x-text="actionType === 'trash' && targetIsActive ? 'Close' : 'Cancel'"`), eliminating the confusing UX of having both Cancel and Confirm do the same thing.

4. **HTTP Controller Graceful Redirect**:
   In `ApprovalWorkflowController::trash()`:
   ```php
   if ($workflow->is_active) {
       return redirect()->route('admin.approval-workflows.index')
           ->with('error', 'Only disabled templates can be deleted. Please disable the template first.');
   }
   ```

---

## 4. Multi-Tenancy & Security Verification

1. **Tenant Isolation**: Non-platform administrators cannot restore or view workflow templates that belong to foreign companies.
2. **Global Template Safety**: Non-platform administrators cannot modify or restore global templates (`company_id === null`) if system policies restrict global templates to platform administrators.
3. **Atomic State Consistency**: `ToggleWorkflowTemplateAction::enable` guarantees that soft-deleted state is cleared (`restore()`) and `is_active` is updated to `true` in a consistent manner.
