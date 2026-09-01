# Tutorial: Building a Complete Domain Module

A comprehensive, step-by-step tutorial for software developers and system builders building a new domain module in this multi-tenant starter kit.

In this tutorial, we will build a complete **Purchase Request Management** module from scratch (`app-modules/purchase-requests/`) and integrate it with **all core subsystems** of the scaffolding platform:

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
1. [Prerequisites & Architecture Overview](#1-prerequisites--architecture-overview)
2. [Step 1: Scaffold the Module & Link Composer](#step-1-scaffold-the-module--link-composer)
3. [Step 2: Database Migration & Multi-Tenant Model](#step-2-database-migration--multi-tenant-model)
4. [Step 3: Register in Feature Toggle System](#step-3-register-in-feature-toggle-system)
5. [Step 4: Implement 3-Layer Domain Actions](#step-4-implement-3-layer-domain-actions)
6. [Step 5: Integrate with the Approval Workflow Engine](#step-5-integrate-with-the-approval-workflow-engine)
7. [Step 6: Integrate with Notifications & Dynamic Emails](#step-6-integrate-with-notifications--dynamic-emails)
8. [Step 7: Build Interactive Web SPA UI (Livewire v4)](#step-7-build-interactive-web-spa-ui-livewire-v4)
9. [Step 8: Expose REST API Endpoints & OpenAPI Docs](#step-8-expose-rest-api-endpoints--openapi-docs)
10. [Step 9: Write Automated Tests with Pest 5](#step-9-write-automated-tests-with-pest-5)
11. [Step 10: End-to-End Verification Checklist](#step-10-end-to-end-verification-checklist)

---

## 1. Prerequisites & Architecture Overview

Before beginning, ensure your development environment is running:
- **Docker Mode**: `docker compose up -d`
- **Local PHP Mode**: `composer dev`

### Domain Isolation Principle
All code for this feature will reside inside `app-modules/purchase-requests/`. We will not modify core platform files in `app/` except for central configuration registries (`config/features.php` and `ApiTokenController.php`).

---

## Step 1: Scaffold the Module & Link Composer

### 1. Run the Artisan Module Generator
```bash
# In Docker: docker compose exec app php artisan make:module purchase-requests
php artisan make:module purchase-requests
```
This generates the module directory structure under `app-modules/purchase-requests/` and creates `composer.json` with PSR-4 autoloading for `Modules\PurchaseRequests\`.

### 2. Link Composer Path Dependencies (MANDATORY)
Root `composer.json` uses Composer's wildcard path repository (`"url": "app-modules/*"`). Run the update command immediately to symlink the module into `vendor/modules/` and build autoloader mappings:

```bash
composer update modules/purchase-requests
# Or in Docker:
docker compose exec app composer update modules/purchase-requests
```

> [!CAUTION]
> Skipping this Composer update step will cause PHP `Class not found` exceptions when creating or testing module classes.

---

## Step 2: Database Migration & Multi-Tenant Model

### 1. Create Migration, Model & Factory
Use Artisan with `--module=purchase-requests`:

```bash
php artisan make:model PurchaseRequest -mfc --module=purchase-requests
```

### 2. Define the Migration Schema
Open `app-modules/purchase-requests/database/migrations/*_create_purchase_requests_table.php`:

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
        Schema::create('purchase_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            $table->string('pr_number', 50)->unique();
            $table->string('title');
            $table->string('department');
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

## Step 3: Register in Feature Toggle System

Every domain module should be toggleable globally and per-company.

### 1. Register in `config/features.php`
Add the feature definition into `config/features.php`:

```php
'purchase-requests' => [
    'label' => 'Purchase Requests',
    'description' => 'Procurement requisition and multi-step approval pipelines.',
    'default_enabled' => true,
    'bypass_permissions' => ['manage-purchase-requests'],
    'setting_key' => 'feature_purchase_requests',
],
```

### 2. Add Sidebar Navigation Link with `@feature`
Open `resources/views/partials/sidebar.blade.php` and add the navigation link:

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

## Step 4: Implement 3-Layer Domain Actions

State-modifying operations (create, submit, cancel) must reside in dedicated **Domain Actions** inside `app-modules/purchase-requests/src/Actions/`.

### 1. Create Purchase Request Action
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
            $prNumber = 'PR-'.date('Ym').'-'.str_pad((string) (PurchaseRequest::count() + 1), 4, '0', STR_PAD_LEFT);

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

## Step 5: Integrate with the Approval Workflow Engine

To connect your model to the approval workflow engine, implement `App\Contracts\Approvable`.

### 1. Implement `Approvable` on `PurchaseRequest`
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

    // === Approvable Contract Methods ===

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

### 2. Create the Approval Preview Partial
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

## Step 6: Integrate with Notifications & Dynamic Emails

Trigger in-app notification bell alerts and branded emails using `NotificationRequested`.

### Create Submit for Approval Action
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
        private readonly ApprovalEngine $engine
    ) {}

    public function execute(PurchaseRequest $purchaseRequest, WorkflowTemplate $template): void
    {
        DB::transaction(function () use ($purchaseRequest, $template): void {
            // 1. Submit to Approval Engine
            $this->engine->submit($purchaseRequest, $template);
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

## Step 7: Build Interactive Web SPA UI (Livewire v4)

We build reactive Single Page Application components using Livewire v4 and `wire:navigate`.

### 1. Routes Definition
Open `app-modules/purchase-requests/routes/purchase-requests-routes.php`:

```php
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\PurchaseRequests\Http\Controllers\Api\v1\PurchaseRequestController as ApiPurchaseRequestController;
use Modules\PurchaseRequests\Http\Controllers\PurchaseRequestController;

// Web Routes
Route::middleware(['web', 'auth', 'feature:purchase-requests'])
    ->prefix('purchase-requests')
    ->name('purchase-requests.')
    ->group(function () {
        Route::get('/', [PurchaseRequestController::class, 'index'])->name('index');
        Route::get('/create', [PurchaseRequestController::class, 'create'])->name('create');
        Route::get('/{purchaseRequest}', [PurchaseRequestController::class, 'show'])->name('show');
    });

// API Routes
Route::middleware(['api', 'auth:sanctum', 'api.company', 'feature:purchase-requests'])
    ->prefix('api/v1')
    ->group(function () {
        Route::get('purchases', [ApiPurchaseRequestController::class, 'index'])->middleware('ability:purchases:index');
        Route::get('purchases/{purchaseRequest}', [ApiPurchaseRequestController::class, 'show'])->middleware('ability:purchases:show');
        Route::post('purchases', [ApiPurchaseRequestController::class, 'store'])->middleware('ability:purchases:store');
    });
```

### 2. Livewire v4 Creation Wizard Component
Create `resources/views/components/purchase-requests/purchase-request-wizard.blade.php`:

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

    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('required|string|max:100')]
    public string $department = '';

    public array $lineItems = [
        ['description' => '', 'quantity' => 1, 'unit_price' => 0.00]
    ];

    public ?int $templateId = null;

    public function addLineItem(): void
    {
        $this->lineItems[] = ['description' => '', 'quantity' => 1, 'unit_price' => 0.00];
    }

    public function calculateTotal(): float
    {
        return array_reduce($this->lineItems, function ($carry, $item) {
            return $carry + ($item['quantity'] * $item['unit_price']);
        }, 0.0);
    }

    public function saveDraft(CreatePurchaseRequestAction $action): void
    {
        $this->validate();

        $pr = $action->execute([
            'company_id' => auth()->user()->getActiveCompany()->id,
            'requester_id' => auth()->id(),
            'title' => $this->title,
            'department' => $this->department,
            'total_amount' => $this->calculateTotal(),
            'line_items' => $this->lineItems,
        ]);

        $this->redirect(route('purchase-requests.show', $pr->id), navigate: true);
    }
};
?>

<div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
    <h2 class="text-xl font-semibold mb-4 text-gray-800 dark:text-white">New Purchase Request</h2>

    <div class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Title</label>
            <input type="text" wire:model="title" class="input w-full mt-1" />
            @error('title') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Department</label>
            <input type="text" wire:model="department" class="input w-full mt-1" />
            @error('department') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
        </div>

        <div class="flex justify-between items-center pt-4">
            <span class="font-bold text-gray-800 dark:text-white">Total: ${{ number_format($this->calculateTotal(), 2) }}</span>
            <button wire:click="saveDraft" class="btn btn-primary">Save as Draft</button>
        </div>
    </div>
</div>
```

---

## Step 8: Expose REST API Endpoints & OpenAPI Docs

Expose stateless REST endpoints for machine-to-machine integrations.

### 1. Create Eloquent Resource
Create `app-modules/purchase-requests/src/Http/Resources/Api/v1/PurchaseRequestResource.php`:

```php
<?php

declare(strict_types=1);

namespace Modules\PurchaseRequests\Http\Resources\Api\v1;

use App\Http\Resources\Api\v1\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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

### 2. Create API Controller (Delegating to Domain Action)
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
        $requests = PurchaseRequest::query()
            ->where('company_id', App::get('active_company_id'))
            ->with(['requester'])
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return PurchaseRequestResource::collection($requests);
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

### 3. Register Endpoints in Token Permissions Registry
Add the endpoints to `App\Http\Controllers\Admin\ApiTokenController::AVAILABLE_ENDPOINTS`:

```php
[
    'ability' => 'purchases:index',
    'method' => 'GET',
    'path' => '/api/v1/purchases',
    'label' => 'List Purchase Requests',
    'group' => 'Purchases',
    'doc_anchor' => 'purchaseRequest.index',
],
[
    'ability' => 'purchases:show',
    'method' => 'GET',
    'path' => '/api/v1/purchases/{id}',
    'label' => 'View Purchase Request Detail',
    'group' => 'Purchases',
    'doc_anchor' => 'purchaseRequest.show',
],
```

---

## Step 9: Write Automated Tests with Pest 5

Create comprehensive tests in `app-modules/purchase-requests/tests/Feature/PurchaseRequestTest.php`:

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

test('user can view purchase requests index page', function (): void {
    $this->actingAs($this->user);
    session(['active_company_id' => $this->company->id]);

    PurchaseRequest::factory()->create([
        'company_id' => $this->company->id,
        'requester_id' => $this->user->id,
        'title' => 'Server Rack Hardware',
    ]);

    $this->get(route('purchase-requests.index'))
        ->assertOk()
        ->assertSee('Server Rack Hardware');
});

test('tenant cannot view purchase requests from another company', function (): void {
    $otherCompany = Company::factory()->create();
    $otherPr = PurchaseRequest::factory()->create(['company_id' => $otherCompany->id]);

    $this->actingAs($this->user);
    session(['active_company_id' => $this->company->id]);

    $this->get(route('purchase-requests.show', $otherPr->id))
        ->assertForbidden();
});
```

Run the tests:
```bash
./test --filter=PurchaseRequestTest
```

---

## Step 10: End-to-End Verification Checklist

When your module is complete, verify the full integration checklist:

- [ ] **1. Composer Autoload**: `composer update modules/purchase-requests` executed.
- [ ] **2. Multi-Tenancy**: `company_id` column present and queries scoped to `App::get('active_company_id')`.
- [ ] **3. Feature Toggle**: Toggling the feature in Admin Settings enables/disables the module.
- [ ] **4. Approval Engine**: Implements `Approvable` and submits workflows via `ApprovalEngine`.
- [ ] **5. Notifications**: In-app bell badge appears and emails render placeholders.
- [ ] **6. 3-Layer Actions**: Writes are encapsulated in `src/Actions/` with `DB::transaction()`.
- [ ] **7. Livewire v4 SPA**: Navigation uses `wire:navigate` without full-page reloads.
- [ ] **8. REST API & Docs**: Endpoints documented in `/docs/api` via Dedoc Scramble.
- [ ] **9. Pest 5 Tests**: All feature tests pass with `./test`.
