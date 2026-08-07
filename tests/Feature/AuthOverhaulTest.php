<?php

declare(strict_types=1);

use App\Models\AuthProviderSetting;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create(['name' => 'Main Company']);
    $this->platformAdminRole = Role::factory()->create([
        'name' => 'platform-admin',
        'is_active' => true,
        'permissions' => ['manage-auth-providers'],
    ]);
    $this->userRole = Role::factory()->create([
        'name' => 'user',
        'is_active' => true,
    ]);

    $this->admin = User::factory()->create([
        'email' => 'admin@example.com',
        'auth_method' => 'local',
    ]);
    $this->admin->companies()->attach($this->company->id);
    $this->admin->companyRoles()->attach($this->platformAdminRole->id, ['company_id' => $this->company->id]);

    session(['active_company_id' => $this->company->id]);
});

it('defaults user auth_method to local', function () {
    $user = User::factory()->create();

    expect($user->auth_method)->toBe('local');
});

it('allows user with manage-auth-providers permission to view auth provider settings page', function () {
    $roleWithPermission = Role::factory()->create([
        'name' => 'admin',
        'is_active' => true,
        'permissions' => ['manage-auth-providers'],
    ]);

    $user = User::factory()->create();
    $user->companies()->attach($this->company->id);
    $user->companyRoles()->attach($roleWithPermission->id, ['company_id' => $this->company->id]);
    session(['active_company_id' => $this->company->id]);

    $response = $this->actingAs($user)->get(route('admin.settings.auth-providers.index'));

    $response->assertSuccessful()
        ->assertSee('Authentication Methods Configuration')
        ->assertSee('Local')
        ->assertSee('LDAP')
        ->assertSee('Google')
        ->assertSee('Microsoft')
        ->assertSee('Auth0')
        ->assertSee('SAML 2.0');
});

it('denies user without manage-auth-providers permission from accessing auth provider settings', function () {
    $regularUser = User::factory()->create();
    $regularUser->companies()->attach($this->company->id);
    $regularUser->companyRoles()->attach($this->userRole->id, ['company_id' => $this->company->id]);
    session(['active_company_id' => $this->company->id]);

    $response = $this->actingAs($regularUser)->get(route('admin.settings.auth-providers.index'));

    $response->assertForbidden();
});

it('allows platform admin to update provider settings and auto-provisioning toggle', function () {
    $response = $this->actingAs($this->admin)->put(route('admin.settings.auth-providers.update', 'google'), [
        'is_enabled' => '1',
        'allow_auto_provisioning' => '1',
        'settings' => [
            'client_id' => 'test-google-id',
            'client_secret' => 'test-google-secret',
        ],
    ]);

    $response->assertRedirect(route('admin.settings.auth-providers.index', ['tab' => 'google']));

    $setting = AuthProviderSetting::forProvider('google');

    expect($setting)->not->toBeNull()
        ->and($setting->is_enabled)->toBeTrue()
        ->and($setting->allow_auto_provisioning)->toBeTrue()
        ->and($setting->settings['client_id'])->toBe('test-google-id');
});

it('resolves user auth_method via login lookup endpoint', function () {
    $user = User::factory()->create([
        'email' => 'john@example.com',
        'auth_method' => 'google',
    ]);

    AuthProviderSetting::create([
        'provider' => 'google',
        'is_enabled' => true,
        'allow_auto_provisioning' => false,
    ]);

    $response = $this->postJson(route('login.lookup'), [
        'email' => 'john@example.com',
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'found' => true,
            'email' => 'john@example.com',
            'auth_method' => 'google',
            'is_enabled' => true,
        ]);
});

it('allows platform admin to create user with specified auth_method', function () {
    session(['active_company_id' => $this->company->id]);

    $response = $this->actingAs($this->admin)->post(route('admin.users.store'), [
        'name' => 'Alice SSO',
        'email' => 'alice@example.com',
        'password' => 'password123',
        'auth_method' => 'saml2',
        'roles' => [$this->userRole->id],
    ]);

    $response->assertRedirect(route('admin.users.index'));

    $user = User::where('email', 'alice@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->auth_method)->toBe('saml2');
});

it('handles SAML 2.0 Socialite callback auto-provisioning when enabled', function () {
    AuthProviderSetting::create([
        'provider' => 'saml2',
        'is_enabled' => true,
        'allow_auto_provisioning' => true,
    ]);

    $abstractUser = Mockery::mock('Laravel\Socialite\Two\User');
    $abstractUser->shouldReceive('getId')->andReturn('saml-id-123');
    $abstractUser->shouldReceive('getName')->andReturn('SAML User');
    $abstractUser->shouldReceive('getEmail')->andReturn('samluser@example.com');

    $provider = Mockery::mock('Laravel\Socialite\Contracts\Provider');
    $provider->shouldReceive('user')->andReturn($abstractUser);

    Laravel\Socialite\Facades\Socialite::shouldReceive('driver')->with('saml2')->andReturn($provider);

    $response = $this->get(route('auth.callback', ['provider' => 'saml2']));

    $response->assertRedirect('/');

    $user = User::where('email', 'samluser@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->auth_method)->toBe('saml2')
        ->and(auth()->check())->toBeTrue();
});
