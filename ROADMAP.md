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

### 1. Approval Module

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

### 2. Notification Module

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

### 3. Audit Trail

- Polymorphic activity log: `actor` (who), `action` (what), `subject` (on what model), `before` / `after` (JSON delta), `timestamp`
- Covers: Eloquent model events (created/updated/deleted), approval events, role assignment changes, login/logout
- Per-company filterable viewer in admin panel (date range, actor, action type, subject type)
- Immutable: append-only, no edit or delete operations

---

### 4. API Layer

- REST/JSON API with Laravel Sanctum token-based authentication
- Eloquent API Resources for consistent response structure
- Versioned: `/api/v1/...`
- Multi-tenant aware: all queries scoped to the authenticated user's active company
- Rate limiting on auth endpoints and all API routes

---

### 5. Authentication Overhaul

Per-user configurable authentication method with system-level provider configuration.

**Two-tier model:**
- **Per-user `auth_method`** on the `users` table (`local`, `ldap`, `google`, `microsoft`, `auth0`, `saml`, `openid`). Determines how this specific user authenticates — globally, regardless of which company they belong to.
- **System-level provider config**: platform admin configures connection details once at the application level:
  - LDAP: host, port, base DN, bind DN, encryption
  - Google OAuth: client ID, client secret
  - Microsoft OAuth: tenant ID, client ID, client secret
  - Auth0: domain, client ID, client secret
  - SAML 2.0: IdP entity ID, SSO URL, x.509 certificate
  - OpenID Connect: issuer URL, client ID, client secret

**Login flow:**
User enters email → system looks up user → reads `auth_method` → routes to the correct authentication provider using system-wide config → authenticates → company selector (if user belongs to multiple companies).

**Supported providers:**
- Local user/password (already built)
- LDAP
- SSO: Google, Microsoft, Auth0
- SAML 2.0
- OpenID Connect

---

## P2 — Future (v1.2+)

### 6. System Configuration Module

**App-Level Settings** (platform admin only):
- Application name, logo, favicon
- Default timezone, date format, locale
- Mail driver and SMTP configuration

**Per-Company Settings:**
- Currency, date/time format preferences
- Branding (company logo, primary color)
- Approval-related thresholds (future use)

**Email Templates:**
- Editable HTML templates per notification type
- Variable placeholders: `{{ user_name }}`, `{{ company_name }}`, `{{ approval_link }}`, etc.

**Authentication Providers:**
- Managed by the [Authentication Overhaul](#5-authentication-overhaul) module (P1). Per-user `auth_method` column + system-level provider configuration for LDAP, Google, Microsoft, Auth0, SAML 2.0, and OpenID Connect.

**Feature Toggles:**
- Enable/disable modules per company

---

## Summary Checklist

| # | Feature | Priority | Status |
|---|---|---|---|
| 1 | Auth (login, register, password reset) | Built | ✅ Done |
| 2 | Multi-tenancy (parent/child company hierarchy) | Built | ✅ Done |
| 3 | Roles & Permissions (per-company) | Built | ✅ Done |
| 4 | Admin Panel (Companies, Roles, Users, Org Chart) | Built | ✅ Done |
| 5 | DataTable component | Built | ✅ Done |
| 6 | Approval Module | P0 | ☐ To Do |
| 7 | Notification Module | P0 | ☐ To Do |
| 8 | Audit Trail | P1 | ☐ To Do |
| 9 | API Layer | P1 | ☐ To Do |
| 10 | Authentication Overhaul | P1 | ☐ To Do |
| 11 | System Configuration | P2 | ☐ To Do |
