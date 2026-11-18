---
name: notification-integration
description: "Apply this skill when building or integrating a module that needs to send notifications (in-app database notifications, emails, multi-company auto-switch alerts). Triggers: notification, notify, NotificationEngine, NotificationPayload, NotificationRequested, send notification, in-app alert, database notification, email template, EmailTemplateService. Covers: constructing NotificationPayload, passing arbitrary variables, dispatching NotificationRequested events, EmailTemplateService cascading resolution, Quill email authoring, direct template rendering, multi-company context auto-switching, header notification bell integration, and Pest test coverage."
license: MIT
metadata:
  author: eldinbiz
---
# Notification & Email Module Integration

This skill documents how any module in the application integrates with the centralized **Notification & Email Template Engine**, dispatching in-app database notifications and branded HTML emails to users across single or multi-company contexts.

---

## Architecture Overview

The notification and email infrastructure is designed as an **event-driven + engine-based multi-tenant pipeline**:

```
[ Domain Module / Observer / Controller / Domain Event ]
                      │
                      ▼
       NotificationRequested::dispatch($recipients, $payload)
                      │
                      ▼
            SendNotificationListener ──► NotificationEngine::send()
                                              │
                                              ▼
                                 AppNotification (Queued)
                       ┌──────────────────────┴──────────────────────┐
                       ▼                                             ▼
                Database Channel                                Mail Channel
            (Header Bell & In-App Inbox)              (EmailTemplateService + SMTP)
```

### Key Components Reference

| Component | Class | Location | Purpose |
|---|---|---|---|
| **Value Object** | `NotificationPayload` | `app/ValueObjects/NotificationPayload.php` | Readonly DTO defining title, message, type, URL, company context, severity, and custom variables |
| **Event** | `NotificationRequested` | `app/Events/NotificationRequested.php` | Universal event dispatched to trigger notification sending |
| **Listener** | `SendNotificationListener` | `app/Listeners/SendNotificationListener.php` | Auto-discovered listener delegating `NotificationRequested` to `NotificationEngine` |
| **Service Engine** | `NotificationEngine` | `app/Services/NotificationEngine.php` | Resolves polymorphic recipients and triggers Laravel's `Notification::send()` |
| **Notification** | `AppNotification` | `app/Notifications/AppNotification.php` | `ShouldQueue` notification handling `database` and `mail` channels with template resolution |
| **Template Engine** | `EmailTemplateService` | `app/Services/EmailTemplateService.php` | Resolves company/global templates, interpolates tokens, and renders Quill HTML/Text |
| **Controller** | `NotificationController` | `app/Http/Controllers/NotificationController.php` | Endpoints for listing, unread count, mark as read, and company auto-switching |

---

## Step 1: Constructing `NotificationPayload`

All notifications require a `NotificationPayload` instance:

```php
use App\ValueObjects\NotificationPayload;

$payload = new NotificationPayload(
    title: 'Purchase Request Approved',
    message: 'Purchase Request #45 for IT Laptops has been fully approved.',
    type: 'purchase_request_approved', // ◄── Matches 'event' key in Email Templates
    url: route('purchase-requests.show', $pr->id), // Target redirect URL when clicked
    actionText: 'View Details', // Action button text for emails
    companyId: $company->id, // Multi-company context ID (enables company overrides)
    companyName: $company->name, // Company display name
    icon: 'shopping-cart', // Optional icon identifier
    level: 'success', // Severities: 'info', 'success', 'warning', 'danger'
    variables: [ // ◄── Custom tokens for dynamic email template interpolation
        'po_number' => $pr->po_number,
        'total_amount' => '$' . number_format($pr->amount, 2),
        'department' => $pr->department->name,
    ]
);
```

### Constructor Parameters Reference

| Parameter | Type | Required | Default | Description |
|---|---|---|---|---|
| `title` | `string` | **Yes** | — | Main notification title heading |
| `message` | `string` | **Yes** | — | Detailed body text of the notification |
| `type` | `string` | No | `'system'` | Category/Event identifier (matches `event` column in `email_templates`) |
| `url` | `?string` | No | `null` | Target URL when user clicks notification or email CTA button |
| `actionText` | `?string` | No | `null` | Email CTA button label (e.g. `'View Details'`, `'Review Request'`) |
| `companyId` | `?int` | No | `null` | Company ID for multi-company auto-switching and tenant-scoped email overrides |
| `companyName` | `?string` | No | `null` | Company name prepended to email subjects: `[Company Name] Title` |
| `icon` | `?string` | No | `null` | Optional icon identifier for in-app UI |
| `level` | `string` | No | `'info'` | Severity level: `'info'` (blue), `'success'` (green), `'warning'` (amber), `'danger'` (red) |
| `variables` | `array` | No | `[]` | Arbitrary custom tokens (e.g. `['po_number' => 'PO-123']`) for dynamic email interpolation |

