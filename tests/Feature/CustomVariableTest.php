<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->settingService = app(SettingService::class);

    $this->company = Company::factory()->create(['name' => 'Main Company']);
    $this->adminUser = User::factory()->create(['email' => 'admin@example.com']);
    $this->adminRole = Role::create([
        'name' => 'admin',
        'permissions' => ['manage-system-settings', 'manage-company-settings'],
    ]);

    $this->adminUser->companies()->attach($this->company->id, ['status' => 'active']);
    $this->adminUser->companyRoles()->attach($this->adminRole->id, ['company_id' => $this->company->id]);
    session(['active_company_id' => $this->company->id]);
});

test('it creates and retrieves custom variables with type casting', function (): void {
    $this->actingAs($this->adminUser);

    // 1. Text variable
    $resText = $this->post(route('admin.settings.custom-variables.store'), [
        'key' => 'support_hotline',
        'type' => 'text',
        'value' => '+1-800-555-0199',
        'description' => 'Helpdesk phone line',
    ]);
    $resText->assertRedirect();

    expect($this->settingService->custom('support_hotline'))->toBe('+1-800-555-0199');

    // 2. Numeric variable
    $resNum = $this->post(route('admin.settings.custom-variables.store'), [
        'key' => 'max_upload_size_mb',
        'type' => 'numeric',
        'value' => '50',
        'description' => 'Maximum upload file size',
    ]);
    $resNum->assertRedirect();

    expect($this->settingService->custom('max_upload_size_mb'))->toBe(50);

    // 3. JSON variable
    $jsonContent = json_encode(['banner' => 'Maintenance Alert', 'priority' => 1]);
    $resJson = $this->post(route('admin.settings.custom-variables.store'), [
        'key' => 'system_banner_config',
        'type' => 'json',
        'value' => $jsonContent,
        'description' => 'Banner configuration',
    ]);
    $resJson->assertRedirect();

    $banner = $this->settingService->custom('system_banner_config');
    expect($banner)->toBeArray()
        ->and($banner['banner'])->toBe('Maintenance Alert')
        ->and($banner['priority'])->toBe(1);
});

test('it validates data types when storing custom variables', function (): void {
    $this->actingAs($this->adminUser);

    // Reject non-numeric for numeric type
    $resNum = $this->from(route('admin.settings.custom-variables'))->post(route('admin.settings.custom-variables.store'), [
        'key' => 'invalid_number',
        'type' => 'numeric',
        'value' => 'not-a-valid-number',
    ]);
    $resNum->assertSessionHasErrors('value');

    // Reject malformed JSON for json type
    $resJson = $this->from(route('admin.settings.custom-variables'))->post(route('admin.settings.custom-variables.store'), [
        'key' => 'invalid_json',
        'type' => 'json',
        'value' => '{ unquoted_key: invalid }',
    ]);
    $resJson->assertSessionHasErrors('value');
});

test('it updates and deletes custom variables successfully', function (): void {
    $this->actingAs($this->adminUser);

    $variable = SystemSetting::create([
        'key' => 'timeout_seconds',
        'value' => '30',
        'type' => 'numeric',
        'group' => 'custom',
        'description' => 'Timeout limit',
    ]);

    // Update
    $resUpdate = $this->put(route('admin.settings.custom-variables.update', $variable->id), [
        'type' => 'numeric',
        'value' => '60',
        'description' => 'Updated timeout limit',
    ]);
    $resUpdate->assertRedirect();

    expect($this->settingService->custom('timeout_seconds'))->toBe(60);

    // Delete
    $resDelete = $this->delete(route('admin.settings.custom-variables.destroy', $variable->id));
    $resDelete->assertRedirect();

    expect($this->settingService->custom('timeout_seconds'))->toBeNull();
    expect(SystemSetting::find($variable->id))->toBeNull();
});
