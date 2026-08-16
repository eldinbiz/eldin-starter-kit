<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ApprovalStatus;
use App\Enums\StepState;
use App\Events\Approval\ApprovalCanceled;
use App\Events\Approval\ApprovalCompleted;
use App\Events\Approval\ApprovalDelegated;
use App\Events\Approval\ApprovalRejected;
use App\Events\Approval\ApprovalRequested;
use App\Events\Approval\ApprovalStepCompleted;
use App\Models\Approval\ApprovalRequest;
use App\Models\Approval\ApprovalStep;
use App\Models\Approval\WorkflowTemplate;
use App\Models\Company;
use App\Models\EmailTemplate;
use App\Models\Role;
use App\Models\User;
use App\Notifications\AppNotification;
use App\ValueObjects\NotificationPayload;
use Database\Seeders\SystemSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(SystemSettingSeeder::class);

    $this->company = Company::factory()->create(['name' => 'Acme Global Corp']);
    $this->requestor = User::factory()->create(['name' => 'Alice Requestor', 'email' => 'alice@example.com']);
    $this->approver1 = User::factory()->create(['name' => 'Bob Manager', 'email' => 'bob@example.com']);
    $this->approver2 = User::factory()->create(['name' => 'Carol Director', 'email' => 'carol@example.com']);

    $this->requestor->companies()->attach($this->company->id, ['status' => 'active']);
    $this->approver1->companies()->attach($this->company->id, ['status' => 'active']);
    $this->approver2->companies()->attach($this->company->id, ['status' => 'active']);

    $this->workflowTemplate = WorkflowTemplate::factory()->create(['company_id' => $this->company->id]);
});

test('ApprovalRequested event sends email with customized approval_requested template', function (): void {
    Notification::fake();

    $approvalRequest = ApprovalRequest::create([
        'company_id' => $this->company->id,
        'requestor_id' => $this->requestor->id,
        'approvable_type' => 'App\Models\PurchaseRequest',
        'approvable_id' => 101,
        'workflow_template_id' => $this->workflowTemplate->id,
        'workflow_snapshot' => ['title' => 'MacBook Pro Requisition'],
        'status' => ApprovalStatus::Pending,
    ]);

    $step = $approvalRequest->steps()->create([
        'group_name' => 'Manager Review',
        'step_label' => 'Direct Manager Approval',
        'order' => 1,
        'resolver_type' => 'specific_user',
        'approver_id' => $this->approver1->id,
        'state' => StepState::Pending,
    ]);

    ApprovalRequested::dispatch($approvalRequest);

    Notification::assertSentTo(
        $this->approver1,
        AppNotification::class,
        function (AppNotification $notification): bool {
            expect($notification->payload->type)->toBe('approval_requested');

            $mail = $notification->toMail($this->approver1);
            expect($mail->subject)->toContain('Action Required')
                ->and((string) $mail->render())->toContain('Hello <strong>Bob Manager</strong>')
                ->and((string) $mail->render())->toContain('Acme Global Corp');

            return true;
        }
    );
});

test('ApprovalCompleted event sends email with customized approval_completed template', function (): void {
    Notification::fake();

    $approvalRequest = ApprovalRequest::create([
        'company_id' => $this->company->id,
        'requestor_id' => $this->requestor->id,
        'approvable_type' => 'App\Models\PurchaseRequest',
        'approvable_id' => 102,
        'workflow_template_id' => $this->workflowTemplate->id,
        'workflow_snapshot' => ['title' => 'Cloud Server Expansion'],
        'status' => ApprovalStatus::Approved,
    ]);

    ApprovalCompleted::dispatch($approvalRequest);

    Notification::assertSentTo(
        $this->requestor,
        AppNotification::class,
        function (AppNotification $notification): bool {
            expect($notification->payload->type)->toBe('approval_completed');

            $mail = $notification->toMail($this->requestor);
            expect($mail->subject)->toContain('Request Fully Approved')
                ->and((string) $mail->render())->toContain('Hello <strong>Alice Requestor</strong>')
                ->and((string) $mail->render())->toContain('Status: Fully Approved');

            return true;
        }
    );
});

