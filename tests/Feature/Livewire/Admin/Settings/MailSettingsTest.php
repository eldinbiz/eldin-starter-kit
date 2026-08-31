<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire\Admin\Settings;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->company = Company::factory()->create(['name' => 'HQ Corporation']);

    $this->adminRole = Role::create([
        'name' => 'admin',
        'permissions' => ['manage-system-settings'],
    ]);

    $this->adminUser = User::factory()->create(['email' => 'admin@example.com']);
    $this->adminUser->companies()->attach($this->company->id, ['status' => 'active']);
    $this->adminUser->companyRoles()->attach($this->adminRole->id, ['company_id' => $this->company->id]);

    session(['active_company_id' => $this->company->id]);
});

it('renders mail settings component with transport options', function (): void {
    $this->actingAs($this->adminUser);

    Livewire::test('admin.settings.mail-settings')
        ->assertSuccessful()
        ->assertSee('Active Mail Transport Driver')
        ->assertSee('SMTP Transport')
        ->assertSee('OAuth2 Modern Auth')
        ->assertSee('Log / Development');
});

it('saves SMTP settings', function (): void {
    $this->actingAs($this->adminUser);

    Livewire::test('admin.settings.mail-settings')
        ->call('selectMailer', 'smtp')
        ->set('mail_host', 'smtp.example.com')
        ->set('mail_port', '465')
        ->set('mail_username', 'user@example.com')
        ->set('mail_password', 'secret123')
        ->set('mail_encryption', 'ssl')
        ->set('mail_from_address', 'notifications@example.com')
        ->set('mail_from_name', 'Example App')
        ->call('save')
        ->assertSet('flashSuccess', 'Mail and transport settings saved successfully.');

    $settingService = app(SettingService::class);
    expect($settingService->get('mail_mailer'))->toBe('smtp')
        ->and($settingService->get('mail_host'))->toBe('smtp.example.com')
        ->and((int) $settingService->get('mail_port'))->toBe(465)
        ->and($settingService->get('mail_from_address'))->toBe('notifications@example.com');
});

it('dispatches live test email successfully', function (): void {
    Mail::fake();
    $this->actingAs($this->adminUser);

    Livewire::test('admin.settings.mail-settings')
        ->set('test_recipient', 'tester@example.com')
        ->call('sendTestEmail')
        ->assertSet('testStatus', 'success');

    Mail::assertSent(\App\Mail\TestEmail::class, function ($mail) {
        return $mail->hasTo('tester@example.com');
    });
});
