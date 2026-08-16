# Developer Guide: Notification & Email Module Integration

This guide provides step-by-step instructions for developers on how to integrate any application domain module (e.g. Purchase Requests, Invoices, Customer Orders, Leave Requests, Tickets) with the centralized **Notification & Email Template Engine**.

---

## 1. Architectural Overview

The application utilizes an **event-driven, multi-tenant notification pipeline**:

```
[ Domain Module / Observer / Controller / Event ]
                      │
                      ▼
       NotificationRequested::dispatch($recipients, $payload)
                      │
                      ▼
            SendNotificationListener
                      │
                      ▼
            NotificationEngine::send()
                      │
                      ▼
               AppNotification (Queued)
           ┌──────────┴──────────┐
           ▼                     ▼
    Database Channel        Mail Channel (HTML + Text)
  (Header Bell & Inbox)   (EmailTemplateService + SMTP)
```

---

## 2. Dispatching Notifications & Emails

### Quick Start Example

To send an in-app alert and customized branded email:

```php
use App\Events\NotificationRequested;
use App\ValueObjects\NotificationPayload;

NotificationRequested::dispatch(
    recipients: $recipientUser, // Single User, Collection of Users, User ID, or Array
    payload: new NotificationPayload(
        title: 'Invoice #INV-2026-001 Ready',
        message: 'Your monthly statement is ready for review.',
        type: 'invoice_ready', // ◄── Trigger Key in Email Templates
        url: route('invoices.show', $invoice->id),
        actionText: 'View Invoice',
        companyId: $invoice->company_id, // ◄── Multi-Company Scoping
        companyName: $invoice->company->name,
        icon: 'document-text',
        level: 'info', // 'info', 'success', 'warning', 'danger'
        variables: [ // ◄── Custom Tokens for Email Template Interpolation
            'invoice_number' => 'INV-2026-001',
            'total_amount' => '$1,450.00',
            'due_date' => '2026-09-15',
        ]
    )
);
```

---

## 3. `NotificationPayload` Parameters Reference

| Parameter | Type | Required | Default | Description |
| :--- | :--- | :---: | :---: | :--- |
| **`title`** | `string` | **Yes** | — | Notification heading and email subject fallback |
| **`message`** | `string` | **Yes** | — | Notification body content |
| **`type`** | `string` | No | `'system'` | Trigger key that matches the `event` column in `email_templates` |
| **`url`** | `?string` | No | `null` | Target URL when user clicks notification or CTA button |
| **`actionText`** | `?string` | No | `null` | Call-to-action button label (e.g. `'View Details'`, `'Review Request'`) |
| **`companyId`** | `?int` | No | `null` | Company ID for tenant-scoped template overrides and auto-switching |
| **`companyName`** | `?string` | No | `null` | Company display name |
| **`icon`** | `?string` | No | `null` | Optional icon identifier for in-app UI |
| **`level`** | `string` | No | `'info'` | Severity: `'info'` (blue), `'success'` (green), `'warning'` (amber), `'danger'` (red) |
| **`variables`** | `array` | No | `[]` | Arbitrary custom tokens (e.g. `['order_id' => '123', 'amount' => '$50']`) |

---

## 4. Recipient Formats Supported

`NotificationEngine` accepts polymorphic recipient inputs and automatically normalizes them into unique `User` models:

```php
// 1. Single User Model
NotificationRequested::dispatch($user, $payload);

// 2. Collection of User Models
NotificationRequested::dispatch($usersCollection, $payload);

// 3. User ID Integer
NotificationRequested::dispatch(42, $payload);

// 4. Mixed Array of IDs and Models
NotificationRequested::dispatch([$user1, 42, $user2, 99], $payload);
```

---

## 5. Standard & Custom Placeholder Tokens

When an email is rendered, `EmailTemplateService` automatically substitutes the following tokens inside the template's Subject, HTML Body, and Plain Text Body:

### Standard Tokens (Provided Automatically):
- `{{ user_name }}` — Name of the recipient user (e.g. `"John Doe"`).
- `{{ user_email }}` — Email address of the recipient.
- `{{ company_name }}` — Name of the company context.
- `{{ title }}` / `{{ request_title }}` — Notification title string.
- `{{ message }}` — Notification message text.
- `{{ action_url }}` / `{{ approval_link }}` — Target redirect URL.
- `{{ action_text }}` — CTA button text (e.g. `"View Invoice"`).
- `{{ app_name }}` — Global application name (e.g. `"Scaffolding App"`).
- `{{ current_year }}` — Current 4-digit calendar year (e.g. `"2026"`).

### Custom Domain Tokens:
Any key passed in `NotificationPayload::$variables` is immediately available as a token:
```php
// Payload:
variables: ['tracking_number' => 'TRK-98765', 'courier' => 'FedEx']

// In Quill Email Editor:
// Your package was shipped with {{ courier }}. Tracking: {{ tracking_number }}
```

---

## 6. How Email Template Resolution Works

When `AppNotification::toMail()` executes, it resolves the template using a cascading hierarchy:

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

---

## 7. Creating Email Templates via the Admin Panel

Administrators can customize emails for any module without editing code:

1. Log in to the application and navigate to **System Settings ➔ Email Templates**.
2. Click **`+ Create Email Template`**.
3. Set **Event Trigger Key** to match your module's `type` parameter (e.g. `invoice_ready`).
4. Enter the **Display Name** and **Subject Pattern** (e.g. `[{{ company_name }}] Invoice #{{ invoice_number }} Ready`).
5. Choose **Global Default** or assign to a specific **Company Override**.
6. Design the email body using the **Quill Snow WYSIWYG editor** or click **`</>`** in the toolbar for raw HTML code editing.
7. Click the placeholder token buttons to insert variables at the cursor position.
8. Click **Live Draft Preview** to inspect dummy rendering before saving.
9. Click **Save Template**.

---

## 8. Direct Rendering (PDF Receipts / Custom Mails)

If your module needs to render raw HTML for PDF export or custom mailers:

```php
use App\Services\EmailTemplateService;

$templateService = app(EmailTemplateService::class);

$rendered = $templateService->render(
    event: 'invoice_ready',
    variables: [
        'user_name' => 'Alice',
        'invoice_number' => 'INV-100',
        'total_amount' => '$500.00',
    ],
    companyId: $company->id
);

// $rendered['subject'] => '[Acme] Invoice #INV-100 Ready'
// $rendered['html']    => '<div ...>...</div>'
// $rendered['text']    => 'Plain text version...'
```

---

## 9. Automated Testing (Pest PHP)

When writing feature tests for your module:

```php
use App\Events\NotificationRequested;
use App\Notifications\AppNotification;
use App\ValueObjects\NotificationPayload;
use Illuminate\Support\Facades\Notification;

test('order placement dispatches order_created notification and email', function () {
    Notification::fake();

    $user = User::factory()->create(['name' => 'Bob Customer', 'email' => 'bob@example.com']);
    
    $this->actingAs($user)->post(route('orders.store'), [
        'item' => 'Keyboard',
        'amount' => 120,
    ]);

    Notification::assertSentTo(
        $user,
        AppNotification::class,
        function (AppNotification $notification) use ($user) {
            expect($notification->payload->type)->toBe('order_created');
            
            $mail = $notification->toMail($user);
            expect($mail->subject)->toContain('Order Created')
                ->and((string) $mail->render())->toContain('Hello Bob Customer');

            return true;
        }
    );
});
```
