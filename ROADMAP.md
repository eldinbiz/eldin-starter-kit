# ROADMAP

Scaffolding App — a multi-tenant SaaS starter kit for client projects.
Covers authentication, company hierarchy, role-based access, and reusable modules.

## Already Built ✅

| Feature | Details |
|---|---|
| **Auth** | Login, register, password reset |
| **Multi-tenancy** | Companies with parent/child hierarchy (subsidiary tree), per-company session scoping via middleware |
| **Roles & Permissions** | Platform-admin bypass role, per-company role assignment via `company_user_role` pivot, `hasRole()` / `hasAnyRole()` methods |
| **Admin Panel** | Companies CRUD, Roles CRUD, User management, Org chart (recursive nested tree with user counts) |
| **DataTable** | Reusable server-rendered component: sort, search, filter, paginate, responsive mobile cards |

---

## P0 — Foundation (v1.0)

### 1. Approval Module ✅ Done

Polymorphic approval engine. Any model implements an `Approvable` interface/contract and the approval pipeline handles the rest. When fully approved, the module dispatches `ApprovalCompleted` so the requesting module executes its pending transaction.

**Workflow Templates** (per-company, configured via admin UI):

Templates contain ordered **groups**. Groups contain **steps**.

| Group Type | Behavior |
|---|---|
| **Sequential** | Steps approve one at a time, in order |
| **Parallel** | All steps can be approved concurrently |

Groups execute in sequence: Group 1 must fully complete before Group 2 begins, and so on. An approval request is "fully approved" only when all groups complete.

**Step Resolution Types — who becomes the approver at runtime:**

| Type | Resolves To |
|---|---|
| `N+1` | Requestor's direct manager (from `company_user.manager_id`) |
| `N+2` | Requestor's manager's manager |
| `specific_user` | A named user selected from the current company **or any ancestor company** |
| `N+1_of_previous` | The previous step approver's manager |

**Runtime Capabilities:**
- **Delegation:** reassign an approver on the fly without editing the template
- **Cancellation:** requestor can cancel a pending approval request entirely

**State Machine (approval request level):**
```
Pending ──→ Approved
        ──→ Rejected
        ──→ Canceled (by requestor)
```

**Design Notes:**
- Polymorphic via `approval_requests` table with `morphs('approvable')`
- Each workflow step has its own state (pending/approved/rejected) tracked in a pivot
- Event-driven: dispatch `ApprovalStepCompleted`, `ApprovalCompleted`, `ApprovalRejected`, `ApprovalCanceled`

---

### 2. Notification Module ✅ Done

**Channels:**
- **In-app:** bell icon in topbar navbar, dropdown with unread badge count, persisted in database, mark-as-read / mark-all-read
- **Email:** via Laravel's Mail facade, configurable per-company mail driver

**Approval Lifecycle Triggers:**

| Event | Recipients |
|---|---|
| Approval requested | All approvers in the current step |
| Step approved | Requestor + next step approvers (if any) |
| Step rejected | Requestor |
| Fully approved | Requestor |
| Canceled | All pending approvers |
| Delegated | Newly assigned approver |

**Extensibility:** future modules register notification types and templates via a service provider pattern.

---

## P1 — High Priority (v1.1)

### 3. Audit Trail ✅ Done

- Polymorphic activity log: `actor` (who), `action` (what), `subject` (on what model), `before` / `after` (JSON delta), `timestamp`
- Covers: Eloquent model events (created/updated/deleted), approval events, role assignment changes, login/logout
- Per-company filterable viewer in admin panel (date range, actor, action type, subject type)
- Immutable: append-only, no edit or delete operations

---

### 4. API Layer ✅ Done

REST/JSON API architecture with centralized token governance, per-endpoint abilities, interactive documentation, and multi-tenant scoping.

**Key Deliverables:**
- **System Service Account Separation**: Tokens are bound to isolated System Bots (`is_system = true` on `users` table), cleanly segregated from human directory listings (`User::human()` scope).
- **API Token Governance UI**: Bespoke management panel (`/admin/api-tokens`) built on the reusable server-rendered DataTable engine (`ApiTokensDataTable`) with sorting, live search, status filters (`Active`, `Expiring Soon`, `Expired`, `Never`), and responsive mobile card layout.
- **Granular Per-Endpoint Abilities**: 1-checkbox-per-endpoint permissions in the token generator modal mapped to Sanctum route abilities (e.g., `approval:read`, `purchase:read`, `user:read`, `role:read`, `company:read`, `notification:read`, `audit:read`).
- **Interactive Scopes Inspection**: Clickable token scopes badges opening an interactive modal detailing all granted endpoints with deep links (`[Docs ↗]`) to the exact OpenAPI operation card.
- **Token Expiration & Lifecycle Governance**: Presets (`30d`, `60d`, `90d`, `1yr`, `Never`) with automated daily cleanup via scheduled `sanctum:prune-expired --hours=24`.
- **Dedicated Access Control**: Protected by dedicated `'manage-api-tokens'` permission under System Administration with strict active-role gate enforcement.
- **Multi-Tenant Company Scoping**: Stateless context resolution via `ApiSetActiveCompany` middleware and `X-Company-ID` HTTP header, with platform-admin cross-company overrides.
- **Versioned Endpoints (`/api/v1/...`) & Eloquent API Resources**: Standardized JSON schemas across Auth, Companies, Users, Roles, Approvals, Purchase Requests, Notifications, and Audit Logs.
- **Interactive OpenAPI 3.1 Documentation**: Powered by `dedoc/scramble` and Stoplight Elements at `/docs/api` with Bearer auth schema and operationId deep-linking.
- **Bruno API Collection**: Complete ready-to-use collection scaffolded in `bruno/` with environments and pre-configured Bearer Auth.
- **Developer Guide & AI Skill**: Comprehensive standards in `docs/API_LAYER_GUIDE.md` and AI workflow automation in `.agents/skills/api-layer-integration/SKILL.md`.
- **Automated Test Suite**: 100% Pest test coverage covering token lifecycle, permissions, scopes enforcement, multi-tenancy, and API endpoints.

