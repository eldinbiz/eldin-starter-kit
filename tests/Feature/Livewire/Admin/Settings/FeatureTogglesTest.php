<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire\Admin\Settings;

use App\Livewire\Admin\Settings\FeatureToggles;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use App\Services\FeatureService;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->company = Company::factory()->create(['name' => 'HQ Corporation', 'is_active' => true]);

    $this->adminRole = Role::create([
        'name' => 'platform-admin',
        'permissions' => ['manage-platform'],
    ]);

    $this->adminUser = User::factory()->create(['email' => 'admin@example.com']);
    $this->adminUser->companies()->attach($this->company->id, ['status' => 'active']);
    $this->adminUser->companyRoles()->attach($this->adminRole->id, ['company_id' => $this->company->id]);

    session(['active_company_id' => $this->company->id]);
});

it('renders feature toggles component with global and company sections', function (): void {
    $this->actingAs($this->adminUser);

    Livewire::test(FeatureToggles::class)
        ->assertSuccessful()
        ->assertSee('Global Defaults')
        ->assertSee('Per-Company Overrides');
});

it('toggles global features reactively', function (): void {
    $this->actingAs($this->adminUser);
    $featureService = app(FeatureService::class);
    $settingService = app(SettingService::class);
    $all = $featureService->all();
    $firstKey = array_key_first($all);

    $initialVal = (bool) $settingService->get(
        $all[$firstKey]['setting_key'],
        null,
        $all[$firstKey]['default_enabled'] ?? true
    );

    Livewire::test(FeatureToggles::class)
        ->call('toggleGlobal', $firstKey)
        ->assertSet('flashSuccess', 'Global feature toggles updated.');

    $updatedVal = (bool) $settingService->get(
        $all[$firstKey]['setting_key'],
        null,
        $all[$firstKey]['default_enabled'] ?? true
    );
    expect($updatedVal)->toBe(! $initialVal);
});

it('saves per company overrides', function (): void {
    $this->actingAs($this->adminUser);
    $featureService = app(FeatureService::class);
    $all = $featureService->all();
    $firstKey = array_key_first($all);

    Livewire::test(FeatureToggles::class)
        ->set('selectedCompanyId', $this->company->id)
        ->set("companyValues.{$firstKey}", '0')
        ->call('saveCompany')
        ->assertSet('flashSuccess', 'Company feature overrides saved successfully.');

    expect($featureService->isEnabled($firstKey, $this->company->id))->toBeFalse();
});
