<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire\Admin\Settings;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->companyA = Company::factory()->create(['name' => 'Alpha Holdings', 'is_active' => true]);
    $this->companyB = Company::factory()->create(['name' => 'Beta Logistics', 'is_active' => true]);

    $this->adminRole = Role::create([
        'name' => 'admin',
        'permissions' => ['manage-company-settings', 'manage-platform'],
    ]);

    $this->adminUser = User::factory()->create(['email' => 'admin@example.com']);
    $this->adminUser->companies()->attach([$this->companyA->id, $this->companyB->id], ['status' => 'active']);
    $this->adminUser->companyRoles()->attach($this->adminRole->id, ['company_id' => $this->companyA->id]);

    session(['active_company_id' => $this->companyA->id]);
});

it('renders company settings component with available companies', function (): void {
    $this->actingAs($this->adminUser);

    Livewire::test('admin.settings.company-settings')
        ->assertSuccessful()
        ->assertSee('Alpha Holdings')
        ->assertSee('Brand Theme Palette');
});

it('updates brand theme and localization overrides for specific company', function (): void {
    Storage::fake('public');
    $this->actingAs($this->adminUser);

    $logo = UploadedFile::fake()->image('alpha_logo.png', 200, 50);

    Livewire::test('admin.settings.company-settings')
        ->set('selectedCompanyId', $this->companyA->id)
        ->call('setTheme', 'emerald')
        ->set('default_timezone', 'Asia/Jakarta')
        ->set('date_format', 'd/m/Y')
        ->set('logo', $logo)
        ->call('save')
        ->assertRedirect(route('admin.settings.company', ['company_id' => $this->companyA->id]))
        ->assertSessionHas('success', 'Company preferences and theme updated successfully.');

    $settingService = app(SettingService::class);
    expect($settingService->get('brand_theme', $this->companyA->id))->toBe('emerald')
        ->and($settingService->get('default_timezone', $this->companyA->id))->toBe('Asia/Jakarta')
        ->and($settingService->get('date_format', $this->companyA->id))->toBe('d/m/Y');
});

it('switches companies and loads correct settings', function (): void {
    $this->actingAs($this->adminUser);
    $settingService = app(SettingService::class);
    $settingService->set('brand_theme', 'purple', 'text', 'company', $this->companyB->id);

    Livewire::test('admin.settings.company-settings')
        ->set('selectedCompanyId', $this->companyB->id)
        ->assertSet('brand_theme', 'purple');
});
