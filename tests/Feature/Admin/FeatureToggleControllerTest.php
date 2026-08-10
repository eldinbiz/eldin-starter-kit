<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use App\Services\FeatureService;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->company = Company::factory()->create(['name' => 'HQ Corporation']);

    $this->platformAdminRole = Role::create([
        'name' => 'platform-admin',
        'permissions' => ['*'],
    ]);

    $this->companyAdminRole = Role::create([
        'name' => 'admin',
        'permissions' => ['manage-system-settings', 'manage-company-settings'],
    ]);

    $this->platformAdmin = User::factory()->create(['email' => 'platform@example.com']);
    $this->platformAdmin->companies()->attach($this->company->id, ['status' => 'active']);
    $this->platformAdmin->companyRoles()->attach($this->platformAdminRole->id, ['company_id' => $this->company->id]);

    $this->companyAdmin = User::factory()->create(['email' => 'company-admin@example.com']);
    $this->companyAdmin->companies()->attach($this->company->id, ['status' => 'active']);
    $this->companyAdmin->companyRoles()->attach($this->companyAdminRole->id, ['company_id' => $this->company->id]);

    session(['active_company_id' => $this->company->id]);

    app(FeatureService::class)->flush();
});

test('guests are redirected to login when visiting the feature toggles page', function (): void {
    $this->get(route('admin.settings.features'))->assertRedirect(route('login'));
});

test('platform admin can view the feature toggles page', function (): void {
    $this->actingAs($this->platformAdmin)
        ->get(route('admin.settings.features'))
        ->assertOk()
        ->assertSee('Feature Toggles')
        ->assertSee('Approvals')
        ->assertSee('Audit Logs')
        ->assertSee('API Access');
});

test('company admin with manage-system-settings is forbidden from the feature toggles page', function (): void {
    $this->actingAs($this->companyAdmin)
        ->get(route('admin.settings.features'))
        ->assertForbidden();
});

test('feature toggles tab is not rendered for company admins', function (): void {
    $this->actingAs($this->companyAdmin)
        ->get(route('admin.settings.general'))
        ->assertOk()
        ->assertDontSee('Feature Toggles');
});

test('platform admin can set a feature off globally', function (): void {
    $this->actingAs($this->platformAdmin)
        ->put(route('admin.settings.features.update'), [
            'features' => [
                'approvals' => '0',
                'audit_logs' => '1',
                'api_access' => '1',
            ],
        ])
        ->assertRedirect(route('admin.settings.features'))
        ->assertSessionHas('success');

    expect(app(SettingService::class)->get('feature_approvals'))->toBeFalse()
        ->and(app(SettingService::class)->get('feature_audit_logs'))->toBeTrue()
        ->and(app(SettingService::class)->get('feature_api_access'))->toBeTrue();
});

test('platform admin can set a company override that beats the global value', function (): void {
    $companyB = Company::factory()->create(['name' => 'Sister Corp']);

    app(SettingService::class)->set('feature_audit_logs', false, 'boolean', 'feature');

    $this->actingAs($this->platformAdmin)
        ->put(route('admin.settings.features.update'), [
            'company_id' => $this->company->id,
            'features' => [
                'approvals' => '1',
                'audit_logs' => '1',
                'api_access' => '1',
            ],
        ])
        ->assertRedirect(route('admin.settings.features', ['company_id' => $this->company->id]))
        ->assertSessionHas('success');

    app(FeatureService::class)->flush();

    expect(app(FeatureService::class)->isEnabled('audit_logs', $this->company->id))->toBeTrue()
        ->and(app(FeatureService::class)->isEnabled('audit_logs', $companyB->id))->toBeFalse();
});

test('platform admin can reset a company override back to use global', function (): void {
    app(SettingService::class)->set('feature_approvals', true, 'boolean', 'feature', $this->company->id);

    $this->actingAs($this->platformAdmin)
        ->put(route('admin.settings.features.update'), [
            'company_id' => $this->company->id,
            'features' => [
                'approvals' => '',
                'audit_logs' => '1',
                'api_access' => '1',
            ],
        ])
        ->assertRedirect(route('admin.settings.features', ['company_id' => $this->company->id]))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('system_settings', [
        'company_id' => $this->company->id,
        'key' => 'feature_approvals',
    ]);

    app(FeatureService::class)->flush();

    expect(app(FeatureService::class)->isEnabled('approvals', $this->company->id))->toBeTrue();
});