---

### 5. Authentication Overhaul ✅ Done

Per-user configurable authentication method with system-level provider configuration.

**Two-tier model:**
- **Per-user `auth_method`** on the `users` table (`local`, `ldap`, `google`, `microsoft`, `auth0`, `saml2`). Determines how this specific user authenticates — globally, regardless of which company they belong to. Managed in User Management.
- **System-level provider config**: platform admin configures connection details once at the application level under System Configuration (`/admin/settings/auth-providers`) in tabbed UI:
  - Local: Notice banner *"authentication will refer to user account data. no configuration necessary"*
  - LDAP: host, port, base DN, encryption
  - Google OAuth: client ID, client secret
  - Microsoft OAuth: tenant ID, client ID, client secret (`socialiteproviders/microsoft`)
  - Auth0: domain, client ID, client secret (`socialiteproviders/auth0`)
  - SAML 2.0: IdP entity ID, SSO URL, metadata URL, certificate (`socialiteproviders/saml2`)
- **Test Connection**: Live real-time connection testing button on each tab verifying server reachability, OIDC discovery endpoints, and OAuth client credential validity.

**Login flow:**
Dynamic 2-step flow: User enters email → system looks up user → reads `auth_method` → routes to password prompt (local/ldap) or redirects to SSO endpoint (`google`, `microsoft`, `auth0`, `saml2`) → authenticates.

---

### 6. User Impersonation ✅ Done

- **Package Integration**: Implemented using `lab404/laravel-impersonate` package.
- **Explicit Permission Gate**: Initiating an impersonation session strictly requires holding the explicit `user-impersonation` permission (defined in `canImpersonate()` on `User` model).
- **Session & UI Banner**: Secure session switching with sticky impersonation alert banner in topbar showing target user details and "Stop Impersonating" action.
- **Audit Trail**: Dispatches activity log events (`TakeImpersonation`, `LeaveImpersonation`) for security audit compliance.

---

## P2 — Future (v1.2+)

### 7. System Configuration Module 🔄 In Progress

Unified system configuration architecture spanning application-level platform administration, per-company tenant preferences, customizable email notification templates, and dynamic feature toggles.

**1. App-Level Settings (Platform Admin only):**
- **General Branding:** Application name, logo, favicon, dark mode defaults.
- **Localization:** Default system timezone, date/time format, locale.
- **Mail & Transport:** Mail driver, SMTP host/port/credentials (securely encrypted), sender identity, and live test email dispatch.

**2. Per-Company Settings (Tenant Admin & Platform Admin):**
- **Tenant Preferences:** Base currency, timezone override, custom date format preferences.
- **Company Branding:** Custom company logo, accent/primary color theme.
- **Workflow & Operational Rules:** Approval threshold amounts, escalation defaults.

**3. Editable Email Templates:**
- Visual template editor/manager for system and notification event triggers (approval requested, step completed, rejection, welcome, etc.).
- Dynamic placeholder tokens: `{{ user_name }}`, `{{ company_name }}`, `{{ approval_link }}`, `{{ app_name }}`, `{{ action_url }}`.
- Live test send and preview rendering.

**4. Feature Toggles & Module Management:**
- Granular enable/disable toggles for platform modules per company (e.g., Approvals, Purchase Requests, Audit Logs, API Access).
- Middleware enforcement and responsive sidebar/navigation visibility adapting to enabled features.

**5. Authentication Providers:**
- Managed by the [Authentication Overhaul](#5-authentication-overhaul) module (P1). Per-user `auth_method` column + system-level provider configuration for LDAP, Google, Microsoft, Auth0, and SAML 2.0 under `/admin/settings/auth-providers`.

---

## Summary Checklist

| # | Feature | Priority | Status |
|---|---|---|---|
| 1 | Auth (login, register, password reset) | Built | ✅ Done |
| 2 | Multi-tenancy (parent/child company hierarchy) | Built | ✅ Done |
| 3 | Roles & Permissions (per-company) | Built | ✅ Done |
| 4 | Admin Panel (Companies, Roles, Users, Org Chart) | Built | ✅ Done |
| 5 | DataTable component | Built | ✅ Done |
| 6 | Approval Module | P0 | ✅ Done |
| 7 | Notification Module | P0 | ✅ Done |
| 8 | Audit Trail | P1 | ✅ Done |
| 9 | API Layer | P1 | ✅ Done |
| 10 | Authentication Overhaul | P1 | ✅ Done |
| 11 | User Impersonation | P1 | ✅ Done |
| 12 | System Configuration | P2 | 🔄 In Progress |