---

## Step 2: Recipient Resolution

`NotificationEngine` accepts polymorphic recipient formats and automatically normalizes them into a collection of unique `User` models:

```php
// 1. Single User Model
$engine->send($user, $payload);

// 2. Collection / Enumerable of User Models
$engine->send($usersCollection, $payload);

// 3. Single User ID (Integer)
$engine->send(42, $payload);

// 4. Array of User Models, User IDs, or Mixed
$engine->send([$user1, 42, $user2, 99], $payload);
```

---

## Step 3: Dispatching Notifications

### Pattern A: Event-Driven Dispatching (Recommended for Decoupled Modules)

Dispatch `NotificationRequested` directly from controllers, jobs, or domain services:

```php
use App\Events\NotificationRequested;
use App\ValueObjects\NotificationPayload;

NotificationRequested::dispatch(
    recipients: $requestor,
    payload: new NotificationPayload(
        title: 'Document Approved',
        message: "Your document \"{$document->title}\" has been approved.",
        type: 'document_approved',
        url: route('documents.show', $document->id),
        actionText: 'View Document',
        companyId: $document->company_id,
        companyName: $document->company->name,
        level: 'success',
        variables: [
            'document_number' => $document->doc_number,
        ]
    )
);
```

### Pattern B: Event Subscriber for Domain Events (Recommended for Subsystem Integration)

If your module dispatches domain events (e.g., `OrderPlaced`, `InvoicePaid`, `LeaveRequested`), create a dedicated **Event Subscriber** in `app/Listeners/`:

```php
namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Events\NotificationRequested;
use App\ValueObjects\NotificationPayload;
use Illuminate\Events\Dispatcher;

class OrderNotificationSubscriber
{
    public function handleOrderPlaced(OrderPlaced $event): void
    {
        $order = $event->order;
        $manager = $order->departmentManager;

        if ($manager) {
            NotificationRequested::dispatch(
                recipients: $manager,
                payload: new NotificationPayload(
                    title: 'New Order Requiring Review',
                    message: "Order #{$order->number} from {$order->customer_name} has been placed.",
                    type: 'order_placed',
                    url: route('orders.show', $order->id),
                    actionText: 'Review Order',
                    companyId: $order->company_id,
                    companyName: $order->company->name,
                    level: 'info',
                    variables: [
                        'order_number' => $order->number,
                        'total_price' => '$' . number_format($order->total, 2),
                    ]
                )
            );
        }
    }

    public function subscribe(Dispatcher $events): array
    {
        return [
            OrderPlaced::class => 'handleOrderPlaced',
        ];
    }
}
```

> **Note on Laravel Auto-Discovery**: Event subscriber classes placed in `app/Listeners/` with type-hinted event handler methods are automatically discovered by Laravel. **Do NOT manually register subscribers in `AppServiceProvider`**.

### Pattern C: Direct `NotificationEngine` Usage (Synchronous / Internal Services)

Inject `NotificationEngine` into your service classes:

```php
namespace App\Services;

use App\Services\NotificationEngine;
use App\ValueObjects\NotificationPayload;

class OrderService
{
    public function __construct(
        private readonly NotificationEngine $notificationEngine
    ) {}

    public function notifyFulfillment(Order $order): void
    {
        $payload = new NotificationPayload(
            title: 'Order Fulfilled',
            message: "Order #{$order->number} has shipped.",
            type: 'order_fulfilled',
            url: route('orders.show', $order->id),
            level: 'success'
        );

        $this->notificationEngine->send($order->customer_user_id, $payload);
    }
}
```

---

## Step 4: Email Templating & Dynamic Token Interpolation

When `AppNotification::toMail()` executes, it resolves email templates using a **3-tier cascading hierarchy**:

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
│    (Standard HTML email rendering title & message)     │
└────────────────────────────────────────────────────────┘
```

### Supported Variable Tokens

`EmailTemplateService` automatically substitutes tokens in the template's Subject, HTML Body, and Plain Text Fallback:

#### Standard Tokens (Always Available):
- `{{ user_name }}` — Name of recipient user (e.g. `"Jane Doe"`)
- `{{ user_email }}` — Email address of recipient
- `{{ company_name }}` — Context company display name
- `{{ title }}` / `{{ request_title }}` — Notification title
- `{{ message }}` — Notification message text
- `{{ action_url }}` / `{{ approval_link }}` — Target redirect URL
- `{{ action_text }}` — CTA button text (e.g. `"View Details"`)
- `{{ app_name }}` — Application name (from config or settings)
- `{{ current_year }}` — 4-digit year (e.g. `"2026"`)

#### Custom Domain Tokens:
Any key passed in `NotificationPayload::$variables` is immediately substituted:
```php
// In Payload:
variables: ['invoice_number' => 'INV-2026-089', 'total_amount' => '$1,450.00', 'due_date' => '2026-09-15']

