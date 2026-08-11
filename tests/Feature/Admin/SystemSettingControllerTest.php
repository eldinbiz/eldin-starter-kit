<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Company;
use App\Models\EmailTemplate;
use App\Models\Role;
use App\Models\User;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->company = Company::factory()->create(['name' => 'HQ Corporation']);

    $this->adminRole = Role::create([
        'name' => 'admin',
        'permissions' => ['manage-system-settings', 'manage-company-settings'],
    ]);

    $this->userRole = Role::create([
        'name' => 'user',
        'permissions' => ['view-dashboard'],
    ]);

    $this->adminUser = User::factory()->create(['email' => 'admin@example.com']);
    $this->adminUser->companies()->attach($this->company->id, ['status' => 'active']);
    $this->adminUser->companyRoles()->attach($this->adminRole->id, ['company_id' => $this->company->id]);

    $this->regularUser = User::factory()->create(['email' => 'user@example.com']);
    $this->regularUser->companies()->attach($this->company->id, ['status' => 'active']);
    $this->regularUser->companyRoles()->attach($this->userRole->id, ['company_id' => $this->company->id]);

    session(['active_company_id' => $this->company->id]);
});

test('unauthorized users are denied access to system settings', function (): void {
    // Guest
    $this->get(route('admin.settings.general'))->assertRedirect(route('login'));

    // Regular user without manage-system-settings permission
    $this->actingAs($this->regularUser);
    $this->get(route('admin.settings.general'))->assertForbidden();
});

test('admin can view and update general settings with branding logo and favicon', function (): void {
    Storage::fake('public');
    $this->actingAs($this->adminUser);

    $this->get(route('admin.settings.general'))
        ->assertOk()
        ->assertSee('General &amp; Localization Settings', false);

    $logo = UploadedFile::fake()->image('custom_logo.png', 200, 50);
    $favicon = UploadedFile::fake()->image('custom_fav.png', 32, 32);

    $response = $this->put(route('admin.settings.general.update'), [
        'app_name' => 'Custom Enterprise Platform',
        'default_timezone' => 'Asia/Jakarta',
        'date_format' => 'd/m/Y',
        'time_format' => '12h',
        'default_theme' => 'dark',
        'app_logo' => $logo,
        'app_favicon' => $favicon,
    ]);

    $response->assertRedirect(route('admin.settings.general'))
        ->assertSessionHas('success');

    $settingService = app(SettingService::class);
    expect($settingService->get('app_name'))->toBe('Custom Enterprise Platform')
        ->and(config('app.name'))->toBe('Custom Enterprise Platform')
        ->and($settingService->get('default_timezone'))->toBe('Asia/Jakarta')
        ->and($settingService->get('date_format'))->toBe('d/m/Y')
        ->and($settingService->get('time_format'))->toBe('12h')
        ->and($settingService->get('default_theme'))->toBe('dark')
        ->and($settingService->get('app_logo'))->toContain('custom_logo.png')
        ->and($settingService->get('app_favicon'))->toContain('custom_fav.png');

    $this->get(route('admin.settings.general'))
        ->assertOk()
        ->assertSee('Custom Enterprise Platform')
        ->assertSee('<span>Custom Enterprise Platform</span>', false)
        ->assertSee('custom_logo.png', false)
        ->assertSee('custom_fav.png', false);
});

