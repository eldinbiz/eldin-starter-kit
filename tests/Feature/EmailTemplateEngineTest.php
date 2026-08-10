<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\EmailTemplate;
use App\Models\Role;
use App\Models\User;
use App\Notifications\AppNotification;
use App\Services\EmailTemplateService;
use App\ValueObjects\NotificationPayload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->templateService = app(EmailTemplateService::class);

    $this->company = Company::factory()->create(['name' => 'Acme Corporation']);
    $this->adminUser = User::factory()->create(['email' => 'admin@example.com', 'name' => 'System Admin']);
    $this->adminRole = Role::create([
        'name' => 'admin',
        'permissions' => ['manage-system-settings', 'manage-company-settings'],
    ]);

    $this->adminUser->companies()->attach($this->company->id, ['status' => 'active']);
    $this->adminUser->companyRoles()->attach($this->adminRole->id, ['company_id' => $this->company->id]);
    session(['active_company_id' => $this->company->id]);
});

test('it renders email templates and substitutes placeholder tokens accurately', function (): void {
    EmailTemplate::create([
        'company_id' => null,
        'event' => 'approval_requested',
        'name' => 'Approval Requested Notification',
        'subject' => '[{{ company_name }}] Action Required: {{ title }}',
        'body_html' => '<p>Hello {{ user_name }},</p><p>Please approve: <a href="{{ approval_link }}">{{ title }}</a></p>',
        'body_text' => 'Hello {{ user_name }}, please approve {{ title }} at {{ approval_link }}',
        'is_active' => true,
    ]);

    $variables = [
        'user_name' => 'Alice Johnson',
        'company_name' => 'Acme Corporation',
        'title' => 'MacBook Pro Purchase Request #101',
        'approval_link' => 'https://scaffolding.test/approvals/requests/101',
    ];

    $rendered = $this->templateService->render('approval_requested', $variables);

    expect($rendered['subject'])->toBe('[Acme Corporation] Action Required: MacBook Pro Purchase Request #101')
        ->and($rendered['html'])->toContain('Hello Alice Johnson,')
        ->and($rendered['html'])->toContain('href="https://scaffolding.test/approvals/requests/101"')
        ->and($rendered['text'])->toContain('Alice Johnson');
});

test('it allows companies to override global email templates', function (): void {
    $companyB = Company::factory()->create(['name' => 'Custom Subsidiary']);

    // Global template
    EmailTemplate::create([
        'company_id' => null,
        'event' => 'approval_requested',
        'name' => 'Global Approval Template',
        'subject' => 'Global: {{ title }}',
        'body_html' => '<p>Global body for {{ user_name }}</p>',
        'is_active' => true,
    ]);

    // Company B custom override
    EmailTemplate::create([
        'company_id' => $companyB->id,
        'event' => 'approval_requested',
        'name' => 'Company B Custom Approval Template',
        'subject' => 'Custom B: {{ title }}',
        'body_html' => '<p>Custom Subsidiary body for {{ user_name }}</p>',
        'is_active' => true,
    ]);

    $vars = ['title' => 'Budget Request', 'user_name' => 'Bob'];

    // 1. Company B resolves to custom override
    $companyBRendered = $this->templateService->render('approval_requested', $vars, $companyB->id);
    expect($companyBRendered['subject'])->toBe('Custom B: Budget Request')
        ->and($companyBRendered['html'])->toContain('Custom Subsidiary body for Bob');

    // 2. Main Company (no override) resolves to global template
    $globalRendered = $this->templateService->render('approval_requested', $vars, $this->company->id);
    expect($globalRendered['subject'])->toBe('Global: Budget Request')
        ->and($globalRendered['html'])->toContain('Global body for Bob');
});

test('it dispatches live test email for template', function (): void {
    Mail::fake();

    $template = EmailTemplate::create([
        'company_id' => null,
        'event' => 'approval_completed',
        'name' => 'Approval Completed Notification',
        'subject' => 'Completed: {{ title }}',
        'body_html' => '<p>Your request {{ title }} is approved.</p>',
        'is_active' => true,
    ]);

    $this->actingAs($this->adminUser);

    $response = $this->postJson(route('admin.settings.email-templates.test', $template->id), [
        'recipient_email' => 'tester@example.com',
    ]);

    $response->assertOk()
        ->assertJson(['success' => true]);

    Mail::assertSentCount(1);
});

test('AppNotification integrates with EmailTemplateService during toMail rendering', function (): void {
    EmailTemplate::create([
        'company_id' => null,
        'event' => 'approval_requested',
        'name' => 'Approval Requested Notification',
        'subject' => '[{{ company_name }}] Action Required: {{ title }}',
        'body_html' => '<p>Custom HTML: {{ message }}</p>',
        'is_active' => true,
    ]);

    $payload = new NotificationPayload(
        type: 'approval_requested',
        title: 'New Hardware PR',
        message: 'Please review and approve hardware requisition.',
        url: 'https://scaffolding.test/approvals/1',
        actionText: 'Review Now',
        companyId: $this->company->id,
        companyName: 'Acme Corporation',
    );

    $notification = new AppNotification($payload);
    $recipient = User::factory()->make(['name' => 'David Miller', 'email' => 'david@example.com']);

    $mailMessage = $notification->toMail($recipient);

    expect($mailMessage->subject)->toBe('[Acme Corporation] Action Required: New Hardware PR')
        ->and((string) $mailMessage->render())->toContain('Custom HTML: Please review and approve hardware requisition.');
});