// In Quill Email Editor:
// <p>Hello {{ user_name }}, your invoice #{{ invoice_number }} for {{ total_amount }} is due on {{ due_date }}.</p>
```

---

## Step 5: System Seeded Events Inventory

The system comes pre-seeded with global templates in `database/seeders/SystemSettingSeeder.php`:

| Event Trigger Key | Template Name | Target Audience |
|---|---|---|
| **`approval_requested`** | Approval Requested Notification | Approver assigned to review step |
| **`step_approved`** | Approval Step Completed Notification | Requestor (intermediate step approved) |
| **`approval_completed`** | Approval Fully Completed Notification | Requestor (final approval complete) |
| **`approval_rejected`** | Approval Rejected Notification | Requestor (request rejected) |
| **`approval_canceled`** | Approval Canceled Notification | Pending approvers (request canceled) |
| **`approval_delegated`** | Approval Delegated Notification | Newly assigned delegate approver |
| **`user_welcome`** | User Welcome Notification | Newly created user on account registration |

---

## Step 6: Direct Service Rendering (PDFs / Custom Mails)

If a module needs to render raw HTML for PDF invoices or custom mail delivery without creating a notification:

```php
use App\Services\EmailTemplateService;

$templateService = app(EmailTemplateService::class);

$rendered = $templateService->render(
    event: 'invoice_ready',
    variables: [
        'user_name' => 'Alice',
        'company_name' => 'Acme Corp',
        'invoice_number' => 'INV-100',
        'total_amount' => '$500.00',
    ],
    companyId: $company->id
);

// $rendered['subject'] => '[Acme Corp] Invoice #INV-100 Ready'
// $rendered['html']    => '<div ...>...</div>'
// $rendered['text']    => 'Plain text version...'
```

---

## Step 7: Multi-Company Context & Auto-Switching

When a user clicks an in-app notification:
1. `POST /notifications/{id}/read` is invoked.
2. If `company_id` is present, `NotificationController` validates membership and updates `session(['active_company_id' => $companyId])`.
3. The user is redirected to the notification's target `url` already switched to the correct tenant context.

---

## Step 8: Testing Notification & Mail Integration (Pest PHP)

When writing feature tests for your module:

```php
use App\Events\NotificationRequested;
use App\Notifications\AppNotification;
use App\ValueObjects\NotificationPayload;
use Illuminate\Support\Facades\Notification;

test('it dispatches notification and renders customized HTML email', function () {
    Notification::fake();

    $user = User::factory()->create(['name' => 'Eva Customer', 'email' => 'eva@example.com']);
    $company = Company::factory()->create(['name' => 'Acme Global Corp']);
    
    $payload = new NotificationPayload(
        title: 'Order Placed',
        message: 'Your order has been received.',
        type: 'order_placed',
        url: 'https://scaffolding.test/orders/101',
        actionText: 'View Order',
        companyId: $company->id,
        companyName: $company->name,
        variables: [
            'order_number' => 'ORD-101',
            'total_amount' => '$199.00',
        ]
    );

    NotificationRequested::dispatch($user, $payload);

    Notification::assertSentTo(
        $user,
        AppNotification::class,
        function (AppNotification $notification) use ($user) {
            expect($notification->payload->type)->toBe('order_placed')
                ->and($notification->payload->variables['order_number'])->toBe('ORD-101');

            $mail = $notification->toMail($user);
            expect($mail->subject)->toContain('Order Placed')
                ->and((string) $mail->render())->toContain('Acme Global Corp');

            return true;
        }
    );
});
```

---

## Real-Time Notification Bell Component (Livewire v4)

In the global navigation bar, real-time unread count and popover notifications are rendered via the **Livewire v4 NotificationBell Component** (`App\Livewire\Navigation\NotificationBell`):

- **Auto-Polling:** Employs `wire:poll.30s` to check for new database notifications in the background.
- **Mark As Read & Tenant Auto-Switch:** Clicking an unread notification triggers `$wire.markAsRead(notificationId)`, which marks the notification read in the database and automatically switches the active tenant company session if the notification belongs to another company context.
- **Mark All As Read:** Triggers `$wire.markAllAsRead()`.

---

## Complete Integration Checklist

- [ ] Import `App\Events\NotificationRequested` and `App\ValueObjects\NotificationPayload`
- [ ] Set `type` parameter matching the email template event trigger key (e.g. `'invoice_ready'`)
- [ ] Pass `companyId` and `companyName` for tenant-scoped template overrides and session auto-switching
- [ ] Provide optional domain-specific variable tokens in `variables: [...]`
- [ ] Place any Event Subscribers in `app/Listeners/` (auto-discovered by Laravel)
- [ ] Write Pest feature tests with `Notification::fake()` asserting payload parameters and `toMail()` rendering
- [ ] Include `<livewire:navigation.notification-bell />` in header navigation layouts
