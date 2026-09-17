# Masterclass Tutorial: Building a Complete Domain Module

A comprehensive, production-ready developer guide for building a business domain module in this multi-tenant starter kit.

In this tutorial, we will build an end-to-end **Purchase Request Management** module (`app-modules/purchase-requests/`) and integrate it with **all core platform subsystems**:

```
                               ┌────────────────────────────────────────────────────────┐
                               │               Domain Module: purchase-requests         │
                               └───────────────────────────┬────────────────────────────┘
                                                           │
        ┌──────────────────┬──────────────────┬────────────┴───────┬──────────────────┬──────────────────┐
        ▼                  ▼                  ▼                    ▼                  ▼                  ▼
┌──────────────┐   ┌──────────────┐   ┌──────────────┐     ┌──────────────┐   ┌──────────────┐   ┌──────────────┐
│Multi-Tenancy │   │Feature Toggle│   │ApprovalEngine│     │Notifications │   │Livewire v4 UI│   │REST API v1   │
│(company_id)  │   │(@feature)    │   │(Approvable)  │     │(Email + Bell)│   │(Wizard & SPA)│   │(Scramble Docs)
└──────────────┘   └──────────────┘   └──────────────┘     └──────────────┘   └──────────────┘   └──────────────┘
```

---

## Table of Contents
1. [Architecture & Subsystem Map](#1-architecture--subsystem-map)
2. [UI & UX Wireframes (ASCII Mockups)](#2-ui--ux-wireframes-ascii-mockups)
   - [2.1 Index & Filtering Dashboard (`/purchase-requests`)](#21-index--filtering-dashboard-purchase-requests)
   - [2.2 Interactive 3-Step Creation Wizard (`/purchase-requests/create`)](#22-interactive-3-step-creation-wizard-purchase-requestscreate)
   - [2.3 Detail & Approval Stepper View (`/purchase-requests/{id}`)](#23-detail--approval-stepper-view-purchase-requestsid)
   - [2.4 Central Approval Inbox Preview Partial (`_approval-preview.blade.php`)](#24-central-approval-inbox-preview-partial-_approval-previewbladephp)
3. [Step 1: Module Scaffolding & Composer Linking](#step-1-module-scaffolding--composer-linking)
4. [Step 2: Database Schema, Multi-Tenant Model & Seeder](#step-2-database-schema-multi-tenant-model--seeder)
5. [Step 3: Centralized Feature Toggle Integration](#step-3-centralized-feature-toggle-integration)
6. [Step 4: 3-Layer Domain Business Actions](#step-4-3-layer-domain-business-actions)
7. [Step 5: Polymorphic Approval Workflow Engine Integration](#step-5-polymorphic-approval-workflow-engine-integration)
8. [Step 6: Notifications & Dynamic Email Engine Integration](#step-6-notifications--dynamic-email-engine-integration)
9. [Step 7: Complete Reactive Web SPA UI (Livewire v4 & Blade)](#step-7-complete-reactive-web-spa-ui-livewire-v4--blade)
   - [7.1 Web Controller (`PurchaseRequestController.php`)](#71-web-controller-purchaserequestcontrollerphp)
   - [7.2 Index Page & Livewire DataTable SFC](#72-index-page--livewire-datatable-sfc)
   - [7.3 Create Page & 3-Step Livewire Wizard SFC](#73-create-page--3-step-livewire-wizard-sfc)
   - [7.4 Document Detail & Approval Stepper View (`show.blade.php`)](#74-document-detail--approval-stepper-view-showbladephp)
10. [Step 8: Machine-to-Machine REST API & OpenAPI 3.1 Documentation](#step-8-machine-to-machine-rest-api--openapi-31-documentation)
11. [Step 9: Comprehensive Automated Testing with Pest 5](#step-9-comprehensive-automated-testing-with-pest-5)
12. [Step 10: End-to-End Scaffolding Checklist](#step-10-end-to-end-scaffolding-checklist)

---

## 1. Architecture & Subsystem Map

The modular monolith keeps domain code strictly isolated in `app-modules/<name>/`:

```
app-modules/purchase-requests/
├── composer.json                          # Package definition (Modules\PurchaseRequests\)
├── routes/
│   └── purchase-requests-routes.php       # Web and /api/v1/ routes
├── database/
│   ├── migrations/                        # Multi-tenant purchase_requests table
│   ├── factories/                         # PurchaseRequestFactory.php
│   └── seeders/                           # PurchaseRequestSeeder.php
├── resources/
│   ├── views/
│   │   ├── index.blade.php                # Livewire DataTable host page
│   │   ├── create.blade.php               # Livewire Wizard host page
│   │   ├── show.blade.php                 # Details & Approval timeline
│   │   └── _approval-preview.blade.php    # Embedded card inside /approvals
│   └── components/
│       └── purchase-requests/
│           ├── purchase-request-wizard.blade.php # Livewire v4 SFC Wizard
│           └── purchase-request-table.blade.php  # Livewire v4 SFC DataTable
├── src/
│   ├── Actions/                           # 3-Layer Domain Actions
│   │   ├── CreatePurchaseRequestAction.php
│   │   ├── SubmitPurchaseRequestAction.php
│   │   └── CancelPurchaseRequestAction.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── PurchaseRequestController.php
│   │   │   └── Api/v1/PurchaseRequestController.php
│   │   ├── Requests/Api/v1/StorePurchaseRequestRequest.php
│   │   └── Resources/Api/v1/PurchaseRequestResource.php
│   ├── Models/
│   │   └── PurchaseRequest.php            # Implements App\Contracts\Approvable
│   └── Providers/
│       └── PurchaseRequestsServiceProvider.php
└── tests/
    └── Feature/
        ├── PurchaseRequestTest.php
        ├── PurchaseRequestWizardTest.php
        └── PurchaseRequestApiTest.php
```

---

## 2. UI & UX Wireframes (ASCII Mockups)

Below are the ASCII box-drawing wireframes for all key views in the Purchase Requests domain module.

### 2.1 Index & Filtering Dashboard (`/purchase-requests`)

```text
┌──────────────────────────────────────────────────────────────────────────────────────────────────┐
│  TOPBAR: [Active Company: Acme Corp ▼]   [🔍 Search...]   [🔔 (3)]   [Admin User ▼]              │
├─────────────────┬────────────────────────────────────────────────────────────────────────────────┤
│  SIDEBAR        │  PURCHASE REQUESTS DASHBOARD                                                   │
│                 │                                                                                │
│  ─ Dashboard    │  ┌──────────────────┐ ┌──────────────────┐ ┌──────────────────┐ ┌────────────┐  │
│  ─ Users        │  │ Total Requisitions│ │ Pending Approval │ │ Approved (Month) │ │ Budget Left│  │
│  ─ Companies    │  │ 24                │ │ 5                │ │ $42,500.00       │ │ $18,400.00 │  │
│  ─ Approvals    │  └──────────────────┘ └──────────────────┘ └──────────────────┘ └────────────┘  │
│  ▼ Requisitions │                                                                                │
│    • Purchase   │  ┌──────────────────────────────────────────────────────────────────────────┐  │
│      Requests   │  │  SEARCH & FILTER BAR (bg: white dark:bg-gray-800, p-4, rounded-xl)       │  │
│    • Invoices   │  │  [🔍 Search PR #, Title, Requester... (wire:model.live.debounce.300ms)]  │  │
│                 │  │  Status: [ All Statuses ▼ ]   Dept: [ All Departments ▼ ]                │  │
│                 │  │                                          [+ New Purchase Request (SPA)]  │  │
│                 │  └──────────────────────────────────────────────────────────────────────────┘  │
│                 │                                                                                │
│                 │  ┌──────────────────────────────────────────────────────────────────────────┐  │
│                 │  │  LIVEWIRE DATA TABLE (purchase-request-table.blade.php)                  │  │
│                 │  │  ┌──────────────┬──────────────────┬────────────┬──────────┬──────────┐  │  │
│                 │  │  │ PR Number ⇅  │ Title            │ Requester  │ Amount   │ Status   │  │  │
│                 │  │  ├──────────────┼──────────────────┼────────────┼──────────┼──────────┤  │  │
│                 │  │  │ PR-202609-001│ Mac Studio M3 Max│ Alice Chen │ $3,999.00│ PENDING  │  │  │
│                 │  │  │ PR-202609-002│ AWS Cloud Credits│ John Doe   │ $1,250.00│ APPROVED │  │  │
│                 │  │  │ PR-202609-003│ Office Supplies  │ Sarah Lee  │   $180.00│ DRAFT    │  │  │
│                 │  │  └──────────────┴──────────────────┴────────────┴──────────┴──────────┘  │  │
│                 │  │  Showing 1 to 3 of 24 results                  [◀ Prev] [1] [2] [Next ▶]  │  │
│                 │  └──────────────────────────────────────────────────────────────────────────┘  │
└─────────────────┴────────────────────────────────────────────────────────────────────────────────┘
```

---

### 2.2 Interactive 3-Step Creation Wizard (`/purchase-requests/create`)

```text
┌──────────────────────────────────────────────────────────────────────────────────────────────────┐
│  CREATE PURCHASE REQUEST WIZARD (Livewire SFC: purchase-request-wizard.blade.php)                │
│                                                                                                  │
│  STEP INDICATOR:                                                                                 │
│   (1) Basic Information  ────▶  (2) Dynamic Line Items  ────▶  (3) Approval Workflow & Submit     │
│   [● Completed]                 [● Active]                     [○ Pending]                       │
│                                                                                                  │
│  ┌────────────────────────────────────────────────────────────────────────────────────────────┐  │
│  │  STEP 2: ADD LINE ITEMS & BUDGET ALLOCATION                                                │  │
│  │                                                                                            │  │
│  │  ┌─────────────────────────────────┬──────────┬──────────────┬──────────────┬───────────┐  │  │
│  │  │ Item Description                │ Quantity │ Unit Price   │ Total Amount │ Action    │  │  │
│  │  ├─────────────────────────────────┼──────────┼──────────────┼──────────────┼───────────┤  │  │
│  │  │ [Dell 32" 4K Monitor          ] │ [  2   ] │ [ $ 650.00 ] │ $ 1,300.00   │ [🗑️ Delete]│  │  │
│  │  │ [Ergonomic Standing Desk       ] │ [  1   ] │ [ $ 850.00 ] │ $   850.00   │ [🗑️ Delete]│  │  │
│  │  └─────────────────────────────────┴──────────┴──────────────┴──────────────┴───────────┘  │  │
│  │                                                                                            │  │
│  │  [+ Add Item Row (wire:click="addLineItem")]                                               │  │
│  │                                                                                            │  │
│  │  ────────────────────────────────────────────────────────────────────────────────────────  │  │
│  │  Summary:  Subtotal: $2,150.00  |  Est. Tax (10%): $215.00  |  GRAND TOTAL: $2,365.00      │  │
│  │                                                                                            │  │
│  │  [◀ Back to Step 1]                                           [Proceed to Approval Step ▶] │  │
│  └────────────────────────────────────────────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────────────────────────────────────────┘
```

---

### 2.3 Detail & Approval Stepper View (`/purchase-requests/{id}`)

```text
┌──────────────────────────────────────────────────────────────────────────────────────────────────┐
│  PURCHASE REQUEST: PR-202609-001                                                                 │
│  Title: Ergonomic Workstations & Monitors                                                        │
├──────────────────────────────────────────────────────────────────────────────────────────────────┤
│  STATUS BAR: [ PENDING APPROVAL (bg: amber-500 text-white) ]         Submitted: Sep 01, 2026    │
│                                                                                                  │
│  ┌────────────────────────────────────────────────────────────────────────────────────────────┐  │
│  │  APPROVAL WORKFLOW TIMELINE (Polymorphic Approval Engine)                                  │  │
│  │                                                                                            │  │
│  │  ┌──────────────┐       ┌──────────────┐       ┌──────────────┐       ┌──────────────┐     │  │
│  │  │ 1. Direct Mgr│  ───▶ │ 2. Dept Head │  ───▶ │ 3. Finance   │  ───▶ │ 4. Finalized │     │  │
│  │  │ [✓ Approved] │       │ [⏳ Pending] │       │ [○ Queued]   │       │ [○ Pending]  │     │  │
│  │  │ By: Alex Wong│       │ By: HR/Eng   │       │ By: CFO Team │       │ Auto-Issue PO│     │  │
│  │  └──────────────┘       └──────────────┘       └──────────────┘       └──────────────┘     │  │
│  └────────────────────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                                  │
│  LINE ITEMS BREAKDOWN:                                                                           │
│  1. Dell 32" 4K Monitor (x2) ...................................................... $1,300.00    │
│  2. Ergonomic Standing Desk (x1) ................................................... $850.00    │
│  ---------------------------------------------------------------------------------------------   │
│  Total Request Value: $2,150.00                                                                  │
│                                                                                                  │
│  ACTIONS: [🖨️ Print PDF]   [✉️ Resend Notification]   [🚫 Cancel Request (x-data modal)]        │
└──────────────────────────────────────────────────────────────────────────────────────────────────┘
```

---

### 2.4 Central Approval Inbox Preview Partial (`_approval-preview.blade.php`)

```text
┌──────────────────────────────────────────────────────────────────────────────────────────────────┐
│  CENTRAL APPROVALS INBOX (`/approvals`) — Embedded Approvable Card                               │
│                                                                                                  │
│  ┌────────────────────────────────────────────────────────────────────────────────────────────┐  │
│  │  [📦 Purchase Request]   PR-202609-001: Ergonomic Workstations & Monitors                 │  │
│  │  Requester: Alice Chen (Engineering)  •  Submitted: 2 hours ago  •  Company: Acme Corp     │  │
│  │  ────────────────────────────────────────────────────────────────────────────────────────  │  │
│  │  Total Amount: $2,150.00  |  Line Items: 2 items (Monitors, Standing Desk)                 │  │
│  │                                                                                            │  │
│  │  [👁️ View Full Details (wire:navigate)]         [✗ Reject (Modal)]   [✓ Approve Requisition]│  │
│  └────────────────────────────────────────────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────────────────────────────────────────┘
```

---

## Step 1: Module Scaffolding & Composer Linking

### 1. Generate the Module Skeleton
Run the Artisan `make:module` command:
```bash
php artisan make:module purchase-requests
```

### 2. Link Composer Path Repository (MANDATORY)
Update the root `composer.json` link so that Composer registers the `Modules\PurchaseRequests\` namespace:

```bash
composer update modules/purchase-requests
# Or inside Docker:
docker compose exec app composer update modules/purchase-requests
```

---

## Step 2: Database Schema, Multi-Tenant Model & Seeder

### 1. Create Model, Migration, and Factory
```bash
php artisan make:model PurchaseRequest -mfc --module=purchase-requests
```

### 2. Database Migration (`database/migrations/...`)
Open the generated migration file and define the multi-tenant schema:

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            $table->string('pr_number', 50)->unique();
            $table->string('title');
            $table->string('department', 100);
            $table->text('description')->nullable();
            $table->decimal('total_amount', 12, 2)->default(0.00);
            $table->string('status', 30)->default('draft'); // draft, pending_approval, approved, rejected, canceled
            $table->json('line_items')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_requests');
    }
};
```

Run the migration:
```bash
php artisan migrate
```

---

## Step 3: Centralized Feature Toggle Integration

Register the module in the centralized feature flags system to allow runtime enabling/disabling per company.

### 1. Register in `config/features.php`
Add the feature entry:

```php
'purchase-requests' => [
    'label' => 'Purchase Requests',
    'description' => 'Requisition management and multi-step approval workflows.',
    'default_enabled' => true,
    'bypass_permissions' => ['manage-purchase-requests'],
    'setting_key' => 'feature_purchase_requests',
],
```

### 2. Sidebar Link with `@feature`
Open `resources/views/partials/sidebar.blade.php`:

```blade
@feature('purchase-requests')
    <li>
        <a
            href="{{ route('purchase-requests.index') }}"
            wire:navigate
            class="menu-item group {{ Request::is('purchase-requests*') ? 'menu-item-active' : 'menu-item-inactive' }}"
        >
            <x-icons.document-text class="w-5 h-5" />
            <span>Purchase Requests</span>
        </a>
    </li>
@endfeature
```

---

## Step 4: 3-Layer Domain Business Actions

All state mutations belong inside single-purpose **Domain Actions** inside `app-modules/purchase-requests/src/Actions/`.

### 1. `CreatePurchaseRequestAction.php`
Create `app-modules/purchase-requests/src/Actions/CreatePurchaseRequestAction.php`:

```php
<?php

declare(strict_types=1);

namespace Modules\PurchaseRequests\Actions;

use Illuminate\Support\Facades\DB;
use Modules\PurchaseRequests\Models\PurchaseRequest;

final class CreatePurchaseRequestAction
{
    /**
     * @param array{
     *     company_id: int,
     *     requester_id: int,
     *     title: string,
     *     department: string,
     *     description?: string|null,
     *     total_amount: float|numeric-string,
     *     line_items?: array<int, array{description: string, quantity: int, unit_price: float}>|null
     * } $data
     */
    public function execute(array $data): PurchaseRequest
    {
        return DB::transaction(function () use ($data): PurchaseRequest {
            $yearMonth = date('Ym');
            $count = PurchaseRequest::where('company_id', $data['company_id'])->count() + 1;
            $prNumber = sprintf('PR-%s-%04d', $yearMonth, $count);

            return PurchaseRequest::create([
                'company_id' => $data['company_id'],
                'requester_id' => $data['requester_id'],
                'pr_number' => $prNumber,
                'title' => $data['title'],
                'department' => $data['department'],
                'description' => $data['description'] ?? null,
                'total_amount' => $data['total_amount'],
                'line_items' => $data['line_items'] ?? [],
                'status' => 'draft',
            ]);
        });
    }
}
```

---

## Step 5: Polymorphic Approval Workflow Engine Integration

Connect the `PurchaseRequest` model to the polymorphic approval workflow engine.

### 1. Implement `App\Contracts\Approvable`
Update `app-modules/purchase-requests/src/Models/PurchaseRequest.php`:

```php
<?php

declare(strict_types=1);

namespace Modules\PurchaseRequests\Models;

use App\Contracts\Approvable;
use App\Models\Approval\ApprovalRequest;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class PurchaseRequest extends Model implements Approvable
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'line_items' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function approvalRequests(): MorphMany
    {
        return $this->morphMany(ApprovalRequest::class, 'approvable');
    }

    // === Approvable Contract Implementation ===

    public function getApprovalTitle(): string
    {
        return "{$this->pr_number}: {$this->title}";
    }

    public function getApprovalUrl(): string
    {
        return route('purchase-requests.show', $this->id);
    }

    public function getApprovalRequester(): User
    {
        return $this->requester;
    }

    public function getApprovalCompany(): Company
    {
        return $this->company;
    }

    public function getApprovalPreview(): ?array
    {
        return [
            'view' => 'purchase-requests::_approval-preview',
            'data' => ['purchaseRequest' => $this],
        ];
    }

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

    public static function getApprovalTypeLabel(): string
    {
        return 'Purchase Request';
    }

    public function isPendingApproval(): bool
    {
        return $this->status === 'pending_approval';
    }
}
```

### 2. Create the Embedded Approval Preview Partial
Create `app-modules/purchase-requests/resources/views/_approval-preview.blade.php`:

```blade
<div class="space-y-3 text-sm">
    <div class="grid grid-cols-2 gap-4">
        <div>
            <span class="text-xs font-medium text-gray-500">PR Number</span>
            <p class="font-semibold text-gray-800 dark:text-white">{{ $purchaseRequest->pr_number }}</p>
        </div>
        <div>
            <span class="text-xs font-medium text-gray-500">Department</span>
            <p class="font-semibold text-gray-800 dark:text-white">{{ $purchaseRequest->department }}</p>
        </div>
    </div>
    <div>
        <span class="text-xs font-medium text-gray-500">Total Amount</span>
        <p class="text-lg font-bold text-brand-600 dark:text-brand-400">${{ number_format((float) $purchaseRequest->total_amount, 2) }}</p>
    </div>
</div>
```

---

## Step 6: Notifications & Dynamic Email Engine Integration

### Create `SubmitPurchaseRequestAction.php`
Create `app-modules/purchase-requests/src/Actions/SubmitPurchaseRequestAction.php`:

```php
<?php

declare(strict_types=1);

namespace Modules\PurchaseRequests\Actions;

use App\Events\NotificationRequested;
use App\Models\Approval\WorkflowTemplate;
use App\Services\ApprovalEngine;
use App\ValueObjects\NotificationPayload;
use Illuminate\Support\Facades\DB;
use Modules\PurchaseRequests\Models\PurchaseRequest;

final class SubmitPurchaseRequestAction
{
    public function __construct(
        private readonly ApprovalEngine $approvalEngine
    ) {}

    public function execute(PurchaseRequest $purchaseRequest, WorkflowTemplate $template): void
    {
        DB::transaction(function () use ($purchaseRequest, $template): void {
            // 1. Submit to Approval Workflow Engine
            $this->approvalEngine->submit($purchaseRequest, $template);
            $purchaseRequest->update(['status' => 'pending_approval']);

            // 2. Dispatch Notification Event
            event(new NotificationRequested(
                recipients: $purchaseRequest->requester,
                payload: new NotificationPayload(
                    title: "PR {$purchaseRequest->pr_number} Submitted",
                    message: "Your purchase request for '{$purchaseRequest->title}' was submitted for approval.",
                    type: 'purchase_request_submitted',
                    url: route('purchase-requests.show', $purchaseRequest->id),
                    actionText: 'View Purchase Request',
                    companyId: $purchaseRequest->company_id,
                    variables: [
                        'pr_number' => $purchaseRequest->pr_number,
                        'title' => $purchaseRequest->title,
                        'total_amount' => '$'.number_format((float) $purchaseRequest->total_amount, 2),
                        'department' => $purchaseRequest->department,
                    ]
                )
            ));
        });
    }
}
```

---

## Step 7: Complete Reactive Web SPA UI (Livewire v4 & Blade)

Here are the complete Blade templates, Web Controller, and Livewire v4 Single-File Components matching all UI wireframes.

### 7.1 Web Controller (`PurchaseRequestController.php`)
Create `app-modules/purchase-requests/src/Http/Controllers/PurchaseRequestController.php`:

```php
<?php

declare(strict_types=1);

namespace Modules\PurchaseRequests\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\PurchaseRequests\Models\PurchaseRequest;

final class PurchaseRequestController extends Controller
{
    public function index(Request $request): View
    {
        $company = auth()->user()->getActiveCompany();
        if (! $company) {
            abort(403, 'No active company context.');
        }

        $metrics = [
            'total' => PurchaseRequest::where('company_id', $company->id)->count(),
            'pending' => PurchaseRequest::where('company_id', $company->id)->where('status', 'pending_approval')->count(),
            'approved_total' => (float) PurchaseRequest::where('company_id', $company->id)->where('status', 'approved')->sum('total_amount'),
        ];

        return view('purchase-requests::index', compact('metrics'));
    }

    public function create(): View
    {
        return view('purchase-requests::create');
    }

    public function show(PurchaseRequest $purchaseRequest): View
    {
        $company = auth()->user()->getActiveCompany();
        if (! $company || $purchaseRequest->company_id !== $company->id) {
            abort(403, 'Unauthorized access to purchase request.');
        }

        $purchaseRequest->load(['requester', 'approvalRequests.steps.approverUser', 'approvalRequests.steps.approverRole']);

        return view('purchase-requests::show', compact('purchaseRequest'));
    }
}
```

---

### 7.2 Index Page & Livewire DataTable SFC

#### Host View: `app-modules/purchase-requests/resources/views/index.blade.php`
```blade
<x-layouts.app title="Purchase Requests">
    <div class="space-y-6">
        <!-- Header & Breadcrumbs -->
        <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Purchase Requests</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Manage internal requisitions, budgets, and approval workflows.</p>
            </div>
            <a
                href="{{ route('purchase-requests.create') }}"
                wire:navigate
                class="inline-flex items-center gap-2 rounded-xl bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white shadow-theme-xs hover:bg-brand-600"
            >
                <x-icons.plus class="h-4 w-4" />
                <span>New Purchase Request</span>
            </a>
        </div>

        <!-- Metrics KPI Cards -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                <span class="text-xs font-medium text-gray-500">Total Requisitions</span>
                <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">{{ $metrics['total'] }}</p>
            </div>
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                <span class="text-xs font-medium text-gray-500">Pending Approval</span>
                <p class="mt-2 text-2xl font-bold text-amber-500">{{ $metrics['pending'] }}</p>
            </div>
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                <span class="text-xs font-medium text-gray-500">Approved Volume</span>
                <p class="mt-2 text-2xl font-bold text-success-500">${{ number_format($metrics['approved_total'], 2) }}</p>
            </div>
        </div>

        <!-- Reactive Livewire DataTable Component -->
        <livewire:purchase-requests.purchase-request-table />
    </div>
</x-layouts.app>
```

#### Livewire DataTable SFC: `resources/views/components/purchase-requests/purchase-request-table.blade.php`
```blade
<?php

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use Modules\PurchaseRequests\Models\PurchaseRequest;

new class extends Component
{
    use WithPagination;

    #[Url(history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $status = 'all';

    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function render(): mixed
    {
        $companyId = auth()->user()->getActiveCompany()->id;

        $requests = PurchaseRequest::query()
            ->where('company_id', $companyId)
            ->with(['requester'])
            ->when($this->search !== '', function ($query) {
                $query->where(function ($q) {
                    $q->where('pr_number', 'like', "%{$this->search}%")
                      ->orWhere('title', 'like', "%{$this->search}%")
                      ->orWhere('department', 'like', "%{$this->search}%");
                });
            })
            ->when($this->status !== 'all', function ($query) {
                $query->where('status', $this->status);
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(10);

        return view('purchase-requests::components.purchase-requests.purchase-request-table', [
            'requests' => $requests,
        ]);
    }
};
?>

<div class="space-y-4">
    <!-- Filter Bar -->
    <div class="flex flex-col justify-between gap-4 rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03] sm:flex-row sm:items-center">
        <div class="relative flex-1">
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Search PR number, title, department..."
                class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 placeholder-gray-400 focus:border-brand-500 dark:border-gray-700 dark:text-white"
            />
        </div>
        <div class="flex items-center gap-2">
            <select wire:model.live="status" class="h-11 rounded-lg border border-gray-300 bg-transparent px-3 text-sm text-gray-800 dark:border-gray-700 dark:text-white">
                <option value="all">All Statuses</option>
                <option value="draft">Draft</option>
                <option value="pending_approval">Pending Approval</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
            </select>
        </div>
    </div>

    <!-- Table -->
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-gray-100 bg-gray-50 text-xs font-semibold uppercase text-gray-500 dark:border-gray-800 dark:bg-gray-900/50 dark:text-gray-400">
                <tr>
                    <th wire:click="sortBy('pr_number')" class="cursor-pointer px-6 py-4">PR Number</th>
                    <th wire:click="sortBy('title')" class="cursor-pointer px-6 py-4">Title</th>
                    <th class="px-6 py-4">Requester</th>
                    <th wire:click="sortBy('total_amount')" class="cursor-pointer px-6 py-4 text-right">Amount</th>
                    <th class="px-6 py-4 text-center">Status</th>
                    <th class="px-6 py-4 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($requests as $pr)
                    <tr class="hover:bg-gray-50/50 dark:hover:bg-white/[0.01]">
                        <td class="px-6 py-4 font-mono font-medium text-gray-900 dark:text-white">{{ $pr->pr_number }}</td>
                        <td class="px-6 py-4">
                            <p class="font-medium text-gray-900 dark:text-white">{{ $pr->title }}</p>
                            <span class="text-xs text-gray-500">{{ $pr->department }}</span>
                        </td>
                        <td class="px-6 py-4 text-gray-600 dark:text-gray-400">{{ $pr->requester->name }}</td>
                        <td class="px-6 py-4 text-right font-semibold text-gray-900 dark:text-white">${{ number_format((float) $pr->total_amount, 2) }}</td>
                        <td class="px-6 py-4 text-center">
                            @if($pr->status === 'approved')
                                <span class="rounded-full bg-success-50 px-2.5 py-1 text-xs font-medium text-success-600 dark:bg-success-500/10 dark:text-success-400">Approved</span>
                            @elseif($pr->status === 'pending_approval')
                                <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-600 dark:bg-amber-500/10 dark:text-amber-400">Pending</span>
                            @else
                                <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-400">{{ ucfirst($pr->status) }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('purchase-requests.show', $pr->id) }}" wire:navigate class="font-medium text-brand-500 hover:text-brand-600">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">No purchase requests found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="border-t border-gray-100 p-4 dark:border-gray-800">
            {{ $requests->links() }}
        </div>
    </div>
</div>
```

---

### 7.3 Create Page & 3-Step Livewire Wizard SFC

#### Host View: `app-modules/purchase-requests/resources/views/create.blade.php`
```blade
<x-layouts.app title="Create Purchase Request">
    <div class="max-w-4xl mx-auto space-y-6">
        <div class="flex items-center gap-4">
            <a href="{{ route('purchase-requests.index') }}" wire:navigate class="rounded-lg border border-gray-200 p-2 text-gray-600 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-400 dark:hover:bg-gray-800">
                <x-icons.arrow-left class="h-5 w-5" />
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">New Purchase Requisition</h1>
                <p class="text-sm text-gray-500">Complete the steps below to submit for budget approval.</p>
            </div>
        </div>

        <livewire:purchase-requests.purchase-request-wizard />
    </div>
</x-layouts.app>
```

#### Full 3-Step Livewire Wizard SFC: `resources/views/components/purchase-requests/purchase-request-wizard.blade.php`
```blade
<?php

use Livewire\Component;
use Livewire\Attributes\Validate;
use Modules\PurchaseRequests\Actions\CreatePurchaseRequestAction;
use Modules\PurchaseRequests\Actions\SubmitPurchaseRequestAction;
use App\Models\Approval\WorkflowTemplate;

new class extends Component
{
    public int $currentStep = 1;

    // Step 1 Fields
    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('required|string|max:100')]
    public string $department = 'Engineering';

    public ?string $description = null;

    // Step 2 Fields
    public array $lineItems = [
        ['description' => '', 'quantity' => 1, 'unit_price' => 0.00]
    ];

    // Step 3 Fields
    public ?int $selectedTemplateId = null;

    public function addLineItem(): void
    {
        $this->lineItems[] = ['description' => '', 'quantity' => 1, 'unit_price' => 0.00];
    }

    public function removeLineItem(int $index): void
    {
        unset($this->lineItems[$index]);
        $this->lineItems = array_values($this->lineItems);
    }

    public function calculateSubtotal(): float
    {
        return array_reduce($this->lineItems, function ($carry, $item): float {
            return $carry + ((int) ($item['quantity'] ?? 0) * (float) ($item['unit_price'] ?? 0));
        }, 0.0);
    }

    public function calculateTotal(): float
    {
        $subtotal = $this->calculateSubtotal();
        return $subtotal + ($subtotal * 0.10); // +10% estimated tax
    }

    public function nextStep(): void
    {
        if ($this->currentStep === 1) {
            $this->validate(['title' => 'required|string|max:255', 'department' => 'required|string|max:100']);
            $this->currentStep = 2;
        } elseif ($this->currentStep === 2) {
            if (empty($this->lineItems)) {
                $this->addError('lineItems', 'Please add at least one line item.');
                return;
            }
            $this->currentStep = 3;
        }
    }

    public function previousStep(): void
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    public function submit(CreatePurchaseRequestAction $createAction, SubmitPurchaseRequestAction $submitAction): void
    {
        $companyId = auth()->user()->getActiveCompany()->id;

        // 1. Create Purchase Request
        $pr = $createAction->execute([
            'company_id' => $companyId,
            'requester_id' => auth()->id(),
            'title' => $this->title,
            'department' => $this->department,
            'description' => $this->description,
            'total_amount' => $this->calculateTotal(),
            'line_items' => $this->lineItems,
        ]);

        // 2. Submit into Approval Engine if template chosen
        if ($this->selectedTemplateId) {
            $template = WorkflowTemplate::find($this->selectedTemplateId);
            if ($template) {
                $submitAction->execute($pr, $template);
            }
        }

        $this->redirect(route('purchase-requests.show', $pr->id), navigate: true);
    }
};
?>

<div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
    <!-- Step Indicator -->
    <div class="mb-8 flex items-center justify-between border-b border-gray-100 pb-4 dark:border-gray-800">
        <div class="flex items-center gap-2">
            <span class="flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold {{ $currentStep >= 1 ? 'bg-brand-500 text-white' : 'bg-gray-100 text-gray-500' }}">1</span>
            <span class="text-sm font-medium {{ $currentStep >= 1 ? 'text-gray-900 dark:text-white' : 'text-gray-400' }}">Basic Info</span>
        </div>
        <div class="h-0.5 w-12 bg-gray-200 dark:bg-gray-800"></div>
        <div class="flex items-center gap-2">
            <span class="flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold {{ $currentStep >= 2 ? 'bg-brand-500 text-white' : 'bg-gray-100 text-gray-500' }}">2</span>
            <span class="text-sm font-medium {{ $currentStep >= 2 ? 'text-gray-900 dark:text-white' : 'text-gray-400' }}">Line Items</span>
        </div>
        <div class="h-0.5 w-12 bg-gray-200 dark:bg-gray-800"></div>
        <div class="flex items-center gap-2">
            <span class="flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold {{ $currentStep >= 3 ? 'bg-brand-500 text-white' : 'bg-gray-100 text-gray-500' }}">3</span>
            <span class="text-sm font-medium {{ $currentStep >= 3 ? 'text-gray-900 dark:text-white' : 'text-gray-400' }}">Approval & Submit</span>
        </div>
    </div>

    <!-- Step 1 Form -->
    @if($currentStep === 1)
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Requisition Title</label>
                <input type="text" wire:model="title" class="mt-1 block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white" placeholder="e.g. Q3 Hardware Refresh" />
                @error('title') <span class="text-xs text-error-500">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Department</label>
                <input type="text" wire:model="department" class="mt-1 block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
                @error('department') <span class="text-xs text-error-500">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description / Justification</label>
                <textarea wire:model="description" rows="3" class="mt-1 block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white"></textarea>
            </div>
            <div class="flex justify-end pt-4">
                <button type="button" wire:click="nextStep" class="rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-600">Next: Line Items ▶</button>
            </div>
        </div>
    @endif

    <!-- Step 2 Form -->
    @if($currentStep === 2)
        <div class="space-y-4">
            <div class="space-y-3">
                @foreach($lineItems as $index => $item)
                    <div class="flex items-center gap-3">
                        <input type="text" wire:model="lineItems.{{ $index }}.description" placeholder="Item description" class="flex-1 rounded-lg border border-gray-300 px-4 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
                        <input type="number" wire:model.live="lineItems.{{ $index }}.quantity" placeholder="Qty" class="w-20 rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white" min="1" />
                        <input type="number" step="0.01" wire:model.live="lineItems.{{ $index }}.unit_price" placeholder="Price" class="w-28 rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
                        <span class="w-24 text-right font-medium text-gray-700 dark:text-gray-300">${{ number_format(((int)($item['quantity'] ?? 0) * (float)($item['unit_price'] ?? 0)), 2) }}</span>
                        <button type="button" wire:click="removeLineItem({{ $index }})" class="text-error-500 hover:text-error-600">✕</button>
                    </div>
                @endforeach
            </div>
            <button type="button" wire:click="addLineItem" class="text-sm font-medium text-brand-500 hover:text-brand-600">+ Add Line Item</button>

            <div class="rounded-xl bg-gray-50 p-4 dark:bg-gray-900/50 space-y-1 text-sm">
                <div class="flex justify-between text-gray-500"><span>Subtotal:</span><span>${{ number_format($this->calculateSubtotal(), 2) }}</span></div>
                <div class="flex justify-between text-gray-500"><span>Est. Tax (10%):</span><span>${{ number_format($this->calculateSubtotal() * 0.10, 2) }}</span></div>
                <div class="flex justify-between border-t border-gray-200 pt-1 font-bold text-gray-900 dark:text-white"><span>Grand Total:</span><span>${{ number_format($this->calculateTotal(), 2) }}</span></div>
            </div>

            <div class="flex justify-between pt-4">
                <button type="button" wire:click="previousStep" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 dark:border-gray-700 dark:text-gray-300">◀ Back</button>
                <button type="button" wire:click="nextStep" class="rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-600">Next: Approval Step ▶</button>
            </div>
        </div>
    @endif

    <!-- Step 3 Form -->
    @if($currentStep === 3)
        <div class="space-y-4">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">Select Approval Workflow</h3>
            <p class="text-sm text-gray-500">Choose a workflow pipeline to route this requisition:</p>
            
            <div class="space-y-2">
                @foreach(WorkflowTemplate::all() as $tpl)
                    <label class="flex items-center gap-3 rounded-xl border p-4 cursor-pointer {{ $selectedTemplateId === $tpl->id ? 'border-brand-500 bg-brand-50/50 dark:bg-brand-500/10' : 'border-gray-200 dark:border-gray-800' }}">
                        <input type="radio" wire:model="selectedTemplateId" value="{{ $tpl->id }}" class="text-brand-500" />
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $tpl->name }}</p>
                            <span class="text-xs text-gray-500">{{ $tpl->description }}</span>
                        </div>
                    </label>
                @endforeach
            </div>

            <div class="flex justify-between pt-4">
                <button type="button" wire:click="previousStep" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 dark:border-gray-700 dark:text-gray-300">◀ Back</button>
                <button type="button" wire:click="submit" class="rounded-lg bg-success-500 px-6 py-2.5 text-sm font-semibold text-white hover:bg-success-600">Submit for Approval ✓</button>
            </div>
        </div>
    @endif
</div>
```

---

### 7.4 Document Detail & Approval Stepper View (`show.blade.php`)

Create `app-modules/purchase-requests/resources/views/show.blade.php`:

```blade
<x-layouts.app :title="$purchaseRequest->pr_number">
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $purchaseRequest->pr_number }}</h1>
                    @if($purchaseRequest->status === 'approved')
                        <span class="rounded-full bg-success-50 px-3 py-1 text-xs font-semibold text-success-600 dark:bg-success-500/10 dark:text-success-400">Approved</span>
                    @elseif($purchaseRequest->status === 'pending_approval')
                        <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-600 dark:bg-amber-500/10 dark:text-amber-400">Pending Approval</span>
                    @else
                        <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-400">{{ ucfirst($purchaseRequest->status) }}</span>
                    @endif
                </div>
                <p class="text-sm text-gray-500">{{ $purchaseRequest->title }} &bull; {{ $purchaseRequest->department }}</p>
            </div>
            <a href="{{ route('purchase-requests.index') }}" wire:navigate class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300">
                Back to Index
            </a>
        </div>

        <!-- Approval Stepper Timeline -->
        @if($purchaseRequest->approvalRequests->isNotEmpty())
            @php $approval = $purchaseRequest->approvalRequests->first(); @endphp
            <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
                <h3 class="mb-4 text-base font-semibold text-gray-900 dark:text-white">Approval Pipeline Progress</h3>
                <div class="flex flex-col gap-4 md:flex-row md:items-center">
                    @foreach($approval->steps as $step)
                        <div class="flex flex-1 items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full text-sm font-bold {{ $step->status === 'approved' ? 'bg-success-500 text-white' : ($step->status === 'pending' ? 'bg-amber-500 text-white animate-pulse' : 'bg-gray-100 text-gray-400') }}">
                                {{ $step->order }}
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $step->approverUser->name ?? $step->approverRole->name ?? 'Approver' }}</p>
                                <span class="text-xs text-gray-500">{{ ucfirst($step->status) }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Line Items Breakdown -->
        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
            <h3 class="mb-4 text-base font-semibold text-gray-900 dark:text-white">Requisition Line Items</h3>
            <table class="w-full text-left text-sm">
                <thead class="border-b border-gray-100 text-xs font-semibold uppercase text-gray-400 dark:border-gray-800">
                    <tr>
                        <th class="py-3">Item Description</th>
                        <th class="py-3 text-center">Qty</th>
                        <th class="py-3 text-right">Unit Price</th>
                        <th class="py-3 text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach($purchaseRequest->line_items ?? [] as $item)
                        <tr>
                            <td class="py-3 font-medium text-gray-900 dark:text-white">{{ $item['description'] }}</td>
                            <td class="py-3 text-center text-gray-600 dark:text-gray-400">{{ $item['quantity'] }}</td>
                            <td class="py-3 text-right text-gray-600 dark:text-gray-400">${{ number_format((float)$item['unit_price'], 2) }}</td>
                            <td class="py-3 text-right font-semibold text-gray-900 dark:text-white">${{ number_format((int)$item['quantity'] * (float)$item['unit_price'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="mt-4 flex justify-end border-t border-gray-100 pt-4 dark:border-gray-800">
                <span class="text-lg font-bold text-gray-900 dark:text-white">Total Request Value: ${{ number_format((float)$purchaseRequest->total_amount, 2) }}</span>
            </div>
        </div>
    </div>
</x-layouts.app>
```

---

## Step 8: Machine-to-Machine REST API & OpenAPI 3.1 Documentation

### 1. Form Request Validation
Create `app-modules/purchase-requests/src/Http/Requests/Api/v1/StorePurchaseRequestRequest.php`:

```php
<?php

declare(strict_types=1);

namespace Modules\PurchaseRequests\Http\Requests\Api\v1;

use Illuminate\Foundation\Http\FormRequest;

final class StorePurchaseRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'department' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'total_amount' => ['required', 'numeric', 'min:0.01'],
            'line_items' => ['nullable', 'array'],
        ];
    }
}
```

### 2. Eloquent API Resource
Create `app-modules/purchase-requests/src/Http/Resources/Api/v1/PurchaseRequestResource.php`:

```php
<?php

declare(strict_types=1);

namespace Modules\PurchaseRequests\Http\Resources\Api\v1;

use App\Http\Resources\Api\v1\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Modules\PurchaseRequests\Models\PurchaseRequest
 */
class PurchaseRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'pr_number' => $this->pr_number,
            'title' => $this->title,
            'department' => $this->department,
            'total_amount' => (float) $this->total_amount,
            'status' => $this->status,
            'company_id' => $this->company_id,
            'requester' => new UserResource($this->whenLoaded('requester')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
```

### 3. API Controller
Create `app-modules/purchase-requests/src/Http/Controllers/Api/v1/PurchaseRequestController.php`:

```php
<?php

declare(strict_types=1);

namespace Modules\PurchaseRequests\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\App;
use Modules\PurchaseRequests\Actions\CreatePurchaseRequestAction;
use Modules\PurchaseRequests\Http\Requests\Api\v1\StorePurchaseRequestRequest;
use Modules\PurchaseRequests\Http\Resources\Api\v1\PurchaseRequestResource;
use Modules\PurchaseRequests\Models\PurchaseRequest;

class PurchaseRequestController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $prs = PurchaseRequest::query()
            ->where('company_id', App::get('active_company_id'))
            ->with(['requester'])
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return PurchaseRequestResource::collection($prs);
    }

    public function show(PurchaseRequest $purchaseRequest): JsonResponse
    {
        if ($purchaseRequest->company_id !== App::get('active_company_id')) {
            return response()->json(['message' => 'Unauthorized resource access.'], 403);
        }

        $purchaseRequest->load(['requester']);

        return (new PurchaseRequestResource($purchaseRequest))->response();
    }

    public function store(StorePurchaseRequestRequest $request, CreatePurchaseRequestAction $action): JsonResponse
    {
        $pr = $action->execute(array_merge(
            $request->validated(),
            [
                'company_id' => App::get('active_company_id'),
                'requester_id' => auth()->id(),
            ]
        ));

        return (new PurchaseRequestResource($pr))
            ->response()
            ->setStatusCode(201);
    }
}
```

---

## Step 9: Comprehensive Automated Testing with Pest 5

Create `app-modules/purchase-requests/tests/Feature/PurchaseRequestTest.php`:

```php
<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\PurchaseRequests\Models\PurchaseRequest;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->create();
    $this->user->companies()->attach($this->company->id, ['status' => 'active']);
});

test('user can view purchase requests index page within active company', function (): void {
    $this->actingAs($this->user);
    session(['active_company_id' => $this->company->id]);

    PurchaseRequest::factory()->create([
        'company_id' => $this->company->id,
        'requester_id' => $this->user->id,
        'title' => 'Server Hardware PR',
    ]);

    $this->get(route('purchase-requests.index'))
        ->assertOk()
        ->assertSee('Server Hardware PR');
});

test('cross-company isolation forbids unauthorized access', function (): void {
    $otherCompany = Company::factory()->create();
    $otherPr = PurchaseRequest::factory()->create(['company_id' => $otherCompany->id]);

    $this->actingAs($this->user);
    session(['active_company_id' => $this->company->id]);

    $this->get(route('purchase-requests.show', $otherPr->id))
        ->assertForbidden();
});
```

Run tests via Pest 5:
```bash
php artisan rubberstamp:features --filter=PurchaseRequestTest
```

---

## Step 10: End-to-End Scaffolding Checklist

Before delivering your module to production, confirm all items:

- [ ] 1. **Composer Path Autoload**: `composer update modules/purchase-requests` executed.
- [ ] 2. **Multi-Tenancy Scoping**: Table contains `company_id` foreign key and queries filter by active tenant.
- [ ] 3. **Feature Flag Registered**: Added to `config/features.php` and navigation wrapped in `@feature`.
- [ ] 4. **Domain Action Delegation**: Mutations executed inside `src/Actions/` wrapped in `DB::transaction()`.
- [ ] 5. **Approval Engine Connected**: Implements `Approvable` with `getApprovalTitle()` and preview partial.
- [ ] 6. **Notifications Configured**: `NotificationRequested` dispatched with `NotificationPayload`.
- [ ] 7. **Livewire v4 SPA UI**: Navigation uses `wire:navigate` and components handle live updates.
- [ ] 8. **REST API & Scramble Docs**: Endpoints documented in `/docs/api` with Sanctum token abilities.
- [ ] 9. **Pest 5 Test Coverage**: Multi-tenancy, workflow actions, and API endpoints verified green.
