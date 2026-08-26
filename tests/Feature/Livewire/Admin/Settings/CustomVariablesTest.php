<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire\Admin\Settings;

use App\Livewire\Admin\Settings\CustomVariables;
use App\Models\Company;
use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->company = Company::factory()->create(['name' => 'HQ Corporation', 'is_active' => true]);

    $this->adminRole = Role::create([
        'name' => 'admin',
        'permissions' => ['manage-system-settings'],
    ]);

    $this->adminUser = User::factory()->create(['email' => 'admin@example.com']);
    $this->adminUser->companies()->attach($this->company->id, ['status' => 'active']);
    $this->adminUser->companyRoles()->attach($this->adminRole->id, ['company_id' => $this->company->id]);

    session(['active_company_id' => $this->company->id]);
});

it('renders custom variables list and filters by scope', function (): void {
    $this->actingAs($this->adminUser);

    $settingService = app(SettingService::class);
    $settingService->set('global_var_1', 'val1', 'text', 'custom');
    $settingService->set('company_var_1', 'comp_val', 'text', 'custom', $this->company->id);

    Livewire::test(CustomVariables::class)
        ->assertSuccessful()
        ->assertSee('global_var_1')
        ->set('selectedCompanyId', $this->company->id)
        ->assertSee('company_var_1');
});

it('creates new custom variable and handles json validation', function (): void {
    $this->actingAs($this->adminUser);

    Livewire::test(CustomVariables::class)
        ->call('openCreateModal')
        ->set('formKey', 'max_upload_size')
        ->set('formType', 'numeric')
        ->set('formValue', '100')
        ->set('formDescription', 'Max upload size in MB')
        ->call('save')
        ->assertSet('modalOpen', false);

    $settingService = app(SettingService::class);
    expect($settingService->get('max_upload_size'))->toBe(100);

    // Invalid JSON validation test
    Livewire::test(CustomVariables::class)
        ->call('openCreateModal')
        ->set('formKey', 'invalid_json_var')
        ->set('formType', 'json')
        ->set('formValue', '{not valid json}')
        ->call('save')
        ->assertHasErrors(['formValue']);
});

it('edits and deletes custom variable', function (): void {
    $this->actingAs($this->adminUser);

    $settingService = app(SettingService::class);
    $settingService->set('to_delete_var', 'temp', 'text', 'custom');
    $var = SystemSetting::customVariables()->where('key', 'to_delete_var')->first();

    Livewire::test(CustomVariables::class)
        ->call('openEditModal', $var->id)
        ->assertSet('formKey', 'to_delete_var')
        ->assertSet('formValue', 'temp')
        ->call('closeModal')
        ->call('promptDelete', $var->id)
        ->assertSet('variableToDeleteId', $var->id)
        ->call('deleteVariable')
        ->assertSet('variableToDeleteId', null);

    expect(SystemSetting::customVariables()->where('key', 'to_delete_var')->exists())->toBeFalse();
});