test('admin can view and update mail settings and send test email', function (): void {
    Mail::fake();
    $this->actingAs($this->adminUser);

    $this->get(route('admin.settings.mail'))
        ->assertOk()
        ->assertSee('Mail Transport &amp; SMTP Configuration', false);

    $response = $this->put(route('admin.settings.mail.update'), [
        'mail_mailer' => 'smtp',
        'mail_host' => 'smtp.custom-provider.com',
        'mail_port' => 587,
        'mail_username' => 'smtp_user',
        'mail_password' => 'secret_smtp_password',
        'mail_encryption' => 'tls',
        'mail_from_address' => 'system@enterprise.test',
        'mail_from_name' => 'Enterprise System',
    ]);

    $response->assertRedirect(route('admin.settings.mail'))
        ->assertSessionHas('success');

    $settingService = app(SettingService::class);
    expect($settingService->get('mail_host'))->toBe('smtp.custom-provider.com')
        ->and($settingService->get('mail_password'))->toBe('secret_smtp_password');

    // Test live test mail endpoint
    $testMailResponse = $this->postJson(route('admin.settings.mail.test'), [
        'recipient_email' => 'admin.test@example.com',
    ]);

    $testMailResponse->assertOk()
        ->assertJson(['success' => true]);

    Mail::assertSentCount(1);
});

test('admin can update company branding and timezone preferences', function (): void {
    Storage::fake('public');
    $this->actingAs($this->adminUser);

    $this->get(route('admin.settings.company', ['company_id' => $this->company->id]))
        ->assertOk()
        ->assertSee('Company Branding &amp; Preferences', false);

    $logo = UploadedFile::fake()->image('company_badge.png', 100, 100);

    $response = $this->put(route('admin.settings.company.update'), [
        'company_id' => $this->company->id,
        'accent_color' => '#10B981',
        'company_timezone' => 'America/New_York',
        'company_date_format' => 'm/d/Y',
        'company_logo' => $logo,
    ]);

    $response->assertRedirect(route('admin.settings.company', ['company_id' => $this->company->id]))
        ->assertSessionHas('success');

    $settingService = app(SettingService::class);
    expect($settingService->get('accent_color', $this->company->id))->toBe('#10B981')
        ->and($settingService->get('company_timezone', $this->company->id))->toBe('America/New_York')
        ->and($settingService->get('company_date_format', $this->company->id))->toBe('m/d/Y')
        ->and($settingService->get('company_logo', $this->company->id))->toContain('company_badge.png');
});

test('admin can view, edit, and preview email templates', function (): void {
    $this->actingAs($this->adminUser);

    $template = EmailTemplate::create([
        'company_id' => null,
        'event' => 'approval_rejected',
        'name' => 'Rejection Notice',
        'subject' => 'Rejected: {{ title }}',
        'body_html' => '<p>Your request {{ title }} was rejected.</p>',
        'is_active' => true,
    ]);

    // Index
    $this->get(route('admin.settings.email-templates.index'))
        ->assertOk()
        ->assertSee('Rejection Notice')
        ->assertDontSee('class=\"text-error-500', false);

    // Edit view
    $this->get(route('admin.settings.email-templates.edit', $template->id))
        ->assertOk()
        ->assertSee('approval_rejected')
        ->assertDontSee('class=\"text-error-500', false)
        ->assertDontSee('{{ {{ $tok }} }}', false)
        ->assertSee('&#123;&#123; user_name &#125;&#125;', false);

    // Update
    $updateRes = $this->put(route('admin.settings.email-templates.update', $template->id), [
        'subject' => '[Urgent] Request Rejected: {{ title }}',
        'body_html' => '<p>Hello {{ user_name }}, your request {{ title }} was unfortunately rejected.</p>',
        'body_text' => 'Hello {{ user_name }}, request {{ title }} rejected.',
        'is_active' => '1',
    ]);

    $updateRes->assertRedirect(route('admin.settings.email-templates.index', ['company_id' => null]))
        ->assertSessionHas('success');

    $template->refresh();
    expect($template->subject)->toBe('[Urgent] Request Rejected: {{ title }}');

    // Preview JSON endpoint
    $previewRes = $this->getJson(route('admin.settings.email-templates.preview', $template->id));
    $previewRes->assertOk()
        ->assertJsonStructure(['subject', 'html'])
        ->assertJsonFragment(['subject' => '[Urgent] Request Rejected: Purchase Request PR-2026-0042']);
});
