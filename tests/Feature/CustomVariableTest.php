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
    $resText->assertSessionHas('success', "Custom variable 'support_hotline' created successfully.");

    expect($this->settingService->custom('support_hotline'))->toBe('+1-800-555-0199');

    // 2. Numeric variable
    $resNum = $this->post(route('admin.settings.custom-variables.store'), [
        'key' => 'max_upload_size_mb',
        'type' => 'numeric',
        'value' => '50',
        'description' => 'Maximum upload file size',
    ]);
    $resNum->assertRedirect();
    $resNum->assertSessionHas('success', "Custom variable 'max_upload_size_mb' created successfully.");

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
    $resJson->assertSessionHas('success', "Custom variable 'system_banner_config' created successfully.");

    $banner = $this->settingService->custom('system_banner_config');
    expect($banner)->toBeArray()
        ->and($banner['banner'])->toBe('Maintenance Alert')
        ->and($banner['priority'])->toBe(1);
});

test('it updates an existing key on store and provides differentiated feedback', function (): void {
    $this->actingAs($this->adminUser);

    // Create initial variable
    $this->post(route('admin.settings.custom-variables.store'), [
        'key' => 'support_hotline',
        'type' => 'text',
        'value' => '+1-800-555-0199',
        'description' => 'Original hotline',
    ])->assertRedirect()->assertSessionHas('success', "Custom variable 'support_hotline' created successfully.");

    // Store again with same key (overwrite)
    $resOverwrite = $this->post(route('admin.settings.custom-variables.store'), [
        'key' => 'support_hotline',
        'type' => 'text',
        'value' => '+1-888-555-9999',
        'description' => 'Updated hotline',
    ]);

    $resOverwrite->assertRedirect();
    $resOverwrite->assertSessionHas('success', "Custom variable 'support_hotline' was updated with the new value.");

    expect($this->settingService->custom('support_hotline'))->toBe('+1-888-555-9999');
    expect(SystemSetting::customVariables()->where('key', 'support_hotline')->count())->toBe(1);
});

test('it checks key existence in database correctly via check endpoint', function (): void {
    $this->actingAs($this->adminUser);

    // 1. Check non-existent key
    $resNone = $this->postJson(route('admin.settings.custom-variables.check'), [
        'key' => 'non_existent_var',
        'company_id' => null,
    ]);
    $resNone->assertOk()->assertJson(['exists' => false]);

    // 2. Create global variable
    SystemSetting::create([
        'key' => 'global_var',
        'value' => 'global-value',
        'type' => 'text',
        'group' => 'custom',
        'company_id' => null,
        'description' => 'Global description',
    ]);

    // Check global variable
    $resGlobal = $this->postJson(route('admin.settings.custom-variables.check'), [
        'key' => 'global_var',
        'company_id' => null,
    ]);
    $resGlobal->assertOk()->assertJson([
        'exists' => true,
        'key' => 'global_var',
        'type' => 'text',
        'value' => 'global-value',
        'company_id' => null,
        'company_name' => 'Global System Defaults',
        'description' => 'Global description',
    ]);

    // Check same key under company scope -> should not exist
    $resGlobalUnderCompany = $this->postJson(route('admin.settings.custom-variables.check'), [
        'key' => 'global_var',
        'company_id' => $this->company->id,
    ]);
    $resGlobalUnderCompany->assertOk()->assertJson(['exists' => false]);

    // 3. Create company-scoped variable
    SystemSetting::create([
        'key' => 'company_var',
        'value' => '100',
        'type' => 'numeric',
        'group' => 'custom',
        'company_id' => $this->company->id,
        'description' => 'Company specific',
    ]);

    // Check under company scope
    $resCompany = $this->postJson(route('admin.settings.custom-variables.check'), [
        'key' => 'company_var',
        'company_id' => $this->company->id,
    ]);
    $resCompany->assertOk()->assertJson([
        'exists' => true,
        'key' => 'company_var',
        'type' => 'numeric',
        'value' => '100',
        'company_id' => $this->company->id,
        'company_name' => 'Main Company',
        'description' => 'Company specific',
    ]);
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
    $resUpdate->assertSessionHas('success', "Custom variable 'timeout_seconds' updated successfully.");

    expect($this->settingService->custom('timeout_seconds'))->toBe(60);

    // Delete
    $resDelete = $this->delete(route('admin.settings.custom-variables.destroy', $variable->id));
    $resDelete->assertRedirect();

    expect($this->settingService->custom('timeout_seconds'))->toBeNull();
    expect(SystemSetting::find($variable->id))->toBeNull();
});
