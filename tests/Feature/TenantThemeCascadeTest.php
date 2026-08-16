<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\User;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authenticated user sees active company theme variables injected into layout', function (): void {
    $company = Company::factory()->create(['name' => 'Emerald Tech']);
    $user = User::factory()->create();
    $user->companies()->attach($company->id, ['status' => 'active']);

    $settingService = app(SettingService::class);
    $settingService->set('brand_theme', 'emerald', 'text', 'company', $company->id);

    $this->actingAs($user)
        ->withSession(['active_company_id' => $company->id])
        ->get('/')
        ->assertOk()
        ->assertSee('--color-brand-500: #059669', false)
        ->assertSee('--color-brand-600: #047857', false);
});

test('user in company with default theme does not receive unnecessary style overrides', function (): void {
    $company = Company::factory()->create(['name' => 'Standard Corp']);
    $user = User::factory()->create();
    $user->companies()->attach($company->id, ['status' => 'active']);

    $settingService = app(SettingService::class);
    $settingService->set('brand_theme', 'brand', 'text', 'company', $company->id);

    $this->actingAs($user)
        ->withSession(['active_company_id' => $company->id])
        ->get('/')
        ->assertOk()
        ->assertDontSee('--color-brand-500: #059669', false);
});

test('active company logo is rendered in sidebar when company has custom logo', function (): void {
    $company = Company::factory()->create([
        'name' => 'Acme Labs',
        'logo' => '/storage/companies/logos/acme.png',
    ]);
    $user = User::factory()->create();
    $user->companies()->attach($company->id, ['status' => 'active']);

    $this->actingAs($user)
        ->withSession(['active_company_id' => $company->id])
        ->get('/')
        ->assertOk()
        ->assertSee('/storage/companies/logos/acme.png', false);
});
