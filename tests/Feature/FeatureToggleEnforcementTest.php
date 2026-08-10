<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use App\Services\FeatureService;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->company = Company::factory()->create(['is_active' => true]);
    $this->otherCompany = Company::factory()->create(['is_active' => true]);

    $this->userRole = Role::create([
        'name' => 'user',
        'permissions' => ['view-approval-inbox', 'view-approval-history', 'view-approval-requests'],
    ]);

    $this->approvalManagerRole = Role::create([
        'name' => 'approval-manager',
        'permissions' => ['manage-approvals', 'view-approval-inbox', 'view-approval-history', 'view-approval-requests'],
    ]);

    $this->user = User::factory()->create();
    $this->user->companies()->attach($this->company->id, ['status' => 'active']);
    $this->user->companyRoles()->attach($this->userRole->id, ['company_id' => $this->company->id]);

    $this->approvalManager = User::factory()->create();
    $this->approvalManager->companies()->attach($this->company->id, ['status' => 'active']);
    $this->approvalManager->companyRoles()->attach($this->approvalManagerRole->id, ['company_id' => $this->company->id]);

    app(FeatureService::class)->flush();
});

test('approvals disabled for the active company returns 404 for regular users', function (): void {
    app(SettingService::class)->set('feature_approvals', false, 'boolean', 'feature', $this->company->id);

    $this->actingAs($this->user);
    session(['active_company_id' => $this->company->id]);

    $this->get(route('approvals.inbox'))->assertNotFound();
    $this->get(route('approvals.history'))->assertNotFound();
    $this->get(route('approvals.requests'))->assertNotFound();
});

test('approvals enabled returns 200 for regular users', function (): void {
    $this->actingAs($this->user);
    session(['active_company_id' => $this->company->id]);

    $this->get(route('approvals.inbox'))->assertOk();
});

test('approvals disabled for one company does not affect another company', function (): void {
    $this->user->companies()->attach($this->otherCompany->id, ['status' => 'active']);
    $this->user->companyRoles()->attach($this->userRole->id, ['company_id' => $this->otherCompany->id]);

    app(SettingService::class)->set('feature_approvals', false, 'boolean', 'feature', $this->company->id);

    $this->actingAs($this->user);
    session(['active_company_id' => $this->otherCompany->id]);

    $this->get(route('approvals.inbox'))->assertOk();
});

test('platform admin bypasses a disabled approvals feature', function (): void {
    $admin = User::factory()->platformAdmin()->create();

    app(SettingService::class)->set('feature_approvals', false, 'boolean', 'feature', $this->company->id);

    $this->actingAs($admin);
    session(['active_company_id' => $this->company->id]);

    $this->get(route('approvals.inbox'))->assertOk();
});

test('a manage-approvals permission holder bypasses a disabled approvals feature', function (): void {
    app(SettingService::class)->set('feature_approvals', false, 'boolean', 'feature', $this->company->id);

    $this->actingAs($this->approvalManager);
    session(['active_company_id' => $this->company->id]);

    $this->get(route('approvals.inbox'))->assertOk();
});

test('sidebar hides approvals items when the feature is disabled', function (): void {
    app(SettingService::class)->set('feature_approvals', false, 'boolean', 'feature', $this->company->id);

    $this->actingAs($this->user);
    session(['active_company_id' => $this->company->id]);

    $this->get('/')
        ->assertOk()
        ->assertDontSee('Inbox')
        ->assertDontSee('My Requests');
});

test('sidebar shows approvals items when the feature is enabled', function (): void {
    $this->actingAs($this->user);
    session(['active_company_id' => $this->company->id]);

    $this->get('/')
        ->assertOk()
        ->assertSee('Inbox')
        ->assertSee('My Requests');
});

test('api returns 404 when api access is disabled for the company', function (): void {
    $bot = User::factory()->system()->create();
    $bot->companies()->attach($this->company->id);
    $token = $bot->createToken('System API', ['approvals:index'])->plainTextToken;

    app(SettingService::class)->set('feature_api_access', false, 'boolean', 'feature', $this->company->id);

    $this->withHeaders([
        'Authorization' => 'Bearer '.$token,
        'X-Company-ID' => (string) $this->company->id,
    ])->getJson('/api/v1/approvals')->assertNotFound();
});

test('api allows access when api access is enabled', function (): void {
    $bot = User::factory()->system()->create();
    $bot->companies()->attach($this->company->id);
    $token = $bot->createToken('System API', ['approvals:index'])->plainTextToken;

    $this->withHeaders([
        'Authorization' => 'Bearer '.$token,
        'X-Company-ID' => (string) $this->company->id,
    ])->getJson('/api/v1/approvals')->assertOk();
});

test('api resolves the toggle per company from the X-Company-ID header', function (): void {
    $bot = User::factory()->system()->create();
    $bot->companies()->attach($this->company->id);
    $bot->companies()->attach($this->otherCompany->id);
    $token = $bot->createToken('System API', ['approvals:index'])->plainTextToken;

    app(SettingService::class)->set('feature_api_access', false, 'boolean', 'feature', $this->company->id);

    $this->withHeaders([
        'Authorization' => 'Bearer '.$token,
        'X-Company-ID' => (string) $this->company->id,
    ])->getJson('/api/v1/approvals')->assertNotFound();

    $this->withHeaders([
        'Authorization' => 'Bearer '.$token,
        'X-Company-ID' => (string) $this->otherCompany->id,
    ])->getJson('/api/v1/approvals')->assertOk();
});

test('api platform admin bypasses a disabled api access feature', function (): void {
    $admin = User::factory()->system()->platformAdmin()->create();
    $token = $admin->createToken('System API', ['approvals:index'])->plainTextToken;

    app(SettingService::class)->set('feature_api_access', false, 'boolean', 'feature', $this->company->id);

    $this->withHeaders([
        'Authorization' => 'Bearer '.$token,
        'X-Company-ID' => (string) $this->company->id,
    ])->getJson('/api/v1/approvals')->assertOk();
});
