# ROADMAP

Scaffolding App — a multi-tenant SaaS starter kit for client projects.
Covers authentication, company hierarchy, role-based access, Livewire v4 SPA reactivity, and reusable modules.

## Already Built ✅

| Feature | Details |
|---|---|
| **Auth** | Login, register, password reset |
| **Multi-tenancy** | Companies with parent/child hierarchy (subsidiary tree), per-company session scoping via middleware |
| **Roles & Permissions** | Sysadmin bypass role, per-company role assignment via `company_user_role` pivot, `hasRole()` / `hasAnyRole()` methods |
| **Admin Panel** | Companies CRUD, Roles CRUD, User management, Org chart (recursive nested tree with user counts) |
| **DataTable** | Reusable server-rendered component + modern reactive Livewire v4 DataTable standard |
| **Approval Module** | Polymorphic approval engine with sequential/parallel workflow templates, runtime delegation & cancellation |
| **Notification Module** | Real-time Livewire notification bell, database in-app alerts, dynamic email templates, company auto-switching |
| **Audit Trail** | Polymorphic activity logging with tenant-scoped UI viewer (`yammi-audit-log-laravel`) |
| **API Layer** | Versioned REST API (`/api/v1/`), token governance, Scramble OpenAPI 3.1 documentation, Bruno collection |
| **Authentication Overhaul** | Per-user `auth_method` + system provider configuration (LDAP, Google, Microsoft, Auth0, SAML 2.0) |
| **User Impersonation** | Zero-flicker impersonation bar, cross-tab session synchronization, audit compliance |
| **System Configuration** | Full Livewire v4 SPA settings suite: General, 20 Themes, Mail/SMTP, Feature Toggles, Custom Variables, Email Template Builder |

---

## Livewire v4 SPA Migration Roadmap

```
┌───────────────────────────────────────────────────────────────────────────────────┐
│                        LIVEWIRE v4 SPA MIGRATION ROADMAP                          │
├───────────────────────────────────────────────────────────────────────────────────┤
│ [✓] Phase 1: Foundation & Navigation Architecture (100% Completed)                │
│     ├── 1.1 Livewire v4 SPA & Asset Core Setup                                    │
│     ├── 1.2 Multi-Company & Role Navigation Switcher                              │
│     ├── 1.3 Real-Time Notification Bell & Counter Popover (NotificationBell.php)   │
│     ├── 1.4 Universal Inactivity & Cross-Tab Session Monitor                      │
│     └── 1.5 Zero-Flicker Impersonation Bar & Cross-Tab Syncer                     │
├───────────────────────────────────────────────────────────────────────────────────┤
│ [~] Phase 2: Core Management Modules & Unified DataTables (In Progress)           │
│     ├── 2.1 User Management Livewire SPA (/admin/users) [COMPLETED]              │
│     ├── 2.2 Livewire DataTable Component Standard (UserTable.php) [COMPLETED]     │
│     ├── 2.3 Role & Permission Management (/admin/roles) [QUEUED - NEXT UP]       │
│     ├── 2.4 API Token Governance (/admin/api-tokens) [QUEUED]                     │
│     └── 2.5 Company Hierarchy & Org Chart (/admin/companies) [QUEUED]             │
├───────────────────────────────────────────────────────────────────────────────────┤
│ [✓] Phase 3: System Administration & Settings (100% Completed & 374/374 Tests)    │
│     ├── 3.0 Settings Layout & Tab Navigation (wire:navigate SPA)                  │
│     ├── 3.1 General & Localization Settings (GeneralSettings.php)                 │
│     ├── 3.2 Company Preferences & 20-Theme Brand Palette (CompanySettings.php)    │
│     ├── 3.3 Mail & SMTP Settings with Live Connection Tester (MailSettings.php)   │
│     ├── 3.4 Feature Toggles Platform Governance (FeatureToggles.php)              │
│     ├── 3.5 Custom Variables Key-Value Parameter Editor (CustomVariables.php)     │
│     └── 3.6 Email Template Builder with Quill & Tokens (TemplateList/Editor.php)  │
├───────────────────────────────────────────────────────────────────────────────────┤
│ [ ] Phase 4: Multi-Step Business Domain Modules (Queued)                          │
│     ├── 4.1 Dynamic Approval Workflow Template Builder                            │
│     ├── 4.2 Interactive Unified Approval Inbox & History                          │
│     └── 4.3 Purchase Request Multi-Step Wizard (/purchase-requests/create)        │
├───────────────────────────────────────────────────────────────────────────────────┤
│ [ ] Phase 5: High-Performance Analytics, Auditing & API (Queued)                  │
│     ├── 5.1 Real-time Metrics Dashboard & ApexCharts                              │
│     ├── 5.2 Audit Trail Log Viewer                                                │
│     └── 5.3 Scramble Interactive OpenAPI Console                                  │
└───────────────────────────────────────────────────────────────────────────────────┘
```

---

## Summary Checklist

| # | Feature / Milestone | Priority | Status |
|---|---|---|---|
| 1 | Auth (login, register, password reset) | Built | ✅ Done |
| 2 | Multi-tenancy (parent/child company hierarchy) | Built | ✅ Done |
| 3 | Roles & Permissions (per-company) | Built | ✅ Done |
| 4 | Admin Panel (Companies, Roles, Users, Org Chart) | Built | ✅ Done |
| 5 | DataTable Component Standard | Built | ✅ Done |
| 6 | Approval Module (Core Engine & Events) | P0 | ✅ Done |
| 7 | Notification Module (Engine & Bell) | P0 | ✅ Done |
| 8 | Audit Trail (Yammi Activity Logs) | P1 | ✅ Done |
| 9 | API Layer (Sanctum, OpenAPI, Scramble) | P1 | ✅ Done |
| 10 | Authentication Overhaul (LDAP, OAuth, SAML) | P1 | ✅ Done |
| 11 | User Impersonation | P1 | ✅ Done |
| 12 | System Configuration (Livewire v4 SPA Suite) | P2 | ✅ Done |
| 13 | Phase 1 Foundation & Nav Architecture | SPA | ✅ Done |
| 14 | Phase 2.1 User Management Livewire SPA | SPA | ✅ Done |
| 15 | Phase 3 Settings Livewire SPA Suite (3.1 - 3.6) | SPA | ✅ Done |
| 16 | Phase 2.3-2.5 Roles, Tokens & Org Chart SPA | SPA | ⏳ Queued |
| 17 | Phase 4 Approvals & PR Multi-Step Wizards | SPA | ⏳ Queued |
| 18 | Phase 5 Dashboard Analytics & Auditing | SPA | ⏳ Queued |
