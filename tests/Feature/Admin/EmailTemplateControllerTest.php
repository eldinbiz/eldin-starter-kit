<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Company;
use App\Models\EmailTemplate;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->company = Company::factory()->create(['name' => 'Acme Global']);
    $this->adminUser = User::factory()->create(['email' => 'admin@example.com', 'name' => 'Admin User']);
    $this->adminRole = Role::create([
        'name' => 'admin',
        'permissions' => ['manage-system-settings', 'manage-company-settings'],
    ]);

    $this->adminUser->companies()->attach($this->company->id, ['status' => 'active']);
    $this->adminUser->companyRoles()->attach($this->adminRole->id, ['company_id' => $this->company->id]);
    session(['active_company_id' => $this->company->id]);

    $this->regularUser = User::factory()->create(['email' => 'regular@example.com']);
});

test('admin can view email templates index, search, and filter by company', function (): void {
    $this->actingAs($this->adminUser);

    $globalTpl = EmailTemplate::create([
        'company_id' => null,
        'event' => 'approval_requested',
        'name' => 'Global Approval Template',
        'subject' => 'Action Required: {{ title }}',
        'body_html' => '<p>Hello {{ user_name }}</p>',
        'is_active' => true,
    ]);

    $companyB = Company::factory()->create(['name' => 'Beta Corp']);
    $overrideTpl = EmailTemplate::create([
        'company_id' => $companyB->id,
        'event' => 'approval_requested',
        'name' => 'Beta Corp Approval Override',
        'subject' => '[Beta] Action Required: {{ title }}',
        'body_html' => '<p>Hello Beta user {{ user_name }}</p>',
        'is_active' => true,
    ]);

    // 1. Index with global filter
    $this->get(route('admin.settings.email-templates.index'))
        ->assertOk()
        ->assertSee('Global Approval Template')
        ->assertDontSee('Beta Corp Approval Override');

    // 2. Index filtered by company B
    $this->get(route('admin.settings.email-templates.index', ['company_id' => $companyB->id]))
        ->assertOk()
        ->assertSee('Global Approval Template')
        ->assertSee('Beta Corp Approval Override');

    // 3. Search query
    $this->get(route('admin.settings.email-templates.index', ['search' => 'Beta Corp']))
        ->assertOk()
        ->assertSee('Beta Corp Approval Override');
});

test('admin can view create page and pre-fill company override clone', function (): void {
    $this->actingAs($this->adminUser);

    $globalTpl = EmailTemplate::create([
        'company_id' => null,
        'event' => 'invoice_created',
        'name' => 'Invoice Created Notification',
        'subject' => 'New Invoice: {{ title }}',
        'body_html' => '<p>Invoice created for {{ company_name }}</p>',
        'body_text' => 'Invoice created',
        'is_active' => true,
    ]);

    // Regular create page
    $this->get(route('admin.settings.email-templates.create'))
        ->assertOk()
        ->assertSee('Create Email Template')
        ->assertSee('Event Trigger Key')
        ->assertSee('Rich HTML (Quill)')
        ->assertSee('Plain Text Fallback')
        ->assertSee('user_name')
        ->assertSee('company_name')
        ->assertSee('approval_link');

    // 1-Click company override clone page
    $this->get(route('admin.settings.email-templates.create', [
        'event' => 'invoice_created',
        'company_id' => $this->company->id,
    ]))
        ->assertOk()
        ->assertSee('Invoice Created Notification (Acme Global Override)')
        ->assertSee('New Invoice: {{ title }}');
});

test('admin can view edit page with placeholder token toolbar and custom template variables', function (): void {
    $this->actingAs($this->adminUser);

    $template = EmailTemplate::create([
        'company_id' => null,
        'event' => 'custom_order_shipped',
        'name' => 'Order Shipped Notification',
        'subject' => 'Your Order #{{ tracking_number }} Has Shipped',
        'body_html' => '<p>Hello {{ user_name }}, your order has shipped.</p>',
        'variables' => ['tracking_number', 'carrier_name', 'estimated_delivery'],
        'is_active' => true,
    ]);

    $this->get(route('admin.settings.email-templates.edit', $template->id))
        ->assertOk()
        ->assertSee('Edit Email Template: Order Shipped Notification')
        ->assertSee('Order Shipped Notification')
        ->assertSee('Rich HTML (Quill)')
        ->assertSee('Plain Text Fallback')
        ->assertSee('quill.snow.css')
        ->assertSee('quill.htmlEditButton.min.js')
        ->assertSee('user_name')
        ->assertSee('company_name')
        ->assertSee('tracking_number')
        ->assertSee('carrier_name')
        ->assertSee('estimated_delivery');
});

