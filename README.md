# Enterprise Business Application Starter Kit

<p align="left">
  <img src="https://img.shields.io/badge/Laravel-12.x%20%7C%2013.x-FF2D20?style=flat-square&logo=laravel&logoColor=white" alt="Laravel Version">
  <img src="https://img.shields.io/badge/PHP-8.3%20%7C%208.4-777BB4?style=flat-square&logo=php&logoColor=white" alt="PHP Version">
  <img src="https://img.shields.io/badge/Tests-passing-22c55e?style=flat-square&logo=pest&logoColor=white" alt="Tests Passed">
  <img src="https://img.shields.io/badge/Pint-passing-22c55e?style=flat-square&logo=laravel&logoColor=white" alt="Pint Passing">
  <img src="https://img.shields.io/badge/Livewire-v4-FB70A9?style=flat-square&logo=livewire&logoColor=white" alt="Livewire Version">
  <img src="https://img.shields.io/badge/Security-pending%20audit-eab308?style=flat-square&logo=shield&logoColor=white" alt="Security Pending Audit">
</p>

A multi-tenant Starter Kit app built on **Laravel 13**, **Livewire v4**, **PHP 8.4**, **Tailwind CSS v4**, and **Pest 5**. 

Designed as a comprehensive **Starter Kit for System Builders and Software Developers** building business-focused web applications—such as ERPs, CRMs, Procurement systems, HRMS portals, and multi-tenant SaaS products.

---

## 🎯 Purpose & Value Proposition

When developing enterprise software, engineering teams typically spend **2 to 3 months** implementing standard plumbing before writing a single line of business logic. 

This starter kit eliminates that repetitive effort by providing pre-built, battle-tested solutions for common business requirements:

| Common Business Challenge | Pre-built Solution in This Starter Kit |
|---|---|
| **Multi-Tenancy & Corporate Structures** | Native company context resolution, parent-subsidiary organizational trees, and tenant-level theme branding overrides. |
| **Document Approval Pipelines** | Polymorphic approval workflow engine supporting multi-step sequential and parallel approvals, manager escalations, and role resolvers. |
| **Transactional Email & Notifications** | Dynamic database email templates with in-browser Quill WYSIWYG authoring, token interpolation, and in-app notification center. |
| **Feature Flags & Modular Monetization** | 3-tier cascading feature toggle system with per-company runtime overrides, `@feature` Blade directives, and admin management UI. |
| **Security, RBAC & Org Hierarchy** | Spatie permission management, company-scoped roles, interactive visual org chart, and manager cycle prevention. |
| **Machine-to-Machine API Integrations** | Stateless REST API (`/api/v1/`) with Sanctum token abilities, auto-generated OpenAPI 3.1 docs via Dedoc Scramble, and Bruno test suites. |
| **Compliance & Traceability** | Automated model, auth, and system event audit logging dashboard. |
| **Single-Page Application UX** | Real-time Livewire v4 reactivity with instant `wire:navigate` SPA transitions, zero full-page reloads, and Alpine.js v3. |
| **Clean Modular Architecture** | Modular monolith design powered by `internachi/modular`, isolating domain features inside `app-modules/` as standard local Composer packages. |

---

## 🏗️ Architectural Foundations

### 1. Modular Monolith Architecture (`internachi/modular`)
The application divides core platform infrastructure from domain-specific business features:

```
┌────────────────────────────────────────────────────────┐
│                   Application Core                     │
│                        (app/)                          │
│  - Multi-tenancy context & Active Company Resolution   │
│  - Authentication & Spatie RBAC                        │
│  - Feature Toggle Engine (config/features.php)         │
│  - Centralized Approval Engine (Approvable contract)   │
│  - Notification Engine & Dynamic Email Templates       │
│  - Global Layouts & UI Theme (TailAdmin / Tailwind v4) │
└────────────────────────────────────────────────────────┘
                           ▲
                           │ depends on core contracts
                           ▼
┌────────────────────────────────────────────────────────┐
│                   Domain Modules                       │
│                    (app-modules/)                      │
│  - app-modules/purchase-requests/                      │
│  - app-modules/invoices/                               │
│  - app-modules/inventory/                              │
│  - app-modules/leave-management/                       │
└────────────────────────────────────────────────────────┘
```

### 2. The 3-Layer + Dual Presentation Standard

To prevent business logic duplication, both the **Web SPA (Livewire v4)** and **External REST API (`/api/v1/`)** serve as thin presentation layers that delegate state mutations to single-purpose **Domain Actions**:

