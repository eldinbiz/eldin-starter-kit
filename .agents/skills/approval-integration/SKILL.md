---
name: approval-integration
description: "Apply this skill when building a new module that requires approval workflows (e.g., Purchase Requests, Expense Reports, Leave Requests). Triggers: approval, approvable, ApprovalEngine, workflow, submit for approval, approval request, implement Approvable. Covers: implementing the Approvable contract, calling ApprovalEngine::submit(), selecting workflow templates, listening to approval events, adding approval status badges, and the full request lifecycle."
license: MIT
metadata:
  author: eldinbiz
---
# Approval Module Integration

This skill documents how any new module integrates with the polymorphic approval engine. Follow this guide **exactly** when building a module that needs approval workflows.

## Architecture

The approval module is an **engine + infrastructure**, not a standalone CRUD app. It provides:

| Layer     | Component                             | Location                            |
| --------- | ------------------------------------- | ----------------------------------- |
| Contract  | `Approvable` interface              | `app/Contracts/Approvable.php`    |
| Engine    | `ApprovalEngine` service            | `app/Services/ApprovalEngine.php` |
| Templates | Admin-configured workflow definitions | `Admin > Workflow Templates`      |
| Inbox     | Approvers act on pending steps        | `/approvals/inbox`                |
| History   | Approvers' past actions               | `/approvals/history`              |
| Tracking  | Requestors monitor submissions        | `/approvals/requests`             |
| Events    | 6 lifecycle events + 1 universal listener | `app/Events/Approval/*` + `app/Listeners/Approval/` |
| Helpers   | Auto-discovery of approvable types    | `app/Helpers/ApprovableTypes.php` |

Any model implementing `Approvable` becomes "approvable" — the approval pipeline handles the rest.

---

## Step 1: Implement the `Approvable` Contract

Your model must implement **9 methods**. Here is a complete example:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\Approvable;
use App\Enums\ApprovalStatus;
use App\Models\Approval\ApprovalRequest;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class PurchaseRequest extends Model implements Approvable
{
    // === Required Identity Methods ===

    public function getApprovalTitle(): string
    {
        return "Purchase Request #{$this->id}: {$this->title}";
    }

    public function getApprovalUrl(): string
    {
        return route('purchase-requests.show', $this->id);
    }

    public function getApprovalRequester(): User
    {
        return $this->requester; // BelongsTo relationship
    }

    public function getApprovalCompany(): Company
    {
        return $this->company; // BelongsTo relationship
    }

    // === Optional Preview (return null for simple title+link) ===

    public function getApprovalPreview(): ?array
    {
        return [
            'view' => 'purchase-requests._approval-preview',
            'data' => ['purchaseRequest' => $this],
        ];
    }

    // === Lifecycle Callbacks (called by the universal listener) ===

    public function onApprovalCompleted(ApprovalRequest $request): void
    {
        $this->update(['status' => 'approved']);
    }

    public function onApprovalRejected(ApprovalRequest $request): void
    {
        $this->update(['status' => 'rejected']);
    }

    public function onApprovalCanceled(ApprovalRequest $request): void
    {
        $this->update(['status' => 'canceled']);
    }

    // === Type Label (shown in the Workflow Template "Applies To" dropdown) ===

    public static function getApprovalTypeLabel(): string
    {
        return 'Purchase Request';
    }

    // === Helpers ===

    public function approvalRequests(): MorphMany
    {
        return $this->morphMany(ApprovalRequest::class, 'approvable');
    }

    public function isPendingApproval(): bool
    {
        return $this->approvalRequests()
            ->where('status', ApprovalStatus::Pending)
            ->exists();
    }
}
```

### Method reference

| Method                     | Return      | Required | Notes                                                    |
| -------------------------- | ----------- | -------- | -------------------------------------------------------- |
| `getApprovalTitle()`     | `string`  | **Yes**    | Shown in inbox, requests list, and notification emails   |
| `getApprovalUrl()`       | `string`  | **Yes**    | "View Full" link in the approval detail page             |
| `getApprovalRequester()` | `User`    | **Yes**    | Used to resolve N+1 approver                             |
| `getApprovalCompany()`   | `Company` | **Yes**    | Scopes to the correct company context                    |
| `getApprovalPreview()`   | `?array`  | No       | Return`['view' => '...', 'data' => [...]]` or `null`     |
| `onApprovalCompleted()`  | `void`    | **Yes**    | Called when request is fully approved                    |
| `onApprovalRejected()`   | `void`    | **Yes**    | Called when any step rejects                             |
| `onApprovalCanceled()`   | `void`    | **Yes**    | Called when requestor cancels                            |
| `getApprovalTypeLabel()` | `string`  | **Yes**    | Static method. Human-readable label (e.g. "Purchase Request"). Used in the workflow template admin UI's "Applies To" dropdown. |

---

## Step 2: Find the Appropriate Workflow Template

Templates are scoped by company and type. Use `WorkflowTemplate::availableFor($company)` to respect the template's scope setting:

```php
use App\Models\Approval\WorkflowTemplate;