test('ApprovalRejected event sends email with customized approval_rejected template', function (): void {
    Notification::fake();

    $approvalRequest = ApprovalRequest::create([
        'company_id' => $this->company->id,
        'requestor_id' => $this->requestor->id,
        'approvable_type' => 'App\Models\PurchaseRequest',
        'approvable_id' => 103,
        'workflow_template_id' => $this->workflowTemplate->id,
        'workflow_snapshot' => ['title' => 'Luxury Office Chair Requisition'],
        'status' => ApprovalStatus::Rejected,
    ]);

    $step = $approvalRequest->steps()->create([
        'group_name' => 'Finance Review',
        'step_label' => 'Finance Approval',
        'order' => 1,
        'resolver_type' => 'specific_user',
        'approver_id' => $this->approver1->id,
        'state' => StepState::Rejected,
    ]);

    ApprovalRejected::dispatch($step, $this->approver1, 'Exceeds budget limit');

    Notification::assertSentTo(
        $this->requestor,
        AppNotification::class,
        function (AppNotification $notification): bool {
            expect($notification->payload->type)->toBe('approval_rejected');

            $mail = $notification->toMail($this->requestor);
            expect($mail->subject)->toContain('Request Rejected')
                ->and((string) $mail->render())->toContain('Hello <strong>Alice Requestor</strong>')
                ->and((string) $mail->render())->toContain('Status: Rejected');

            return true;
        }
    );
});

test('ApprovalCanceled and ApprovalDelegated events send emails with respective seeded templates', function (): void {
    Notification::fake();

    $approvalRequest = ApprovalRequest::create([
        'company_id' => $this->company->id,
        'requestor_id' => $this->requestor->id,
        'approvable_type' => 'App\Models\PurchaseRequest',
        'approvable_id' => 104,
        'workflow_template_id' => $this->workflowTemplate->id,
        'workflow_snapshot' => ['title' => 'Software Licenses'],
        'status' => ApprovalStatus::Pending,
    ]);

    $step = $approvalRequest->steps()->create([
        'group_name' => 'Manager Review',
        'step_label' => 'Direct Manager Approval',
        'order' => 1,
        'resolver_type' => 'specific_user',
        'approver_id' => $this->approver1->id,
        'state' => StepState::Pending,
    ]);

    // 1. Delegated event
    ApprovalDelegated::dispatch($step, $this->approver1, $this->approver2);

    Notification::assertSentTo(
        $this->approver2,
        AppNotification::class,
        function (AppNotification $notification): bool {
            expect($notification->payload->type)->toBe('approval_delegated');

            $mail = $notification->toMail($this->approver2);
            expect($mail->subject)->toContain('Approval Delegated for')
                ->and((string) $mail->render())->toContain('Hello <strong>Carol Director</strong>')
                ->and((string) $mail->render())->toContain('Direct Manager Approval');

            return true;
        }
    );

    // 2. Canceled event
    ApprovalCanceled::dispatch($approvalRequest, $this->requestor);

    Notification::assertSentTo(
        $this->approver1,
        AppNotification::class,
        function (AppNotification $notification): bool {
            expect($notification->payload->type)->toBe('approval_canceled');

            $mail = $notification->toMail($this->approver1);
            expect($mail->subject)->toContain('Request Canceled')
                ->and((string) $mail->render())->toContain('Hello <strong>Bob Manager</strong>')
                ->and((string) $mail->render())->toContain('Status: Canceled');

            return true;
        }
    );
});

test('company email template override is prioritized at runtime over global template', function (): void {
    Notification::fake();

    // Create Company-specific override for approval_requested
    EmailTemplate::create([
        'company_id' => $this->company->id,
        'event' => 'approval_requested',
        'name' => 'Acme Custom Approval Request',
        'subject' => '[VIP Acme] Action Needed for {{ title }}',
        'body_html' => '<div class="vip-custom">Special Acme VIP Notification for {{ user_name }}</div>',
        'is_active' => true,
    ]);

    $approvalRequest = ApprovalRequest::create([
        'company_id' => $this->company->id,
        'requestor_id' => $this->requestor->id,
        'approvable_type' => 'App\Models\PurchaseRequest',
        'approvable_id' => 105,
        'workflow_template_id' => $this->workflowTemplate->id,
        'workflow_snapshot' => ['title' => 'Executive Office Requisition'],
        'status' => ApprovalStatus::Pending,
    ]);

    $approvalRequest->steps()->create([
        'group_name' => 'Executive Review',
        'step_label' => 'Executive Approval',
        'order' => 1,
        'resolver_type' => 'specific_user',
        'approver_id' => $this->approver1->id,
        'state' => StepState::Pending,
    ]);

    ApprovalRequested::dispatch($approvalRequest);

    Notification::assertSentTo(
        $this->approver1,
        AppNotification::class,
        function (AppNotification $notification): bool {
            $mail = $notification->toMail($this->approver1);

            expect($mail->subject)->toBe('[VIP Acme] Action Needed for Approval Requested')
                ->and((string) $mail->render())->toContain('Special Acme VIP Notification for Bob Manager');

            return true;
        }
    );
});

