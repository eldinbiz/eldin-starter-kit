<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire\Admin\ApiTokens;

use App\Actions\ApiTokens\CreateApiTokenAction;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create(['name' => 'Acme Corp', 'is_active' => true]);

    $this->adminRole = Role::firstOrCreate(['name' => 'admin'], ['label' => 'Administrator', 'permissions' => ['*'], 'is_active' => true]);
    $this->adminRole->update(['permissions' => ['*'], 'is_active' => true]);
    $this->admin = User::factory()->create(['name' => 'Admin User', 'email' => 'admin@acme.com']);
    $this->admin->companies()->attach($this->company->id, ['status' => 'active']);
    $this->admin->companyRoles()->attach($this->adminRole->id, ['company_id' => $this->company->id]);
});

it('renders api token table with list of tokens', function () {
    $this->actingAs($this->admin);
    session(['active_company_id' => $this->company->id]);

    $action = app(CreateApiTokenAction::class);
    $action->execute(['name' => 'Zapier Sync', 'expiry' => '90', 'abilities' => ['auth:me']], $this->admin);

    Livewire::test('admin.api-tokens.api-token-table')
        ->assertSuccessful()
        ->assertSee('System API Tokens')
        ->assertSee('Zapier Sync');
});

it('filters tokens by search query', function () {
    $this->actingAs($this->admin);
    session(['active_company_id' => $this->company->id]);

    $action = app(CreateApiTokenAction::class);
    $action->execute(['name' => 'Zapier Sync', 'expiry' => '90'], $this->admin);
    $action->execute(['name' => 'Billing Webhook', 'expiry' => '90'], $this->admin);

    Livewire::test('admin.api-tokens.api-token-table')
        ->set('search', 'Zapier')
        ->assertSee('Zapier Sync')
        ->assertDontSee('Billing Webhook');
});

it('generates a new api token and reveals the secret token', function () {
    $this->actingAs($this->admin);
    session(['active_company_id' => $this->company->id]);

    Livewire::test('admin.api-tokens.api-token-table')
        ->call('openCreateModal')
        ->assertSet('showCreateModal', true)
        ->set('tokenName', 'Test Integration')
        ->set('tokenExpiry', '60')
        ->call('generateToken')
        ->assertSet('showCreateModal', false)
        ->assertSee('API Token Generated: Test Integration')
        ->assertSee('Token Generated Successfully', false);

    $token = PersonalAccessToken::where('name', 'Test Integration')->first();
    expect($token)->not->toBeNull()
        ->and($token->tokenable->is_system)->toBeTrue();
});

it('inspects token scopes in modal', function () {
    $this->actingAs($this->admin);
    session(['active_company_id' => $this->company->id]);

    $action = app(CreateApiTokenAction::class);
    $newToken = $action->execute([
        'name' => 'Restricted Service',
        'expiry' => '90',
        'abilities' => ['auth:me', 'users:index'],
    ], $this->admin);

    Livewire::test('admin.api-tokens.api-token-table')
        ->call('inspectScopes', $newToken->accessToken->id)
        ->assertSet('showScopesModal', true)
        ->assertSet('inspectingTokenName', 'Restricted Service')
        ->assertSee('/api/v1/me')
        ->assertSee('/api/v1/users');
});

it('prompts and revokes an api token', function () {
    $this->actingAs($this->admin);
    session(['active_company_id' => $this->company->id]);

    $action = app(CreateApiTokenAction::class);
    $newToken = $action->execute(['name' => 'Temporary Token', 'expiry' => '30'], $this->admin);
    $tokenId = $newToken->accessToken->id;

    Livewire::test('admin.api-tokens.api-token-table')
        ->call('promptRevoke', $tokenId)
        ->assertSet('tokenToRevokeId', $tokenId)
        ->call('revokeToken')
        ->assertSet('tokenToRevokeId', null)
        ->assertSee('revoked successfully');

    expect(PersonalAccessToken::find($tokenId))->toBeNull();
});