// Fetch all templates available to this company, filtered by type + universal
$templates = WorkflowTemplate::availableFor($company)
    ->where('is_active', true)
    ->where(fn ($q) => $q
        ->where('approvable_type', PurchaseRequest::class)
        ->orWhereNull('approvable_type'))
    ->get();
```

**Template scoping rules** (`WorkflowTemplate::availableFor()`):

| Scope | Available to |
|---|---|
| `this_company` | Only the owning company |
| `subsidiaries` | Owning company + all ancestors |
| `global` | All companies |

**Type filtering** in the query ensures templates assigned to "Purchase Order" don't appear when submitting a "Purchase Request" — and vice versa. Templates with `NULL` `approvable_type` ("Any Module") appear for all modules.

---

## Step 3: Submit the Approval Request

Call the engine from your module's controller. **Always wrap `$engine->submit()` in a try/catch** to handle resolution failures gracefully:

```php
use App\Models\Approval\WorkflowTemplate;
use App\Services\ApprovalEngine;
use RuntimeException;

class PurchaseRequestController extends Controller
{
    public function submitForApproval(PurchaseRequest $purchaseRequest, Request $request): RedirectResponse
    {
        // Guard: ensure the user is the owner
        if ($purchaseRequest->requester_id !== auth()->id()) {
            abort(403);
        }

        // Guard: prevent duplicate submissions
        if ($purchaseRequest->status !== 'draft') {
            return back()->with('error', 'This purchase request cannot be submitted.');
        }

        $templateId = $request->integer('template_id');
        $template = WorkflowTemplate::findOrFail($templateId);

        // Submit with error handling for resolution failures
        try {
            $this->engine->submit($purchaseRequest, $template);
        } catch (RuntimeException $e) {
            return back()->with('error', 'Cannot submit for approval: '.$e->getMessage());
        }

        $purchaseRequest->update(['status' => 'pending_approval']);

        return redirect()->route('purchase-requests.show', $purchaseRequest->id)
            ->with('success', 'Purchase request submitted for approval.');
    }
}
```

### What the engine does automatically

- Snapshots the workflow template (so edits don't affect in-flight requests)
- Resolves approvers based on resolver type:
  - `N+1` → requestor's direct manager
  - `specific_user` → named user from the company or an ancestor company
  - `N+1_of_previous` → created with `state = AwaitingPrevious` and `approver_id = null`. Resolved at runtime when the previous step is approved.
- Creates `approval_requests` + `approval_request_steps` rows
- Dispatches `ApprovalRequested` event

### Error handling

`ApprovalEngine::submit()` can throw `RuntimeException` when:
- An N+1 resolver finds the requestor has **no manager** assigned in the company
- A `specific_user` resolver targets a user who **doesn't belong** to the company or any ancestor
- A `specific_user` resolver has **no `user_id`** in its payload

The try/catch in the controller catches these and displays a user-friendly error message with actual names (not internal IDs).

---

## Step 4: Event Listeners (Universal — No Module-Specific Code Needed)

A **single universal listener** handles all approval lifecycle events for all modules. No per-module listeners are needed.

### Architecture

```
ApprovalRejected ──┐
ApprovalCompleted ─┤ → UpdateApprovableStatus::handle() → $approvable->onApprovalRejected()
ApprovalCanceled ──┘                                      → $approvable->onApprovalCompleted()
                                                          → $approvable->onApprovalCanceled()