test('admin creating user dispatches user_welcome notification with welcome template', function (): void {
    Notification::fake();

    $adminUser = User::factory()->create(['name' => 'Super Admin', 'email' => 'superadmin@example.com']);
    $adminRole = Role::create([
        'name' => 'admin',
        'permissions' => ['manage-users', 'manage-company-settings'],
    ]);
    $adminUser->companies()->attach($this->company->id, ['status' => 'active']);
    $adminUser->companyRoles()->attach($adminRole->id, ['company_id' => $this->company->id]);
    session(['active_company_id' => $this->company->id]);

    $userRole = Role::firstOrCreate(['name' => 'user'], ['permissions' => []]);

    $response = $this->actingAs($adminUser)->post(route('admin.users.store'), [
        'name' => 'Daniel Newbie',
        'email' => 'daniel@example.com',
        'password' => 'SecurePass123!',
        'auth_method' => 'local',
        'job_title' => 'Software Engineer',
        'roles' => [$userRole->id],
    ]);

    $response->assertRedirect(route('admin.users.index'))
        ->assertSessionHas('success');

    $createdUser = User::where('email', 'daniel@example.com')->first();
    expect($createdUser)->not->toBeNull();

    Notification::assertSentTo(
        $createdUser,
        AppNotification::class,
        function (AppNotification $notification) use ($createdUser): bool {
            expect($notification->payload->type)->toBe('user_welcome');

            $mail = $notification->toMail($createdUser);
            expect((string) $mail->render())->toContain('Welcome to')
                ->and((string) $mail->render())->toContain('Hello <strong>Daniel Newbie</strong>')
                ->and((string) $mail->render())->toContain('Acme Global Corp');

            return true;
        }
    );
});

test('domain modules can pass arbitrary custom tokens in NotificationPayload and render in templates', function (): void {
    // 1. Create a custom email template with arbitrary domain tokens
    EmailTemplate::create([
        'company_id' => $this->company->id,
        'event' => 'invoice_generated',
        'name' => 'Invoice Generated Template',
        'subject' => '[{{ company_name }}] Invoice #{{ invoice_number }} Ready',
        'body_html' => '<div class="invoice">Hello {{ user_name }}, your invoice #{{ invoice_number }} for <strong>{{ total_amount }}</strong> is due on {{ due_date }}.</div>',
        'body_text' => 'Hello {{ user_name }}, invoice #{{ invoice_number }} for {{ total_amount }} is due on {{ due_date }}.',
        'is_active' => true,
    ]);

    $recipient = User::factory()->create(['name' => 'Eva Customer', 'email' => 'eva@example.com']);

    // 2. Dispatch payload with arbitrary custom variables
    $payload = new NotificationPayload(
        title: 'Invoice Ready',
        message: 'Your monthly invoice is available.',
        type: 'invoice_generated',
        url: 'https://scaffolding.test/invoices/999',
        actionText: 'View Invoice',
        companyId: $this->company->id,
        companyName: 'Acme Global Corp',
        variables: [
            'invoice_number' => 'INV-2026-999',
            'total_amount' => '$3,450.00',
            'due_date' => '2026-09-15',
        ]
    );

    $notification = new AppNotification($payload);
    $mail = $notification->toMail($recipient);

    expect($mail->subject)->toBe('[Acme Global Corp] Invoice #INV-2026-999 Ready')
        ->and((string) $mail->render())->toContain('Hello Eva Customer, your invoice #INV-2026-999 for <strong>$3,450.00</strong> is due on 2026-09-15.');
});
