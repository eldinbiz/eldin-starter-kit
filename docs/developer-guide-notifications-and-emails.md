# Developer Guide: Notification & Email Module Integration

A complete, step-by-step developer guide for integrating any application module (e.g., Invoices, Purchase Requests, Leave Requests, Tickets, Orders) with the centralized **Notification Engine** and **Email Template System**.

---

## Table of Contents
1. [Quick Start in 60 Seconds](#1-quick-start-in-60-seconds)
2. [Architecture Overview](#2-architecture-overview)
3. [The `NotificationPayload` Value Object](#3-the-notificationpayload-value-object)
4. [Supported Recipient Formats](#4-supported-recipient-formats)
5. [The 3 Integration Patterns](#5-the-3-integration-patterns)
6. [Email Templating & Token Interpolation](#6-email-templating--token-interpolation)
7. [Multi-Tenant Scoping & Auto-Switching](#7-multi-tenant-scoping--auto-switching)
8. [Direct Template Rendering (PDFs & Custom Mails)](#8-direct-template-rendering-pdfs--custom-mails)
9. [Complete Real-World Example: Invoice Module](#9-complete-real-world-example-invoice-module)
10. [Testing Your Integration with Pest](#10-testing-your-integration-with-pest)
11. [Troubleshooting & Best Practices](#11-troubleshooting--best-practices)

---

## 1. Quick Start in 60 Seconds

To trigger an in-app notification bell alert and a branded, multi-tenant HTML email from any controller, service, or job:

```php
use App\Events\NotificationRequested;
use App\ValueObjects\NotificationPayload;

// 1. Dispatch notification event
NotificationRequested::dispatch(
    recipients: $recipientUser, // User model, User ID, Collection, or Array
    payload: new NotificationPayload(
        title: 'Invoice #INV-2026-089 Ready',
        message: 'Your monthly billing invoice is ready for download.',
        type: 'invoice_ready', // ◄── Matches 'event' key in Email Templates
        url: route('invoices.show', $invoice->id),
        actionText: 'View Invoice',
        companyId: $invoice->company_id, // ◄── Respects company template overrides
        companyName: $invoice->company->name,
        icon: 'document-text',
        level: 'info', // 'info', 'success', 'warning', 'danger'
        variables: [ // ◄── Custom tokens available in email template
            'invoice_number' => 'INV-2026-089',
            'total_amount'   => '$1,450.00',
            'due_date'       => '2026-09-15',
        ]
    )
);
```

**That's it!** The system will:
- Insert the notification into the database (showing in the header bell dropdown).
- Queue the email via `AppNotification`.
- Look up the `invoice_ready` database email template (respecting company overrides).
- Substitute all placeholders (`{{ user_name }}`, `{{ invoice_number }}`, `{{ total_amount }}`, etc.).
- Deliver the email using the active SMTP/OAuth transport.

---

## 2. Architecture Overview

The system uses an **event-driven, decoupled pipeline**:

```
┌─────────────────────────────────────────────────────────────────────────────┐
│ 1. Your Domain Code (Controller / Service / Observer / Event)               │
│    Calls: NotificationRequested::dispatch($recipients, $payload)            │
└──────────────────────────────────────┬──────────────────────────────────────┘
                                       │
                                       ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│ 2. SendNotificationListener ──► NotificationEngine::send()                  │
│    - Resolves polymorphic recipients (single model, collections, IDs)       │
│    - Instantiates AppNotification($payload)                                 │
│    - Calls Laravel's Notification::send()                                   │
└──────────────────────────────────────┬──────────────────────────────────────┘
                                       │
                                       ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│ 3. AppNotification (Queued via ShouldQueue)                                 │
├──────────────────────────────────────┬──────────────────────────────────────┤
│ Channel A: Database (In-App)         │ Channel B: Mail (HTML + Plain Text)  │
│ - Inserts into `notifications` table │ - Applies runtime SMTP configuration │
│ - Displays in header bell dropdown   │ - Resolves EmailTemplate from DB     │
│ - Powers /notifications index page   │ - Interpolates baseline & custom vars│
│ - Handles multi-company auto-switch  │ - Sends responsive multipart email   │
└──────────────────────────────────────┴──────────────────────────────────────┘
```

---

## 3. The `NotificationPayload` Value Object

Located at [`app/ValueObjects/NotificationPayload.php`](file:///c:/Users/Public/Downloads/projects/scaffolding-app-laravel/app/ValueObjects/NotificationPayload.php).

### Constructor Reference

| Parameter | Type | Required | Default | Description |
| :--- | :--- | :---: | :---: | :--- |
| `title` | `string` | **Yes** | — | Notification title and fallback email subject |
| `message` | `string` | **Yes** | — | Notification body content |
| `type` | `string` | No | `'system'` | Trigger key matching the `event` column in `email_templates` |
| `url` | `?string` | No | `null` | Target URL when clicked or CTA button link in email |
| `actionText` | `?string` | No | `null` | CTA button label (e.g. `'View Details'`, `'Review Request'`) |
| `companyId` | `?int` | No | `null` | Company ID for tenant-scoped email overrides and session auto-switching |
| `companyName` | `?string` | No | `null` | Company display name prepended to email subjects |
| `icon` | `?string` | No | `null` | Optional icon identifier for in-app UI |
| `level` | `string` | No | `'info'` | Severity: `'info'` (blue), `'success'` (green), `'warning'` (amber), `'danger'` (red) |
| `variables` | `array` | No | `[]` | Arbitrary custom tokens (e.g. `['po_number' => '123']`) for email interpolation |

---

## 4. Supported Recipient Formats

`NotificationEngine` automatically resolves and deduplicates recipients in any of the following formats:

```php
// 1. Single User Model
NotificationRequested::dispatch($user, $payload);

// 2. Collection of User Models
NotificationRequested::dispatch(User::where('department_id', 4)->get(), $payload);

// 3. User ID Integer
NotificationRequested::dispatch(42, $payload);

// 4. Mixed Array of Models and User IDs
NotificationRequested::dispatch([$user1, 42, $user2, 99], $payload);
```

---

## 5. The 3 Integration Patterns

Depending on your subsystem architecture, choose the pattern that best fits your design:

### Pattern A: Event-Driven Dispatching (Clean & Decoupled)
Directly fire `NotificationRequested` inside your controller or action class:

```php
namespace App\Http\Controllers;

use App\Events\NotificationRequested;
use App\ValueObjects\NotificationPayload;

class LeaveRequestController extends Controller
{
    public function submit(Request $request)
    {
        // ... save leave request ...

        NotificationRequested::dispatch(
            recipients: $manager,
            payload: new NotificationPayload(
                title: 'Leave Request Submitted',
                message: "{$requestor->name} requested 3 days of Annual Leave.",
                type: 'leave_request_submitted',
                url: route('leave.show', $leave->id),
                actionText: 'Review Leave Request',
                companyId: $leave->company_id,
                companyName: $leave->company->name,
                level: 'info',
                variables: [
                    'leave_type' => 'Annual Leave',
                    'start_date' => '2026-09-01',
                    'end_date'   => '2026-09-03',
                ]
            )
        );
    }
}
```

---

### Pattern B: Event Subscribers for Domain Events (Enterprise Subsystems)
If your module already emits domain events (e.g. `OrderPlaced`, `TicketEscalated`, `ApprovalCompleted`), create a subscriber in `app/Listeners/`:

```php
namespace App\Listeners;

use App\Events\TicketEscalated;
use App\Events\NotificationRequested;
use App\ValueObjects\NotificationPayload;
use Illuminate\Events\Dispatcher;

class TicketNotificationSubscriber
{
    public function handleTicketEscalated(TicketEscalated $event): void
    {
        $ticket = $event->ticket;
        $supervisors = $ticket->department->supervisors;

        NotificationRequested::dispatch(
            recipients: $supervisors,
            payload: new NotificationPayload(
                title: 'Ticket Priority Escalated',
                message: "Ticket #{$ticket->id} ({$ticket->subject}) has been escalated to Priority 1.",
                type: 'ticket_escalated',
                url: route('tickets.show', $ticket->id),
                actionText: 'Investigate Ticket',
                companyId: $ticket->company_id,
                companyName: $ticket->company->name,
                level: 'danger',
                variables: [
                    'ticket_id' => $ticket->id,
                    'priority'  => 'Priority 1 - Critical',
                ]
            )
        );
    }

    public function subscribe(Dispatcher $events): array
    {
        return [
            TicketEscalated::class => 'handleTicketEscalated',
        ];
    }
}
```
> **Note on Auto-Discovery:** Laravel automatically registers subscriber classes in `app/Listeners/`. Do **not** register them manually in `AppServiceProvider`.

---

### Pattern C: Direct Service Injection (Synchronous / CLI Scripts)
If you need direct programmatic dispatching:

```php
namespace App\Services;

use App\Services\NotificationEngine;
use App\ValueObjects\NotificationPayload;

class MaintenanceService
{
    public function __construct(
        private readonly NotificationEngine $notificationEngine
    ) {}

    public function alertAdmins(string $serverName): void
    {
        $admins = User::role('admin')->get();

        $this->notificationEngine->send($admins, new NotificationPayload(
            title: 'Server Alert',
            message: "Server {$serverName} load exceeds 95%.",
            type: 'server_alert',
            level: 'danger'
        ));
    }
}
```

---

## 6. Email Templating & Token Interpolation

### Cascading Resolution Hierarchy
When sending an email, `EmailTemplateService` searches for a matching template in this exact order:

```
┌────────────────────────────────────────────────────────┐
│ 1. Company Override Template                           │
│    (where company_id = {companyId} and event = {type}) │
└───────────────────────────┬────────────────────────────┘
                            │ (If not found)
                            ▼
┌────────────────────────────────────────────────────────┐
│ 2. Global System Default Template                      │
│    (where company_id is null and event = {type})       │
└───────────────────────────┬────────────────────────────┘
                            │ (If not found)
                            ▼
┌────────────────────────────────────────────────────────┐
│ 3. Safe Built-In Responsive HTML Fallback              │
│    (Standard clean HTML email rendering title/message) │
└────────────────────────────────────────────────────────┘
```

---

### Available Placeholder Tokens

All tokens use `{{ token_name }}` syntax inside Subject lines, HTML bodies, and Plain Text fallbacks.

#### Standard Baseline Tokens (Injected Automatically)
| Token | Description | Example Output |
| :--- | :--- | :--- |
| `{{ user_name }}` | Recipient's display name | `Alice Requestor` |
| `{{ user_email }}` | Recipient's email address | `alice@example.com` |
| `{{ company_name }}` | Active company context name | `Acme Global Corp` |
| `{{ title }}` | Notification title | `Purchase Request #102` |
| `{{ message }}` | Notification message text | `Your request requires review.` |
| `{{ action_url }}` | Redirect URL | `https://app.test/approvals/102` |
| `{{ approval_link }}` | Alias for action_url | `https://app.test/approvals/102` |
| `{{ action_text }}` | CTA button label | `View Details` |
| `{{ app_name }}` | Application name | `Scaffolding App` |
| `{{ current_year }}` | 4-digit calendar year | `2026` |

#### Custom Domain Tokens
Any key passed in `NotificationPayload::$variables` is immediately available as a token:
```php
// Payload:
variables: [
    'po_number' => 'PO-9941',
    'total_cost' => '$12,500.00',
    'vendor' => 'Dell Technologies',
]

// In Template:
// <p>Purchase order {{ po_number }} for {{ vendor }} totaling {{ total_cost }} was approved.</p>
```

---

### Standard System Events Seeded by Default
| Event Trigger Key | Display Name | Trigger Scenario |
| :--- | :--- | :--- |
| **`approval_requested`** | Approval Requested Notification | Approver assigned to pending workflow step |
| **`step_approved`** | Approval Step Completed Notification | Intermediate step approved notification to requestor |
| **`approval_completed`** | Approval Fully Completed Notification | Final workflow completion notification to requestor |
| **`approval_rejected`** | Approval Rejected Notification | Workflow rejection notification to requestor |
| **`approval_canceled`** | Approval Canceled Notification | Request cancellation notification to approvers |
| **`approval_delegated`** | Approval Delegated Notification | Step delegation alert to newly assigned approver |
| **`user_welcome`** | User Welcome Notification | Account registration notification to newly created user |

---

## 7. Multi-Tenant Scoping & Auto-Switching

The notification system natively supports multi-company environments:

1. When generating a payload, pass `companyId: $model->company_id` and `companyName: $model->company->name`.
2. When the user clicks the in-app notification, `POST /notifications/{id}/read` runs.
3. If the user is a member of that company, the controller automatically updates `session(['active_company_id' => $companyId])` and redirects the user directly to the target URL inside the proper company context.

---

## 8. Direct Template Rendering (PDFs & Custom Mails)

If your module needs to render raw HTML for PDF exports or custom mail messages without creating a notification record:

```php
use App\Services\EmailTemplateService;

$templateService = app(EmailTemplateService::class);

$rendered = $templateService->render(
    event: 'invoice_ready',
    variables: [
        'user_name' => 'Alice Smith',
        'company_name' => 'Acme Corp',
        'invoice_number' => 'INV-2026-001',
        'total_amount' => '$1,250.00',
    ],
    companyId: $company->id
);

// $rendered['subject'] -> '[Acme Corp] Invoice #INV-2026-001 Ready'
// $rendered['html']    -> '<div style="font-family: Arial...">...</div>'
// $rendered['text']    -> "Plain text version...\n"
```

---

## 9. Complete Real-World Example: Invoice Module

Here is an end-to-end guide on creating a brand new module with email notifications:

### Step 1: Create Domain Event
```php
// app/Events/InvoiceCreated.php
namespace App\Events;

use App\Models\Invoice;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvoiceCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Invoice $invoice) {}
}
```

### Step 2: Create Notification Subscriber
```php
// app/Listeners/InvoiceNotificationSubscriber.php
namespace App\Listeners;

use App\Events\InvoiceCreated;
use App\Events\NotificationRequested;
use App\ValueObjects\NotificationPayload;
use Illuminate\Events\Dispatcher;

class InvoiceNotificationSubscriber
{
    public function handleInvoiceCreated(InvoiceCreated $event): void
    {
        $invoice = $event->invoice;

        NotificationRequested::dispatch(
            recipients: $invoice->customerUser,
            payload: new NotificationPayload(
                title: 'New Invoice Issued',
                message: "Invoice #{$invoice->number} for \${$invoice->amount} has been issued.",
                type: 'invoice_created', // ◄── Trigger Key
                url: route('invoices.show', $invoice->id),
                actionText: 'Pay Invoice',
                companyId: $invoice->company_id,
                companyName: $invoice->company->name,
                level: 'info',
                variables: [
                    'invoice_number' => $invoice->number,
                    'amount_due'     => '$' . number_format($invoice->amount, 2),
                    'due_date'       => $invoice->due_date->format('M d, Y'),
                ]
            )
        );
    }

    public function subscribe(Dispatcher $events): array
    {
        return [
            InvoiceCreated::class => 'handleInvoiceCreated',
        ];
    }
}
```

### Step 3: Design Template in Admin UI
1. Go to **System Settings ➔ Email Templates**.
2. Click **`+ Create Email Template`**.
3. Set **Event Trigger Key** to `invoice_created`.
4. Set **Subject Pattern** to `[{{ company_name }}] Invoice #{{ invoice_number }} Ready`.
5. Enter HTML Body in Quill:
   ```html
   <p>Hello <strong>{{ user_name }}</strong>,</p>
   <p>A new invoice #<strong>{{ invoice_number }}</strong> has been issued for <strong>{{ amount_due }}</strong>.</p>
   <p>Payment is due on <strong>{{ due_date }}</strong>.</p>
   <p><a href="{{ action_url }}" style="background:#4f46e5;color:#fff;padding:10px 20px;border-radius:6px;text-decoration:none;">{{ action_text }}</a></p>
   ```
6. Click **Save Template**.

---

## 10. Testing Your Integration with Pest

Always write feature tests verifying that domain events trigger notifications and render HTML emails properly:

```php
use App\Events\InvoiceCreated;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Support\Facades\Notification;

test('invoice creation triggers notification and renders custom email', function () {
    Notification::fake();

    $company = Company::factory()->create(['name' => 'Acme Corp']);
    $customer = User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
    
    $invoice = Invoice::factory()->create([
        'company_id' => $company->id,
        'customer_id' => $customer->id,
        'number' => 'INV-888',
        'amount' => 500.00,
    ]);

    InvoiceCreated::dispatch($invoice);

    Notification::assertSentTo(
        $customer,
        AppNotification::class,
        function (AppNotification $notification) use ($customer) {
            expect($notification->payload->type)->toBe('invoice_created')
                ->and($notification->payload->variables['invoice_number'])->toBe('INV-888');

            $mail = $notification->toMail($customer);
            expect($mail->subject)->toContain('INV-888')
                ->and((string) $mail->render())->toContain('Hello <strong>John Doe</strong>');

            return true;
        }
    );
});
```

---

## 11. Troubleshooting & Best Practices

1. **Queue Connection in Local Development:**
   - `AppNotification` implements `ShouldQueue`. In local testing, set `QUEUE_CONNECTION=sync` in `.env` to execute notifications synchronously without running `php artisan queue:work`.
2. **Missing Token Placeholders in Emails:**
   - Verify that your token name in the template matches the exact key in `NotificationPayload::$variables` (e.g. `{{ invoice_number }}` matching `'invoice_number' => '...'`).
3. **Company Overrides Not Rendering:**
   - Ensure you pass `companyId: $model->company_id` when constructing `NotificationPayload`. If `companyId` is `null`, the engine will always resolve the global default template.
4. **HTML Quoting in Blade Views:**
   - When editing templates in Blade files, always write placeholders as `@{{ token_name }}` so Blade does not treat them as PHP syntax.