```

The listener is registered in `app/Providers/AppServiceProvider.php::boot()`:

```php
use App\Events\Approval\ApprovalCanceled;
use App\Events\Approval\ApprovalCompleted;
use App\Events\Approval\ApprovalRejected;
use App\Listeners\Approval\UpdateApprovableStatus;

Event::listen(ApprovalCompleted::class, UpdateApprovableStatus::class);
Event::listen(ApprovalRejected::class, UpdateApprovableStatus::class);
Event::listen(ApprovalCanceled::class, UpdateApprovableStatus::class);
```

### Events reference

| Event                  | Fired When              | What Happens Automatically                 |
| ---------------------- | ----------------------- | ------------------------------------------ |
| `ApprovalRequested`    | Request created         | Dispatched by engine; notification module picks up |
| `ApprovalStepCompleted`| One step approved       | Dispatched by engine; state machine advances |
| `ApprovalCompleted`    | All groups complete     | Listener calls `onApprovalCompleted()`      |
| `ApprovalRejected`     | Any step rejected       | Listener calls `onApprovalRejected()`       |
| `ApprovalCanceled`     | Requestor cancels       | Listener calls `onApprovalCanceled()`       |
| `ApprovalDelegated`    | Approver reassigned     | Notification only                           |

**How rejection cascading works:** When any step is rejected via `ApprovalEngine::reject()`, all remaining `Pending` AND `AwaitingPrevious` steps are bulk-rejected. The step that was directly rejected has an `action_at` timestamp and notes. Cascaded steps have no `action_at` — in the workflow progress table, they display as a gray "Skipped" badge instead of red "Rejected".

---

## Step 5: Add Approval Status to the Module's View

### Status badge (in your module's show/index pages)

```blade
@if ($purchaseRequest->status === 'draft')
    <span class="...">Draft</span>
@elseif ($purchaseRequest->status === 'pending_approval')
    <span class="...">Pending Approval</span>
@elseif ($purchaseRequest->status === 'approved')
    <span class="...">Approved</span>
@elseif ($purchaseRequest->status === 'rejected')
    <span class="...">Rejected</span>
@endif
```

### Flash messages (required on the show page)

Both `show.blade.php` must render `session('error')` so submission failures are visible:

```blade
@if (session('error'))
    <div class="mb-4 rounded-lg bg-error-50 p-4 text-sm text-error-600 dark:bg-error-500/10 dark:text-error-400">
        {{ session('error') }}
    </div>
@endif

@if (session('success'))
    <div class="mb-4 rounded-lg bg-success-50 p-4 text-sm text-success-600 dark:bg-success-500/10 dark:text-success-500">
        {{ session('success') }}
    </div>
@endif
```

### Submit modal (reusable Blade partial)

Use the reusable `approvals._submit-modal` partial with template selection:

```blade
@include('approvals._submit-modal', [
    'approvable' => $purchaseRequest,
    'submitRoute' => route('purchase-requests.submit-for-approval', $purchaseRequest->id),
    'templates' => $templates,
    'previewRoute' => route('purchase-requests.preview-workflow', $purchaseRequest->id),
])
```

This partial:
- Shows a dropdown of available templates
- Previews the workflow diagram via AJAX when a template is selected
- Includes a "Confirm Submission" button

### Workflow progress table (reusable)

```blade
@if ($latestRequest)
    @include('approvals._workflow-progress-table', ['approvalRequest' => $latestRequest])
