---
name: api-layer-integration
description: "Apply this skill when building or exposing REST API endpoints for any module in this application. Triggers: api, rest api, api controller, api resource, api route, v1 api, scramble api, api token, sanctum api, api integration. Covers: API v1 architecture, Core vs Domain Module namespacing conventions, Laravel Sanctum token authentication, multi-tenant company scoping with api.company middleware and X-Company-ID header, 3-layer Domain Action delegation for mutating endpoints, Eloquent API Resources, Dedoc Scramble OpenAPI documentation, Bruno request collections, and Pest test coverage."
license: MIT
metadata:
  author: eldinbiz
---

# API Layer Module Integration

This skill documents how to expose and build REST API endpoints for any application module adhering to the multi-tenant scaffolding conventions of this project.

---

## 1. Architecture Overview & Separation of Concerns

All REST API endpoints in this application follow a unified, stateless, rate-limited, and tenant-scoped lifecycle under `/api/v1/`:

```
[ External Client / Integration Bot ]
               │
               │ HTTP (Bearer Token + X-Company-ID Header)
               ▼
   [ routes/api.php or <module>-routes.php ]
               │
               ▼
   [ auth:sanctum ] ──> Authenticates User/System Bot
               │
               ▼
   [ throttle:api.v1 ] ──> Rate limiting (60 req/min)
               │
        ┌──────┴──────────────────────────┐
        ▼                                 ▼
[ Tenant-Scoped Routes ]         [ Platform-Level Routes ]
  (middleware: 'api.company')      (middleware: 'can:manage-platform')
        │                                 │
        ▼                                 ▼
App::get('active_company_id')    Global / Cross-Company Scope
        │                                 │
        └──────────────┬──────────────────┘
                       ▼
            [ API v1 Controller ]
                       │
                       ├─► Validates FormRequest (rules() reflected by Scramble)
                       ├─► Mutating actions delegate to Domain Action ($action->execute())
                       ▼
         [ Eloquent API Resource(s) ]
                       │
                       ▼
          JSON Response (OpenAPI 3.1)
```

### Livewire Web SPA vs REST API Boundary

- **Livewire v4**: Powers the interactive Single-Page Application in the browser for human users (cookie sessions, CSRF, real-time reactivity, `wire:navigate`).
- **REST API v1**: Powers external machine-to-machine integrations, system bots (`is_system = true`), and mobile clients via Sanctum tokens and `X-Company-ID`.
- **Shared Domain Actions**: Both Livewire components and API controllers are thin presentation layers that delegate state-modifying operations to the **exact same Domain Actions** (`App\Actions\*` or `Modules\<Module>\Actions\*`).

---

## 2. Key Directories & Naming Conventions

| Component | Core Platform (`app/`) | Domain Module (`app-modules/<name>/`) |
|---|---|---|
| **API Controller** | `app/Http/Controllers/Api/v1/<Name>Controller.php` | `app-modules/<name>/src/Http/Controllers/Api/v1/<Name>Controller.php` |
| **API Resource** | `app/Http/Resources/Api/v1/<Name>Resource.php` | `app-modules/<name>/src/Http/Resources/Api/v1/<Name>Resource.php` |
| **Form Request** | `app/Http/Requests/Api/v1/<Name>Request.php` | `app-modules/<name>/src/Http/Requests/Api/v1/<Name>Request.php` |
| **Domain Action** | `app/Actions/<Group>/<Action>.php` | `app-modules/<name>/src/Actions/<Action>.php` |
| **API Routes** | `routes/api.php` | `app-modules/<name>/routes/<name>-routes.php` |
| **Pest Tests** | `tests/Feature/Api/v1/<Name>Test.php` | `app-modules/<name>/tests/Feature/<Name>ApiTest.php` |
| **Bruno Requests** | `bruno/<Name>/` | `bruno/<Name>/` |

> [!IMPORTANT]
> If developing an API for a domain module, ensure the module has been linked via `composer update modules/<name>` before running tests or migrations.

---

## 3. Step-by-Step Implementation Pattern

### Step 1: Create the Eloquent API Resource

All API endpoints must return structured Eloquent API Resources with strict typing and eager-loading checks (`whenLoaded`):

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\v1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Invoice
 */
class InvoiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'amount' => (float) $this->amount,
            'status' => $this->status,
            'company_id' => $this->company_id,
            'user' => new UserResource($this->whenLoaded('user')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
```

---

### Step 2: Create Form Request (Validation & Scramble Doc Reflection)

Create Form Requests for validating incoming request payloads. Dedoc Scramble uses AST reflection on the `rules()` method to document the request body schema:

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\v1;

use Illuminate\Foundation\Http\FormRequest;

final class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Ability gating is enforced via middleware in routes
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'invoice_number' => ['required', 'string', 'max:50'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string'],
        ];
    }
}
```

---

### Step 3: Build the API Controller (Delegating Mutations to Domain Actions)

API controllers must be thin and delegate state-modifying business logic to Domain Actions:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1;

use App\Actions\Invoices\CreateInvoiceAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v1\StoreInvoiceRequest;
use App\Http\Resources\Api\v1\InvoiceResource;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\App;

class InvoiceController extends Controller
{
    /**
     * List Invoices
     *
     * Retrieve a paginated list of invoice records belonging to the active company context.
     *
     * @param Request $request
     * @return AnonymousResourceCollection<InvoiceResource>
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $invoices = Invoice::query()
            ->where('company_id', App::get('active_company_id'))
            ->with(['user'])
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return InvoiceResource::collection($invoices);
    }

    /**
     * Get Invoice Detail
     *
     * Retrieve full details for a single invoice belonging to the active company context.
     *
     * @param Invoice $invoice
     * @return JsonResponse
     */
    public function show(Invoice $invoice): JsonResponse
    {
        if ($invoice->company_id !== App::get('active_company_id')) {
            return response()->json(['message' => 'Unauthorized resource access.'], 403);
        }

        $invoice->load(['user']);

        return (new InvoiceResource($invoice))->response();
    }

    /**
     * Create Invoice
     *
     * Create a new invoice record within the active company context.
     *
     * @param StoreInvoiceRequest $request
     * @param CreateInvoiceAction $action
     * @return JsonResponse
     */
    public function store(StoreInvoiceRequest $request, CreateInvoiceAction $action): JsonResponse
    {
        $invoice = $action->execute(array_merge(
            $request->validated(),
            ['company_id' => App::get('active_company_id')]
        ));

        return (new InvoiceResource($invoice))
            ->response()
            ->setStatusCode(201);
    }
}
```

---

### Step 4: Register Routes in `routes/api.php` or Module Routes

Routes belong inside the `/v1` prefix group. Choose between **Tenant-Scoped** or **Platform-Level** middleware, and attach granular `ability:<scope>` middleware:

```php
// routes/api.php (or app-modules/<name>/routes/<name>-routes.php)

Route::prefix('v1')->middleware(['auth:sanctum', 'throttle:api.v1'])->group(function () {
    
    // 1. Tenant-Scoped Routes (Requires X-Company-ID header, company membership, and token ability)
    Route::middleware(['api.company'])->group(function () {
        Route::get('invoices', [InvoiceController::class, 'index'])->middleware('ability:invoices:index');
        Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->middleware('ability:invoices:show');
        Route::post('invoices', [InvoiceController::class, 'store'])->middleware('ability:invoices:store');
    });

    // 2. Platform-Level Routes (Requires platform-admin role and token ability)
    Route::middleware('can:manage-platform')->group(function () {
        Route::get('global-reports', [GlobalReportController::class, 'index'])->middleware('ability:global-reports:index');
    });
});
```

---

### Step 5: Register Endpoints in Token Permissions Registry

Register new endpoints in `App\Http\Controllers\Admin\ApiTokenController::AVAILABLE_ENDPOINTS` so developers and administrators can select them from the Token Permissions checklist with live deep-linking:

```php
// app/Http/Controllers/Admin/ApiTokenController.php

[
    'ability' => 'invoices:index',
    'method' => 'GET',
    'path' => '/api/v1/invoices',
    'label' => 'List Invoices',
    'group' => 'Invoices',
    'doc_anchor' => 'invoice.index',
],
[
    'ability' => 'invoices:show',
    'method' => 'GET',
    'path' => '/api/v1/invoices/{id}',
    'label' => 'View Invoice Detail',
    'group' => 'Invoices',
    'doc_anchor' => 'invoice.show',
],
[
    'ability' => 'invoices:store',
    'method' => 'POST',
    'path' => '/api/v1/invoices',
    'label' => 'Create Invoice',
    'group' => 'Invoices',
    'doc_anchor' => 'invoice.store',
],
```

---

### Step 6: Write Pest Feature Tests

Always create a test in `tests/Feature/Api/v1/[Module]ApiTest.php` (or `app-modules/<name>/tests/Feature/`) testing authentication, company scoping, and action execution:

```php
<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lists invoices scoped to the requested company', function () {
    $company = Company::factory()->create();
    $otherCompany = Company::factory()->create();

    $user = User::factory()->system()->create();
    $user->companies()->attach($company->id);

    Invoice::factory()->count(2)->create(['company_id' => $company->id]);
    Invoice::factory()->create(['company_id' => $otherCompany->id]);

    $token = $user->createToken('Test API')->plainTextToken;

    $response = $this->getJson('/api/v1/invoices', [
        'Authorization' => 'Bearer ' . $token,
        'X-Company-ID' => (string) $company->id,
    ]);

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

it('creates an invoice using the domain action', function () {
    $company = Company::factory()->create();
    $user = User::factory()->system()->create();
    $user->companies()->attach($company->id);

    $token = $user->createToken('Test API')->plainTextToken;

    $response = $this->postJson('/api/v1/invoices', [
        'invoice_number' => 'INV-2026-001',
        'amount' => 1250.00,
        'description' => 'Consulting services',
    ], [
        'Authorization' => 'Bearer ' . $token,
        'X-Company-ID' => (string) $company->id,
    ]);

    $response->assertCreated();
    $this->assertDatabaseHas('invoices', [
        'company_id' => $company->id,
        'invoice_number' => 'INV-2026-001',
    ]);
});
```

---

### Step 7: Add Bruno Request Files

Create a directory under `bruno/[Module]/` and add `.bru` files for testing:

```bru
meta {
  name: List Invoices
  type: http
  seq: 1
}

get {
  url: {{baseUrl}}/api/v1/invoices
  body: none
  auth: inherit
}

headers {
  X-Company-ID: {{companyId}}
}
```

---

## 4. Multi-Tenant Scoping Rules Summary

1. **`X-Company-ID` Header**: Required for all routes inside the `api.company` middleware.
2. **Context Resolution**: The middleware validates the user's company membership (or allows platform admins with global access) and sets `App::instance('active_company_id', $companyId)`.
3. **Database Queries**: Always filter queries using `->where('company_id', App::get('active_company_id'))`.
4. **Mutations**: Delegate state creation and updates to Domain Actions.
5. **Interactive Documentation**: Endpoints are automatically documented by **Dedoc Scramble** and accessible at `/docs/api` (or `/docs/api.json`).