```
┌──────────────────────────────────────┐     ┌──────────────────────────────────────┐
│       Web SPA (Browser Users)        │     │     External API / System Bots       │
│   Livewire v4 Component / Alpine     │     │    Sanctum Token + X-Company-ID      │
└──────────────────┬───────────────────┘     └──────────────────┬───────────────────┘
                   │                                            │
                   │ validates input                            │ validates FormRequest
                   ▼                                            ▼
┌───────────────────────────────────────────────────────────────────────────────────┐
│                     1. Thin Presentation & Validation Layer                       │
│ - Livewire Component methods (save, submit) / API Controllers (store, update)     │
│ - Livewire #[Validate] / Form Requests (authorize() & rules())                     │
└─────────────────────────────────────────┬─────────────────────────────────────────┘
                                          │
                                          │ delegates execution ($action->execute())
                                          ▼
┌───────────────────────────────────────────────────────────────────────────────────┐
│                      2. Domain Business Layer (Domain Actions)                    │
│ ┌───────────────────────────────────────────────────────────────────────────────┐ │
│ │  DB::transaction(), Eloquent mutations, Approval Engine, Events, Notifications│ │
│ └───────────────────────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────┬─────────────────────────────────────────┘
                                          │
                   ┌──────────────────────┴──────────────────────┐
                   │                                             │
                   ▼                                             ▼
┌──────────────────────────────────────┐     ┌──────────────────────────────────────┐
│        Reactive DOM Update           │     │          JSON HTTP Response          │
│   (Livewire Morph / Alpine state)    │     │      (Eloquent API JsonResource)     │
└──────────────────────────────────────┘     └──────────────────────────────────────┘
```

---

## 📦 Built-in Core Subsystems

### 🏢 1. Multi-Tenancy & Corporate Hierarchy
- **Tenant Context**: Automatically resolves active company via session (`getActiveCompany()`) in Web requests, or via `api.company` middleware with `X-Company-ID` in API requests.
- **Hierarchical Trees**: Support for multi-tier company structures (holding companies, subsidiaries, branch offices) with ancestor/descendant policy traversal.
- **Dynamic Theming**: Per-company theme overrides (custom branding, logos, and dynamic CSS variables).

### 🚦 2. Polymorphic Approval Workflow Engine
- **Flexible Workflows**: Define sequential or parallel approval steps for any domain document (Purchase Requests, Invoices, Expenses, Leave).
- **Resolver Strategies**: Direct reporting managers, specific users, or custom role-based resolvers.
- **Approvable Contract**: Any model implementing `App\Contracts\Approvable` instantly integrates with central approval inboxes, history logs, and status badges.

### 🔔 3. Notification & Dynamic Email Template Engine
- **Event-Driven Dispatch**: Trigger notifications with a single line: `NotificationRequested::dispatch($user, $payload)`.
- **Dual Channel**: Delivers instant in-app alerts (header notification bell + `/notifications` index) and multipart HTML/plain-text emails.
- **Quill WYSIWYG Editor**: Admins can edit email templates live in the browser with token interpolation (e.g. `{{ user_name }}`, `{{ amount }}`) and per-company template overrides.

### 🎛️ 4. Feature Toggles & Module Management
- **3-Tier Cascade Resolution**: Per-Company Database Override ➔ Global Platform Setting ➔ `config/features.php` default.
- **Granular Gating**: Route middleware (`feature:<key>`), Blade directives (`@feature('<key>')`), and programmatic checks (`FeatureService::isEnabled()`).
- **No-Code Admin UI**: Toggle features on or off globally or per tenant without deploying code.

### 🛡️ 5. Role-Based Access Control (RBAC) & Org Chart
- **Granular Permissions**: Powered by Spatie Laravel Permission with company-scoped roles.
- **System Bot Accounts**: Distinguishes human users from automated system bots (`is_system = true`).
- **Interactive Org Chart**: Visualizes reporting lines, enables drag-and-drop manager reassignments, and automatically detects and prevents circular reporting dependencies.

### ⚡ 6. Stateless REST API & OpenAPI 3.1 Documentation
- **API Token Management**: Platform admins can provision scoped Sanctum Bearer tokens for external integrations.
- **Zero-Annotation Docs (Dedoc Scramble)**: Scramble reflects PHP types, Form Requests, and Eloquent Resources into interactive OpenAPI 3.1 docs at `/docs/api`.
- **Bruno Collection**: Includes version-controlled `.bru` request collections in `bruno/` for API testing.

### 📋 7. Audit Trail & Compliance Logging
- Comprehensive event and model activity trail logging for security auditing and operational tracking.