@endif
```

---

## Step 6: Routes for Submission

```php
// routes/web.php — inside the auth middleware group
Route::middleware('auth')->prefix('purchase-requests')->name('purchase-requests.')->group(function () {
    Route::get('/', [PurchaseRequestController::class, 'index'])->name('index');
    Route::get('/create', [PurchaseRequestController::class, 'create'])->name('create');
    Route::post('/', [PurchaseRequestController::class, 'store'])->name('store');
    Route::get('/{purchaseRequest}', [PurchaseRequestController::class, 'show'])->name('show');
    Route::get('/{purchaseRequest}/preview-workflow', [PurchaseRequestController::class, 'previewWorkflow'])->name('preview-workflow');
    Route::post('/{purchaseRequest}/submit-for-approval', [PurchaseRequestController::class, 'submitForApproval'])->name('submit-for-approval');
});
```

---

## Resolver Types Reference

When configuring workflow templates in the admin UI, these are the available resolver types:

| Type                | Label in UI         | Who Becomes the Approver                                                 |
| ------------------- | ------------------- | ------------------------------------------------------------------------ |
| `N+1`             | N+1 (Manager)       | The requestor's direct manager within the company                        |
| `specific_user`   | Specific User       | A named user selected from the company or any ancestor company           |
| `N+1_of_previous` | N+1 of Previous     | The direct manager of the previous step's approver (resolved at runtime) |

### N+1_of_previous behavior

- At submission time, the step is created with `approver_id = null`, `state = AwaitingPrevious`.
- When the previous step is approved, the engine resolves the previous approver's manager.
- If the previous approver has **no manager**, the step is auto-skipped (auto-approved with notes "Auto-skipped (no manager found for previous approver)").
- If auto-resolution fails for other reasons, the step stays `AwaitingPrevious` — a platform admin or the requestor can manually resolve it via the `resolvePreviousManager` route.
- Only works in **sequential** groups (parallel groups have no defined "previous").

---

## Step States

| State | Meaning |
|---|---|
| `pending` | Awaiting the approver's action |
| `approved` | Approver approved the step |
| `rejected` | Approver rejected the step (cascades to all remaining steps) |
| `awaiting_previous` | Cannot resolve until the previous step in the sequential group completes |

---

## Workflow Template Configuration

### Template scope

Templates have a `scope` column that controls which companies can use them:

| Scope | Description |
|---|---|
| This Company | Only users in the owning company |
| This Company & Subsidiaries | Owning company + all ancestor companies |
| Global (All Companies) | All companies |

The scope radio buttons in the admin forms display the actual company name (e.g., "This Company (MMSGI)", "This Company & Subsidiaries (MMSGI → MMS Solution, MMS Land)") for clarity.

### Applies To dropdown

The "Applies To" field in the workflow template form uses a `<select>` dropdown auto-populated by `App\Helpers\ApprovableTypes::options()`. Any model implementing `Approvable` with `getApprovalTypeLabel()` appears automatically — no manual wiring needed.

---

## Race Condition Handling

When multiple approvers act in a **parallel** group, one may reject while another's approval page is still open. The controller guards against this:

```php
// In approve() and reject() — before calling the engine:
if (! $step->isPending()) {
    return redirect()->back()->with('error',
        'This step has already been processed. The approval request status may have changed.');
}
```

The page re-renders with a red error banner, action buttons disappear, and the updated workflow status is shown.

---

## Key Files Reference

| File                                            | Purpose                                                                             |
| ----------------------------------------------- | ----------------------------------------------------------------------------------- |
| `app/Contracts/Approvable.php`                | Contract (9 methods) — implements this on your model                                 |
| `app/Services/ApprovalEngine.php`             | Core engine: `submit()`, `approve()`, `reject()`, `cancel()`, `delegate()`, `resolvePreviousManager()` |
| `app/Enums/ApprovalStatus.php`                | `Pending`, `Approved`, `Rejected`, `Canceled`                               |
| `app/Enums/StepState.php`                     | `Pending`, `Approved`, `Rejected`, `AwaitingPrevious`                        |
| `app/Enums/ResolverType.php`                  | `NPlus1`, `SpecificUser`, `NPlus1OfPrevious`                                  |
| `app/Enums/GroupType.php`                     | `Sequential`, `Parallel`                                                        |
| `app/Enums/TemplateScope.php`                 | `ThisCompany`, `ThisCompanyAndSubsidiaries`, `Global`                        |
| `app/Helpers/ApprovableTypes.php`             | Auto-discovers all `Approvable` implementations for dropdowns                   |
| `app/Models/Approval/ApprovalRequest.php`     | Polymorphic request model; `currentSteps()` finds active group                  |
| `app/Models/Approval/ApprovalRequestStep.php` | Per-step runtime state with `display_label` and scopes                         |
| `app/Models/Approval/WorkflowTemplate.php`    | Template model with `availableFor()` scope                                      |
| `app/Listeners/Approval/UpdateApprovableStatus.php` | Universal listener — calls lifecycle methods on `Approvable` models       |
| `app/Events/Approval/*`                       | 6 lifecycle events                                                                  |
| `resources/views/approvals/_submit-modal.blade.php` | Reusable template selection + workflow preview modal                       |
| `resources/views/approvals/_workflow-progress-table.blade.php` | Reusable per-step status table with skipped/awaiting badges     |

---

## Complete Module Template

When building a new module from scratch (e.g., Expense Reports, Leave Requests), follow this template. Replace module-specific names (prefix, route prefix, model class) as needed.

### 1. Create the migration

```bash
php artisan make:migration create_expense_reports_table
```

```php
Schema::create('expense_reports', function (Blueprint $table): void {
    $table->id();
    $table->foreignId('company_id')->constrained()->cascadeOnDelete();
    $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
    $table->string('title');
    $table->text('description')->nullable();
    $table->decimal('amount', 15, 2)->default(0);
    $table->string('status')->default('draft'); // draft | pending_approval | approved | rejected | canceled
    $table->timestamps();
});
```

### 2. Create the model

```php
// app/Models/ExpenseReport.php
class ExpenseReport extends Model implements Approvable
{
    protected $fillable = ['company_id', 'requester_id', 'title', 'description', 'amount', 'status'];
    protected function casts(): array { return ['amount' => 'decimal:2']; }

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function requester(): BelongsTo { return $this->belongsTo(User::class, 'requester_id'); }

    // --- Approvable contract (9 methods) — see Step 1 for full implementation ---
    public function getApprovalTitle(): string { return "Expense Report #{$this->id}: {$this->title}"; }
    public function getApprovalUrl(): string { return route('expense-reports.show', $this->id); }
    public function getApprovalRequester(): User { return $this->requester; }
    public function getApprovalCompany(): Company { return $this->company; }
    public function getApprovalPreview(): ?array { return ['view' => 'expense-reports._approval-preview', 'data' => ['expenseReport' => $this]]; }
    public function onApprovalCompleted(ApprovalRequest $request): void { $this->update(['status' => 'approved']); }
    public function onApprovalRejected(ApprovalRequest $request): void { $this->update(['status' => 'rejected']); }
    public function onApprovalCanceled(ApprovalRequest $request): void { $this->update(['status' => 'canceled']); }
    public static function getApprovalTypeLabel(): string { return 'Expense Report'; }

    public function approvalRequests(): MorphMany { return $this->morphMany(ApprovalRequest::class, 'approvable'); }
    public function isPendingApproval(): bool { return $this->approvalRequests()->where('status', ApprovalStatus::Pending)->exists(); }
}
```

### 3. Create the controller

```php
// app/Http/Controllers/ExpenseReportController.php
class ExpenseReportController extends Controller
{
    public function __construct(private readonly ApprovalEngine $engine) {}

    // index: list all for active company
    public function index(): View
    {
        $company = auth()->user()?->getActiveCompany();
        if (! $company) abort(403);
        $reports = ExpenseReport::with(['requester', 'approvalRequests'])
            ->where('company_id', $company->id)->orderBy('created_at', 'desc')->get();
        return view('expense-reports.index', ['expenseReports' => $reports]);
    }

    // create: show form
    public function create(): View { return view('expense-reports.create'); }

    // store: persist new record
    public function store(Request $request): RedirectResponse
    {
        $company = auth()->user()?->getActiveCompany();
        if (! $company) abort(403);
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'amount' => ['required', 'numeric', 'min:0'],
        ]);
        $report = ExpenseReport::create([
            'company_id' => $company->id, 'requester_id' => auth()->id(),
            'title' => $validated['title'], 'description' => $validated['description'],
            'amount' => $validated['amount'], 'status' => 'draft',
        ]);
        return redirect()->route('expense-reports.show', $report->id)->with('success', 'Expense report created.');
    }

    // show: detail view with templates for submission
    public function show(ExpenseReport $expenseReport): View
    {
        $company = auth()->user()?->getActiveCompany();
        if (! $company || $expenseReport->company_id !== $company->id) abort(403);
        $expenseReport->load(['requester', 'approvalRequests.steps' => fn ($q) => $q->with(['approver', 'delegatedFrom'])]);
        $templates = WorkflowTemplate::availableFor($company)->where('is_active', true)
            ->where(fn ($q) => $q->where('approvable_type', ExpenseReport::class)->orWhereNull('approvable_type'))->get();
        $latestRequest = $expenseReport->approvalRequests->last();
        return view('expense-reports.show', ['expenseReport' => $expenseReport, 'templates' => $templates, 'latestRequest' => $latestRequest]);
    }

    // previewWorkflow: AJAX endpoint for workflow diagram before submission
    public function previewWorkflow(ExpenseReport $expenseReport, Request $request): View
    {
        $template = WorkflowTemplate::findOrFail($request->integer('template_id'));
        return view('approvals._workflow-diagram', ['preview' => $this->engine->preview($expenseReport, $template)]);
    }

    // submitForApproval: submit to the approval engine
    public function submitForApproval(ExpenseReport $expenseReport, Request $request): RedirectResponse
    {
        if ($expenseReport->requester_id !== auth()->id()) abort(403);
        if ($expenseReport->status !== 'draft') return back()->with('error', 'This expense report cannot be submitted.');
        $template = WorkflowTemplate::findOrFail($request->integer('template_id'));
        try { $this->engine->submit($expenseReport, $template); }
        catch (RuntimeException $e) { return back()->with('error', 'Cannot submit: '.$e->getMessage()); }
        $expenseReport->update(['status' => 'pending_approval']);
        return redirect()->route('expense-reports.show', $expenseReport->id)->with('success', 'Expense report submitted.');
    }
}
```

### 4. Create Blade views

**`resources/views/expense-reports/index.blade.php`:**

```blade
@extends('layouts.app')
@section('title', 'Expense Reports')
@section('content')
<div x-data="{ pageName: 'Expense Reports' }">@include('partials.breadcrumb')</div>
<div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
    <div class="flex flex-col gap-3 mb-6 sm:flex-row sm:items-center sm:justify-between">
        <div><h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Expense Reports</h3></div>
        <a href="{{ route('expense-reports.create') }}" class="...btn-primary...">+ Create Report</a>
    </div>
    @if ($expenseReports->isEmpty())
        <p class="text-center text-gray-500">No expense reports yet.</p>
    @else
        <table class="min-w-full">
            <thead><tr>...cols: Title, Amount, Status, Created, Action...</tr></thead>
            <tbody>
                @foreach ($expenseReports as $report)
                    <tr>
                        <td><a href="{{ route('expense-reports.show', $report->id) }}">{{ $report->title }}</a></td>
                        <td>${{ number_format((float) $report->amount, 2) }}</td>
                        <td>{{-- Status badge: draft/pending_approval/approved/rejected --}}</td>
                        <td>{{ $report->created_at->format('M d, Y') }}</td>
                        <td><a href="{{ route('expense-reports.show', $report->id) }}" class="...">View</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
```

**`resources/views/expense-reports/create.blade.php`:**
- Standard form with Title, Amount, Description fields
- POST to `route('expense-reports.store')`

**`resources/views/expense-reports/show.blade.php`:**
```blade
{{-- Must include flash message rendering --}}
@if (session('error')) <div class="...bg-error-50...">{{ session('error') }}</div> @endif
@if (session('success')) <div class="...bg-success-50...">{{ session('success') }}</div> @endif

{{-- Header with title, requester, date, status badge --}}
{{-- Details: amount, status, description --}}

{{-- Approval progress (if $latestRequest exists) --}}
@if ($latestRequest)
    @include('approvals._workflow-progress-table', ['approvalRequest' => $latestRequest])
@endif

{{-- Submit modal (if draft and owned by current user) --}}
@if ($report->status === 'draft' && $report->requester_id === auth()->id())
    @include('approvals._submit-modal', [
        'approvable' => $report,
        'submitRoute' => route('expense-reports.submit-for-approval', $report->id),
        'templates' => $templates,
        'previewRoute' => route('expense-reports.preview-workflow', $report->id),
    ])
@endif
```

**`resources/views/expense-reports/_approval-preview.blade.php`:**
```blade
<div class="grid grid-cols-2 gap-3 text-sm">
    <div><p class="text-xs text-gray-400">Title</p><p class="font-medium">{{ $expenseReport->title }}</p></div>
    <div><p class="text-xs text-gray-400">Amount</p><p class="font-medium">${{ number_format((float) $expenseReport->amount, 2) }}</p></div>
    @if ($expenseReport->description) <div class="col-span-2"><p class="text-xs text-gray-400">Description</p><p>{{ $expenseReport->description }}</p></div> @endif
    <div class="col-span-2"><p class="text-xs text-gray-400">Requested by</p><p>{{ $expenseReport->requester->name }}</p></div>
</div>
```

### 5. Register routes

```php
// routes/web.php
use App\Http\Controllers\ExpenseReportController;

// Inside auth middleware group:
Route::middleware('auth')->prefix('expense-reports')->name('expense-reports.')->group(function () {
    Route::get('/', [ExpenseReportController::class, 'index'])->name('index');
    Route::get('/create', [ExpenseReportController::class, 'create'])->name('create');
    Route::post('/', [ExpenseReportController::class, 'store'])->name('store');
    Route::get('/{expenseReport}', [ExpenseReportController::class, 'show'])->name('show');
    Route::get('/{expenseReport}/preview-workflow', [ExpenseReportController::class, 'previewWorkflow'])->name('preview-workflow');
    Route::post('/{expenseReport}/submit-for-approval', [ExpenseReportController::class, 'submitForApproval'])->name('submit-for-approval');
});
```

### 6. Add sidebar entry

```blade
{{-- resources/views/partials/sidebar.blade.php — inside an appropriate group --}}
<li>
    <a href="{{ route('expense-reports.index') }}"
        class="menu-item group {{ Request::is('expense-reports*') ? 'menu-item-active' : 'menu-item-inactive' }}">
        <span class="flex items-center gap-3">
            {{-- SVG icon --}}
            <span class="menu-item-text" x-show="!sidebarToggle || sidebarHover" x-cloak>Expense Reports</span>
        </span>
    </a>
</li>
```

### 7. Create a workflow template in Admin UI

Go to **Admin → Workflow Templates → Create**. Set "Applies To" to your module's label (auto-discovered from `getApprovalTypeLabel()`). The label appears automatically — no code changes needed to the helper or the admin form.

---

## Checklist: Adding Approval to a New Module

- [ ] Model implements `App\Contracts\Approvable` (9 methods: 5 identity + 3 lifecycle + 1 static type label)
- [ ] Model has `approvalRequests()` morphMany relationship
- [ ] Model has `isPendingApproval()` helper
- [ ] Controller has `show()` method that fetches type-filtered templates via `availableFor()`
- [ ] Controller has `previewWorkflow()` method for AJAX preview
- [ ] Controller has `submitForApproval()` with try/catch around `$engine->submit()`
- [ ] Routes registered: index, create, store, show, preview-workflow, submit-for-approval
- [ ] Show view renders `session('error')` and `session('success')` flash messages
- [ ] Show view includes `approvals._submit-modal` partial
- [ ] Show view includes `approvals._workflow-progress-table` partial
- [ ] Index view shows status badges for draft/pending_approval/approved/rejected
- [ ] No per-module event listener needed — `UpdateApprovableStatus` handles all modules
- [ ] Workflow template configured in Admin > Workflow Templates with matching `approvable_type`
- [ ] Sidebar nav entry added for the module
