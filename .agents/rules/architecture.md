---
description: "Triggers when creating, modifying, refactoring, or reviewing any controller, Livewire component, form request, domain action, or backend service in root app/ or app-modules/. Enforces the 3-Layer Architecture Pattern (Thin Controllers & Livewire Components, Form Requests, Domain Actions), strict typing, transactional boundaries, and tenant authorization."
---

# Architecture Rules: 3-Layer Pattern (Controller/Livewire ➡️ Request ➡️ Action)

Whenever creating or modifying backend features or interactive UI in `app/` or `app-modules/`, strictly follow the **3-Layer Architecture Pattern**:

## 1. Presentation Layer

### Thin Controllers (`app/Http/Controllers/...` or `Modules\<Module>\Http\Controllers\...`)
- **Pure HTTP Orchestration**: Controllers should ONLY extract inputs, inject Form Requests & Domain Actions, execute the action, and return responses (View, Redirect, or Json).
- **No Business Logic**: Do not write database queries, raw Eloquent persistence, or transaction handling inside controllers.

### Livewire v4 Components (`app/Livewire/...` or `Modules\<Module>\Livewire\...`)
- **Interactive UI Orchestration**: Livewire v4 components manage reactive UI state, modal visibility, search inputs, active tabs, and pagination.
- **Strict Typing**: Must declare `declare(strict_types=1);` and be defined as `final class`.
- **PHP 8.4 Attributes**: Use `#[Computed]` for cached database queries, `#[Url]` for query string parameters, and `#[Validate]` or Livewire Form Objects for validation.
- **Delegation to Actions**: Livewire components must NEVER perform raw database transactions or heavy entity mutations directly in component methods. Always inject and delegate to **Domain Actions** (`$action->execute(...)`).
- **SPA Navigation**: Use `wire:navigate` and `wire:navigate.hover` on all internal links for instant SPA navigation without full-page reloads.

## 2. Validation & Authorization Layer (Form Requests & Rules)
- **Form Requests (`app/Http/Requests/...` or `Modules\<Module>\Http\Requests\...`)**:
  - Must begin with `declare(strict_types=1);` and be defined as `final class`.
  - Implement `authorize(): bool` checking Spatie permissions (`$this->user()?->can(...)`), platform access (`$this->user()?->hasPlatformAccess()`), or active company ownership.
  - Define clean validation rules in `rules(): array`.
  - **No Mutations**: Do not perform database mutations, queries, or side-effects inside Form Requests.

## 3. Domain Business Layer (Domain Actions)
- **Domain Actions (`app/Actions/...` or `Modules\<Module>\Actions\...`)**:
  - **Single Responsibility**: Each action class represents one discrete business operation (e.g. `CreateUserAction`, `UpdateCompanyAction`, `ApproveStepAction`).
  - **Strict Typing**: Must declare `declare(strict_types=1);`, `final class`, and a single public `execute(...)` method with explicit parameter and return types.
  - **Transactional Integrity**: Wrap multi-table operations and database mutations inside `DB::transaction()`.
  - **Side Effects**: Handle file storage, pivot assignments, events (`event(...)`), and notifications (`NotificationEngine`) inside the action.
  - **Independence**: Actions must not access HTTP `Request` or `Session` directly; accept typed scalar values, models, or array shapes.

## 4. Multi-Tenancy & Authorization Boundaries
- Always scope operations to the active tenant (`$user->getActiveCompany()` or `api.company` middleware).
- Platform administrators (`$user->hasPlatformAccess()`) bypass company boundaries where explicitly designed (e.g. role name `'sysadmin'` or `'platform-admin'`).
