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
    $this->company = Company::factory()->create(['name' => 'HQ Corporation']);

    $this->adminRole = Role::create([
        'name' => 'admin',
        'permissions' => ['manage-system-settings', 'manage-company-settings'],
    ]);

    $this->adminUser = User::factory()->create(['email' => 'admin@example.com']);
    $this->adminUser->companies()->attach($this->company->id, ['status' => 'active']);
    $this->adminUser->companyRoles()->attach($this->adminRole->id, ['company_id' => $this->company->id]);

    session(['active_company_id' => $this->company->id]);
});

it('renders general settings component with initial values', function (): void {
    $this->actingAs($this->adminUser);

    Livewire::test('admin.settings.general-settings')
        ->assertSuccessful()
        ->assertSet('app_name', config('app.name', 'Scaffolding App'))
        ->assertSee('Application Name');
});

it('saves updated general settings and uploads assets', function (): void {
    Storage::fake('public');
    $this->actingAs($this->adminUser);

    $logo = UploadedFile::fake()->image('custom_app_logo.png', 300, 100);
    $favicon = UploadedFile::fake()->image('favicon.ico', 32, 32);

    Livewire::test('admin.settings.general-settings')
        ->set('app_name', 'Acme ERP Enterprise')
        ->set('default_timezone', 'Asia/Tokyo')
        ->set('date_format', 'd M Y')
        ->set('time_format', '12h')
        ->set('default_theme', 'dark')
        ->set('logo', $logo)
        ->set('favicon', $favicon)
        ->call('save')
        ->assertRedirect(route('admin.settings.general'))
        ->assertSessionHas('success', 'General & localization settings updated successfully.');

    $settingService = app(SettingService::class);
    expect($settingService->get('app_name'))->toBe('Acme ERP Enterprise')
        ->and($settingService->get('default_timezone'))->toBe('Asia/Tokyo')
        ->and($settingService->get('default_theme'))->toBe('dark');
});

it('validates input parameters correctly', function (): void {
    $this->actingAs($this->adminUser);

    Livewire::test('admin.settings.general-settings')
        ->set('app_name', '')
        ->call('save')
        ->assertHasErrors(['app_name' => 'required']);
});