### 🎨 8. TailAdmin UI & Reactive DataTables
- Modern responsive dashboard powered by Tailwind CSS v4 and TailAdmin.
- Dark mode support, accessible Alpine.js confirmation modals with focus trapping, and reactive Livewire v4 DataTables featuring real-time debounced search, column sorting, and pagination.

---

## 🧩 Domain Modules & Reference Implementation

Business domains reside in `app-modules/<name>/`. The repository includes a complete reference module:

### `app-modules/purchase-requests/`
Demonstrates how to build an end-to-end domain feature:
- **Model & Migrations**: Implements `App\Contracts\Approvable` with `company_id` tenant scoping.
- **Livewire v4 SPA UI**: Multi-step purchase request creation wizard and reactive filtering table.
- **Approval Integration**: Submits requests directly into the core `ApprovalEngine`.
- **REST API v1 Endpoints**: Exposed under `/api/v1/purchases` with Scramble OpenAPI documentation.
- **Pest 5 Feature Tests**: Full test suite verifying wizard calculations, draft saves, and approvals.

---

## 📚 Developer Documentation Hub

Comprehensive architectural guides and specifications are located in the [`docs/`](docs/) directory:

| Guide | Description |
|---|---|
| 🎓 [**Tutorial: Building Your First Module**](docs/TUTORIAL_BUILDING_YOUR_FIRST_MODULE.md) | **Recommended starting point.** Step-by-step tutorial building a complete domain module (`purchase-requests`) integrated with all core subsystems. |
| 🚀 [**Environment Setup & Development Modes**](docs/ENVIRONMENT_SETUP_GUIDE.md) | Complete guide for Docker + MariaDB and Local PHP + SQLite workflows, branch switching, queue workers, and image rebuilds. |
| 🔄 [**Local ⇋ Docker Transition Manual**](docs/ENVIRONMENT_SWITCHING_MANUAL.md) | Exhaustive transition playbooks, dual-sync workflows, side-by-side CLI command equivalency matrix, and troubleshooting. |
| 📐 [**Developer & Architecture Handbook**](docs/DEVELOPER_GUIDE.md) | Foundational 3-Layer + Livewire v4 SPA architecture, shared domain actions, coding standards, and blueprint checklist. |
| 📦 [**Modular Architecture Guide (`internachi/modular`)**](docs/MODULAR_ARCHITECTURE_GUIDE.md) | Module lifecycle, directory anatomy, Composer path repositories, Artisan `--module` CLI, and scaffolding steps. |
| ⚡ [**REST API Developer Guide & OpenAPI**](docs/API_LAYER_GUIDE.md) | REST API v1 architecture, multi-tenant headers (`X-Company-ID`), Sanctum tokens, Dedoc Scramble, and Bruno test collections. |
| 🎛️ [**Feature Toggles & Module Management Guide**](docs/developer-guide-feature-toggles.md) | Feature registry, 3-tier cascade resolution, `@feature` Blade directive, and per-tenant overrides. |
| 🔔 [**Notification & Email Templates Guide**](docs/developer-guide-notifications-and-emails.md) | In-app alerts, `NotificationPayload`, Quill WYSIWYG database templates, and dynamic SMTP/OAuth configurations. |

---

## ⚡ 30-Second Quick Start

Choose either Docker or Local PHP mode:

### Option A: Docker + MariaDB (Full Stack)
```bash
copy .env.example .env
docker compose up -d --build
docker compose exec app php artisan key:generate
docker compose exec app php artisan storage:link
docker compose exec app php artisan migrate:fresh --seed
docker compose exec app php artisan audit-log:ui enable
```
👉 Open **http://localhost:8080** *(API docs: http://localhost:8080/docs/api)*

### Option B: Local PHP + SQLite (Lightweight)
```bash
composer setup
composer dev
```
👉 Open **http://localhost:8000** *(API docs: http://localhost:8000/docs/api)*

> For detailed environment switching, dual-sync workflows, queue scaling, and container rebuild instructions, see the [Environment Transition Manual](docs/ENVIRONMENT_SWITCHING_MANUAL.md) and [Environment Setup Guide](docs/ENVIRONMENT_SETUP_GUIDE.md).

---

## 🧪 Testing & Quality Assurance

We enforce Test-Driven Development (TDD) using **Pest 5**. Tests execute against an isolated in-memory SQLite database:

```bash
# Run the complete test suite (430+ tests)
./test

# Or in Docker mode:
docker compose exec app ./test
```

---

## 📄 License

This project is open-sourced software licensed under the [MIT license](LICENSE).
