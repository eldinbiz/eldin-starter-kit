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

| Layer | Component | Location |
|-------|-----------|----------|
| Contract | `Approvable` interface | `app/Contracts/Approvable.php` |
| Engine | `ApprovalEngine` service | `app/Services/ApprovalEngine.php` |
| Templates | Admin-configured workflow definitions | `Admin > Workflow Templates` |
| Inbox | Approvers act on pending steps | `/approvals/inbox` |
| Tracking | Requestors monitor submissions | `/approvals/requests` |
| Events | 6 lifecycle events | `app/Events/Approval/*` |

Any model implementing `Approvable` becomes "approvable" — the approval pipeline handles the rest.

---

## Step 1: Implement the `Approvable` Contract

Your model must implement all 5 methods. Here is a complete example:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\Approvable;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class PurchaseRequest extends Model implements Approvable
{
    public function getApprovalTitle(): string
    {
        return "Purchase Request #{$this->id}";
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

    public function getApprovalPreview(): ?array
    {
        // Optional: return a Blade partial that renders a read-only summary
        // inside the approval detail page. Return null for simple title+link.
        return [
            'view' => 'purchase-requests._approval-preview',
            'data' => ['purchaseRequest' => $this],
        ];
    }

    // --- Helper: define a polymorphic relationship for convenience ---

    public function approvalRequests(): MorphMany
    {
        return $this->morphMany(
            \App\Models\Approval\ApprovalRequest::class,
            'approvable',
        );
    }

    // Status helpers:
    public function isPendingApproval(): bool
    {
        return $this->approvalRequests()
            ->where('status', \App\Enums\ApprovalStatus::Pending)
            ->exists();
    }

    public function isApproved(): bool
    {
        return $this->approvalRequests()
            ->where('status', \App\Enums\ApprovalStatus::Approved)
            ->exists();
    }
}
```

### Method reference

| Method | Return | Required | Notes |
|--------|--------|----------|-------|
| `getApprovalTitle()` | `string` | Yes | Shown in inbox, requests list, and notification emails |
| `getApprovalUrl()` | `string` | Yes | "View Full" link in the approval detail page |
| `getApprovalRequester()` | `User` | Yes | Used to resolve N+1 approver |
| `getApprovalCompany()` | `Company` | Yes | Scopes to the correct company context |
| `getApprovalPreview()` | `?array` | No | Return `['view' => '...', 'data' => [...]]` or `null` |

---

## Step 2: Find the Appropriate Workflow Template

Templates are per-company and can be filtered by `approvable_type`. Find the right one:

```php
use App\Models\Approval\WorkflowTemplate;

$template = WorkflowTemplate::where('company_id', $company->id)
    ->where('approvable_type', PurchaseRequest::class) // Optional: type-specific
    ->where('is_active', true)
    ->first();

// Or: a template that applies to any approvable type:
$template = WorkflowTemplate::where('company_id', $company->id)
    ->whereNull('approvable_type')
    ->where('is_active', true)
    ->first();
```

**If multiple templates match**, present the user with a dropdown to choose. **If no template is configured**, redirect with an error message asking the admin to create one.

---

## Step 3: Submit the Approval Request

Call the engine from your module's controller:

```php
use App\Services\ApprovalEngine;
use App\Models\Approval\WorkflowTemplate;

class PurchaseRequestController extends Controller
{
    public function submitForApproval(PurchaseRequest $purchaseRequest, ApprovalEngine $engine): RedirectResponse
    {
        // Guard: ensure the user is the owner
        if ($purchaseRequest->getApprovalRequester()->id !== auth()->id()) {
            abort(403);
        }

        // Guard: prevent duplicate submissions
        if ($purchaseRequest->isPendingApproval()) {
            return back()->with('error', 'This purchase request is already pending approval.');
        }

        // Find the template (or let the user select from the request)
        $template = WorkflowTemplate::where('company_id', $purchaseRequest->getApprovalCompany()->id)
            ->where('is_active', true)
            ->firstOrFail();

        // Submit — the engine does everything else
        $engine->submit($purchaseRequest, $template);

        // Update the model's local status
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
  - `N+1_of_previous` → previous step approver's manager (runtime, not at submission)
- Creates `approval_requests` + `approval_request_steps` rows
- Dispatches `ApprovalRequested` event (triggers notifications via the Notification module)

---

## Step 4: Listen for Lifecycle Events

Register event listeners in your module's service provider or in a dedicated listener class.

### Events to listen for

| Event | Fired When | What To Do |
|-------|-----------|------------|
| `ApprovalRequested` | Request created | Log it; display status badge (handled by notification module) |
| `ApprovalStepCompleted` | One step approved | *(usually just notification — no action needed)* |
| **`ApprovalCompleted`** | **All groups complete** | **Execute the pending transaction!** |
| `ApprovalRejected` | Any step rejected | Update model status to "rejected"; notify requestor |
| `ApprovalCanceled` | Requestor cancels | Update model status to "canceled" |
| `ApprovalDelegated` | Approver reassigned | *(notification only)* |

### Listener example

```php
<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\Approval\ApprovalCompleted;
use App\Models\PurchaseRequest;

class ExecutePurchaseRequest
{
    public function handle(ApprovalCompleted $event): void
    {
        $approvable = $event->approvalRequest->getApprovable();

        if (! $approvable instanceof PurchaseRequest) {
            return; // Not our concern
        }

        // Execute the pending transaction
        $approvable->update(['status' => 'approved']);

        // e.g., create a Purchase Order, send to vendor, etc.
        // Order::createFromPurchaseRequest($approvable);
    }
}
```

Register the listener in `app/Providers/AppServiceProvider.php` or in an EventServiceProvider:

```php
use App\Events\Approval\ApprovalCompleted;
use App\Listeners\ExecutePurchaseRequest;

Event::listen(ApprovalCompleted::class, ExecutePurchaseRequest::class);
```

---

## Step 5: Add Approval Status to the Module's View

### Status badge (in your module's show/index pages)

```blade
@php
    $latestRequest = $purchaseRequest->approvalRequests->last();
@endphp

@if ($latestRequest)
    @if ($latestRequest->status === \App\Enums\ApprovalStatus::Pending)
        <span class="inline-flex items-center gap-1 rounded-full bg-warning-50 px-2.5 py-0.5 text-xs font-medium text-warning-600 dark:bg-warning-500/10 dark:text-warning-400">
            Pending Approval
        </span>
    @elseif ($latestRequest->status === \App\Enums\ApprovalStatus::Approved)
        <span class="inline-flex items-center gap-1 rounded-full bg-success-50 px-2.5 py-0.5 text-xs font-medium text-success-600 dark:bg-success-500/10 dark:text-success-500">
            Approved
        </span>
    @elseif ($latestRequest->status === \App\Enums\ApprovalStatus::Rejected)
        <span class="inline-flex items-center gap-1 rounded-full bg-error-50 px-2.5 py-0.5 text-xs font-medium text-error-600 dark:bg-error-500/10 dark:text-error-400">
            Rejected
        </span>
    @endif
@endif
```

### "Submit for Approval" button

```blade
@if (! $purchaseRequest->isPendingApproval() && ! $purchaseRequest->isApproved())
    <form action="{{ route('purchase-requests.submit-for-approval', $purchaseRequest) }}" method="POST">
        @csrf
        <button type="submit"
            class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">
            Submit for Approval
        </button>
    </form>
@endif
```

### "View Approval" link (to the approval detail page)

```blade
@if ($latestRequest)
    <a href="{{ route('approvals.show', $latestRequest->id) }}"
        class="text-brand-500 hover:text-brand-600 text-sm">
        View Approval Progress
    </a>
@endif
```

---

## Step 6: Routes for Submission

Add a route in your module for the submission action:

```php
// routes/web.php — inside the auth group
Route::post('/purchase-requests/{purchaseRequest}/submit-for-approval',
    [PurchaseRequestController::class, 'submitForApproval']
)->name('purchase-requests.submit-for-approval');
```

---

## Resolver Types Reference

When configuring workflow templates in the admin UI, these are the available resolver types:

| Type | Label in UI | Who Becomes the Approver |
|------|-------------|--------------------------|
| `N+1` | N+1 (Manager) | The requestor's direct manager within the company |
| `specific_user` | Specific User | A named user selected from the company or any ancestor company |
| `N+1_of_previous` | N+1 of Previous | The direct manager of the previous step's approver (resolved at runtime) |

---

## Key Files Reference

| File | Purpose |
|------|---------|
| `app/Contracts/Approvable.php` | Contract your module's model implements |
| `app/Services/ApprovalEngine.php` | Core engine: `submit()`, `approve()`, `reject()`, `cancel()`, `delegate()` |
| `app/Enums/ApprovalStatus.php` | `Pending`, `Approved`, `Rejected`, `Canceled` |
| `app/Enums/StepState.php` | `Pending`, `Approved`, `Rejected` (per-step) |
| `app/Enums/ResolverType.php` | `NPlus1`, `SpecificUser`, `NPlus1OfPrevious` |
| `app/Enums/GroupType.php` | `Sequential`, `Parallel` |
| `app/Models/Approval/ApprovalRequest.php` | Polymorphic request model — uses `morphs('approvable')` |
| `app/Models/Approval/ApprovalRequestStep.php` | Per-step runtime state; `display_label` accessor |
| `app/Events/Approval/*` | 6 lifecycle events |

---

## Checklist: Adding Approval to a New Module

- [ ] Model implements `App\Contracts\Approvable` (5 methods)
- [ ] Model has `approvalRequests()` morphMany relationship
- [ ] Controller has `submitForApproval()` method calling `ApprovalEngine::submit()`
- [ ] Route registered for the submit action
- [ ] Event listener registered for `ApprovalCompleted` (to execute the pending transaction)
- [ ] Event listener registered for `ApprovalRejected` (to update model status)
- [ ] Status badge shown in the module's view (show/index)
- [ ] "Submit for Approval" button shown on the module's detail page
- [ ] "View Approval Progress" link to `approvals.show` route
- [ ] Workflow template configured in Admin > Workflow Templates
