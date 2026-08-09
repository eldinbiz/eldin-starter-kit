# API Layer Developer Guide

This guide provides developers with instructions, best practices, and architecture rules for building and integrating REST API endpoints into this multi-tenant Laravel ERP scaffolding.

---

## Table of Contents
1. [Architecture & Request Lifecycle](#1-architecture--request-lifecycle)
2. [Authentication & Authorization](#2-authentication--authorization)
3. [Multi-Tenant Scoping (`X-Company-ID`)](#3-multi-tenant-scoping-x-company-id)
4. [Building an API Endpoint (5-Step Walkthrough)](#4-building-an-api-endpoint-5-step-walkthrough)
5. [Interactive API Documentation (Dedoc Scramble)](#5-interactive-api-documentation-dedoc-scramble)
6. [Testing with Bruno (VS Code Extension)](#6-testing-with-bruno-vs-code-extension)
7. [Automated Testing with Pest](#7-automated-testing-with-pest)

---

## 1. Architecture & Request Lifecycle

All REST APIs reside under the `/api/v1/` route prefix and follow a strict, layered request pipeline:

```
[ HTTP Client / Integration ]
            │
            │ Headers:
            │   Authorization: Bearer <token>
            │   X-Company-ID: <company_id>
            │   Accept: application/json
            ▼
 [ routes/api.php ]
            │
            ▼
 [ auth:sanctum ] ──────────── Validates API token and resolves User (Human or Bot)
            │
            ▼
 [ throttle:api.v1 ] ───────── Enforces 60 requests/min rate limiting per user/IP
            │
            ▼
 [ api.company ] ───────────── Validates company membership & binds App::get('active_company_id')
            │
            ▼
 [ Api\v1\*Controller ] ────── Executes query scoped to active_company_id
            │
            ▼
 [ Api\v1\*Resource ] ──────── Serializes data according to OpenAPI 3.1 specification
            │
            ▼
 [ JSON HTTP Response ]
```

---

## 2. Authentication & Authorization

### System Bots vs Human Users
API tokens in this system are governed centrally by **Platform Admins**:
- API tokens can be created under **Admin > API Tokens** (`/admin/api-tokens`).
- When a token is created, a background **System Bot** account (`is_system = true`, e.g. `bot.integration-name@system.local`) is automatically provisioned.
- System bot accounts are excluded from the main human User Management directory (`UsersDataTable`) to keep employee lists clean.

### Request Headers Required
| Header | Value | Description |
|---|---|---|
| `Authorization` | `Bearer <plain_text_token>` | Sanctum API access token |
| `X-Company-ID` | `<integer_id>` | Active company ID for tenant-scoped routes |
| `Accept` | `application/json` | Ensures JSON error and validation responses |

---

## 3. Multi-Tenant Scoping (`X-Company-ID`)

Our application is strictly multi-company. For any tenant-specific API endpoint:
1. The route must be wrapped in the `api.company` middleware alias.
2. The client must supply the `X-Company-ID: <id>` header.
3. The `ApiSetActiveCompany` middleware verifies that:
   - The user is an attached member of the requested company, **OR**
   - The user possesses the `platform-admin` role (which has cross-company access).
4. Once verified, the active company ID is bound in Laravel's container: `App::get('active_company_id')`.

---

## 4. Building an API Endpoint (5-Step Walkthrough)

### Step 1: Create the Eloquent API Resource
Generate or create a Resource class in `app/Http/Resources/Api/v1/`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\v1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'report_number' => $this->report_number,
            'title' => $this->title,
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

### Step 2: Create the API Controller
Place your controller in `app/Http/Controllers/Api/v1/`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\v1\ExpenseReportResource;
use App\Models\ExpenseReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\App;

class ExpenseReportController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $reports = ExpenseReport::query()
            ->where('company_id', App::get('active_company_id'))
            ->with(['user'])
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return ExpenseReportResource::collection($reports);
    }

    public function show(ExpenseReport $expenseReport): JsonResponse
    {
        if ($expenseReport->company_id !== App::get('active_company_id')) {
            return response()->json(['message' => 'Resource not found in this company context.'], 403);
        }

        $expenseReport->load(['user']);

        return (new ExpenseReportResource($expenseReport))->response();
    }
}
```

### Step 3: Register the Routes in `routes/api.php`
Add the routes into the `v1` prefix group:

```php
// routes/api.php

Route::prefix('v1')->middleware(['auth:sanctum', 'throttle:api.v1'])->group(function () {
    
    // Company Scoped Routes
    Route::middleware(['api.company'])->group(function () {
        Route::get('expenses', [ExpenseReportController::class, 'index']);
        Route::get('expenses/{expenseReport}', [ExpenseReportController::class, 'show']);
    });
});
```

---

## 5. Interactive API Documentation (Dedoc Scramble)

Our API documentation is powered by **Dedoc Scramble**, which parses your PHP types, FormRequests, and Eloquent Resources automatically to generate a comprehensive **OpenAPI 3.1** specification without manual annotation clutter.

### Documentation Endpoints & Access
- **Interactive Stoplight Elements UI**: `http://localhost:8000/docs/api` (or `http://localhost:8080/docs/api` in Docker)
- **OpenAPI 3.1 JSON Spec**: `http://localhost:8000/docs/api.json`
- **Navigation Shortcuts**:
  - Accessible via the **API Docs** link in the Admin Sidebar (`@can('manage-platform')`).
  - Accessible via the **View API Docs** button on the API Tokens page (`/admin/api-tokens`).

---

### Writing Code for Rich Documentation

Scramble uses AST reflection to analyze your code. Follow these conventions to ensure accurate, high-quality documentation:

#### 1. Method PHPDoc Blocks (Titles & Descriptions)
The first line of your controller method docblock becomes the **Endpoint Title**, and subsequent lines become the **Description**:

```php
/**
 * List Expense Reports
 *
 * Retrieve a paginated list of expense reports scoped strictly to the authenticated
 * company context provided via the X-Company-ID header.
 *
 * @param Request $request
 * @return AnonymousResourceCollection<ExpenseReportResource>
 */
public function index(Request $request): AnonymousResourceCollection
{
    // ...
}
```

#### 2. Automatic FormRequest Reflection
When you inject a FormRequest (e.g. `StoreExpenseReportRequest $request`), Scramble automatically reads the `rules()` method and documents every field, its data type, `required` or `nullable` status, and regex validation rules without requiring manual `@bodyParam` annotations:

```php
public function rules(): array
{
    return [
        'title' => ['required', 'string', 'max:255'],
        'amount' => ['required', 'numeric', 'min:0.01'],
        'notes' => ['nullable', 'string'],
    ];
}
```

#### 3. Explicit Return Types on Resources
Always typehint your controller return methods (`AnonymousResourceCollection`, `JsonResponse`, or explicit Resource instances). This allows Scramble to resolve the exact JSON output schema.

---

### Testing Endpoints Live in the Browser ("Try It")

You can execute live requests directly inside the Scramble documentation interface:

1. Visit `/docs/api` in your browser.
2. In the top-right corner of the documentation page, click the **Authorize** lock button.
3. Paste your plain-text **Sanctum API Token** into the `BearerAuth` input.
4. Select any endpoint (e.g. `GET /api/v1/approvals`), click the **Try It** tab, and enter your `X-Company-ID` (e.g. `1`).
5. Click **Send Request** to view live JSON response status, headers, and payload.

---

### Exporting Static OpenAPI Specifications (CLI)

For CI/CD pipelines or generating frontend client SDKs (TypeScript / Axios), you can export the full OpenAPI specification to a static JSON file on disk:

```bash
# Export static openapi.json to project root
docker compose exec app php artisan scramble:export
```
This writes the full OpenAPI 3.1 document to the path configured in `config/scramble.php` (`export_path`).

---

## 6. Testing with Bruno (VS Code Extension)

We maintain version-controlled `.bru` request collections in the `bruno/` directory:

1. Open the **Bruno** extension tab in VS Code.
2. Click **Open Collection** and select the `bruno/` folder.
3. In `bruno/environments/Local.bru`, configure:
   ```bru
   vars {
     baseUrl: http://localhost:8000
     companyId: 1
   }
   vars:secret [
     apiToken
   ]
   ```
4. Paste your token into `apiToken` to authenticate all inherited requests automatically.

---

## 7. Automated Testing with Pest

Every new API module must be accompanied by a Pest test in `tests/Feature/Api/v1/`:

```php
<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\ExpenseReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lists expense reports scoped to active company', function () {
    $company = Company::factory()->create();
    $otherCompany = Company::factory()->create();

    $user = User::factory()->system()->create();
    $user->companies()->attach($company->id);

    ExpenseReport::factory()->count(2)->create(['company_id' => $company->id]);
    ExpenseReport::factory()->create(['company_id' => $otherCompany->id]);

    $token = $user->createToken('Test API')->plainTextToken;

    $response = $this->getJson('/api/v1/expenses', [
        'Authorization' => 'Bearer ' . $token,
        'X-Company-ID' => (string) $company->id,
    ]);

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});
```

Run all tests:
```bash
docker compose exec app composer test
```