test('admin can store a new global template and prevent duplicate events', function (): void {
    $this->actingAs($this->adminUser);

    // 1. Successful store
    $response = $this->post(route('admin.settings.email-templates.store'), [
        'event' => 'user_registered',
        'name' => 'User Registration Welcome',
        'subject' => 'Welcome to {{ app_name }}',
        'body_html' => '<p>Welcome {{ user_name }}!</p>',
        'body_text' => 'Welcome {{ user_name }}!',
        'is_active' => '1',
    ]);

    $response->assertRedirect(route('admin.settings.email-templates.index', ['company_id' => null]))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('email_templates', [
        'company_id' => null,
        'event' => 'user_registered',
        'name' => 'User Registration Welcome',
    ]);

    // 2. Duplicate event rejection
    $dupResponse = $this->post(route('admin.settings.email-templates.store'), [
        'event' => 'user_registered',
        'name' => 'Duplicate User Registration',
        'subject' => 'Duplicate',
        'body_html' => '<p>Duplicate</p>',
    ]);

    $dupResponse->assertSessionHasErrors(['event']);
});

test('admin can store a company-specific override and update it', function (): void {
    $this->actingAs($this->adminUser);

    // 1. Store company override
    $storeRes = $this->post(route('admin.settings.email-templates.store'), [
        'company_id' => (string) $this->company->id,
        'event' => 'custom_reminder',
        'name' => 'Acme Custom Reminder',
        'subject' => '[Acme] Reminder: {{ title }}',
        'body_html' => '<p>Acme reminder: {{ message }}</p>',
        'is_active' => '1',
    ]);

    $storeRes->assertRedirect(route('admin.settings.email-templates.index', ['company_id' => $this->company->id]))
        ->assertSessionHas('success');

    $template = EmailTemplate::where('company_id', $this->company->id)->where('event', 'custom_reminder')->first();
    expect($template)->not->toBeNull()
        ->and($template->company_id)->toBe($this->company->id);

    // 2. Update the template
    $updateRes = $this->put(route('admin.settings.email-templates.update', $template->id), [
        'name' => 'Acme Custom Reminder (Updated)',
        'subject' => '[Acme] Updated Reminder: {{ title }}',
        'body_html' => '<p>Updated HTML: {{ message }}</p>',
        'body_text' => 'Updated text',
        'is_active' => '1',
    ]);

    $updateRes->assertRedirect(route('admin.settings.email-templates.index', ['company_id' => $this->company->id]))
        ->assertSessionHas('success');

    $template->refresh();
    expect($template->name)->toBe('Acme Custom Reminder (Updated)')
        ->and($template->subject)->toBe('[Acme] Updated Reminder: {{ title }}');
});

test('admin can delete a custom template or reset a company override', function (): void {
    $this->actingAs($this->adminUser);

    // 1. Company override reset
    $override = EmailTemplate::create([
        'company_id' => $this->company->id,
        'event' => 'test_event',
        'name' => 'Acme Test Override',
        'subject' => 'Override Subject',
        'body_html' => '<p>Override</p>',
        'is_active' => true,
    ]);

    $resetRes = $this->delete(route('admin.settings.email-templates.destroy', $override->id));
    $resetRes->assertRedirect(route('admin.settings.email-templates.index', ['company_id' => $this->company->id]))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('email_templates', ['id' => $override->id]);

    // 2. Custom global template delete
    $customGlobal = EmailTemplate::create([
        'company_id' => null,
        'event' => 'custom_global',
        'name' => 'Custom Global Template',
        'subject' => 'Global Subject',
        'body_html' => '<p>Global</p>',
        'is_active' => true,
    ]);

    $deleteRes = $this->delete(route('admin.settings.email-templates.destroy', $customGlobal->id));
    $deleteRes->assertRedirect(route('admin.settings.email-templates.index', ['company_id' => null]))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('email_templates', ['id' => $customGlobal->id]);
});

test('admin can request live preview draft without saving', function (): void {
    $this->actingAs($this->adminUser);

    $appName = (string) config('app.name', 'Scaffolding App');

    $response = $this->postJson(route('admin.settings.email-templates.preview-draft'), [
        'subject' => '[{{ company_name }}] Draft Subject for {{ user_name }}',
        'body_html' => '<p>Draft body for {{ title }} at {{ app_name }}</p>',
        'company_id' => $this->company->id,
    ]);

    $response->assertOk()
        ->assertJsonStructure(['subject', 'html'])
        ->assertJson([
            'subject' => '[Acme Global] Draft Subject for Jane Doe',
        ])
        ->assertJsonFragment([
            'html' => "<p>Draft body for Purchase Request PR-2026-0042 at {$appName}</p>",
        ]);
});

test('admin can dispatch test email and unauthorized user is blocked', function (): void {
    Mail::fake();

    $template = EmailTemplate::create([
        'company_id' => null,
        'event' => 'test_dispatch',
        'name' => 'Test Dispatch Template',
        'subject' => 'Test: {{ title }}',
        'body_html' => '<p>Test html</p>',
        'is_active' => true,
    ]);

    // 1. Admin test dispatch
    $this->actingAs($this->adminUser);
    $response = $this->postJson(route('admin.settings.email-templates.test', $template->id), [
        'recipient_email' => 'qa@example.com',
    ]);

    $response->assertOk()
        ->assertJson(['success' => true]);

    Mail::assertSentCount(1);

    // 2. Regular user is forbidden
    $this->actingAs($this->regularUser);
    $this->get(route('admin.settings.email-templates.index'))
        ->assertForbidden();

    $this->post(route('admin.settings.email-templates.store'), [])
        ->assertForbidden();
});
