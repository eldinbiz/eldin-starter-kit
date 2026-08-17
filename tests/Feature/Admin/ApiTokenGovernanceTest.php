<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows platform admin to view api tokens governance page', function () {
    $admin = User::factory()->platformAdmin()->create();

    $this->actingAs($admin)
        ->get(route('admin.api-tokens.index'))
        ->assertOk()
        ->assertSee('System API Tokens');
});

it('prevents regular users from viewing api tokens governance page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.api-tokens.index'))
        ->assertForbidden();
});

it('allows company admin with manage-api-tokens permission to view api tokens governance page', function () {
    $company = Company::factory()->create(['is_active' => true]);
    $role = Role::factory()->create([
        'name' => 'admin',
        'permissions' => ['manage-api-tokens'],
    ]);
    $user = User::factory()->create();
    $user->companies()->attach($company->id, [
        'job_title' => 'API Manager',
        'status' => 'active',
    ]);
    $user->companyRoles()->attach($role->id, [
        'company_id' => $company->id,
    ]);

    session(['active_company_id' => $company->id]);

    $this->actingAs($user)
        ->get(route('admin.api-tokens.index'))
        ->assertOk()
        ->assertSee('System API Tokens');
});

it('prevents company admin without manage-api-tokens permission from viewing api tokens governance page', function () {
    $company = Company::factory()->create(['is_active' => true]);
    $role = Role::factory()->create([
        'name' => 'admin',
        'permissions' => ['manage-users'],
    ]);
    $user = User::factory()->create();
    $user->companies()->attach($company->id, [
        'job_title' => 'Company Admin',
        'status' => 'active',
    ]);
    $user->companyRoles()->attach($role->id, [
        'company_id' => $company->id,
    ]);

    session(['active_company_id' => $company->id]);

    $this->actingAs($user)
        ->get(route('admin.api-tokens.index'))
        ->assertForbidden();
});

it('generates a new api token and creates a system bot', function () {
    $admin = User::factory()->platformAdmin()->create();

    $response = $this->actingAs($admin)
        ->post(route('admin.api-tokens.store'), [
            'name' => 'Zapier Integration',
        ]);

    $response->assertRedirect()
        ->assertSessionHas('flash.api_token');

    $this->assertDatabaseHas('users', [
        'email' => 'bot.zapier-integration@system.local',
        'is_system' => true,
    ]);

    $bot = User::where('email', 'bot.zapier-integration@system.local')->first();
    expect($bot->tokens)->toHaveCount(1);
    expect($bot->tokens->first()->name)->toBe('Zapier Integration');
});

it('revokes an api token successfully', function () {
    $admin = User::factory()->platformAdmin()->create();
    $bot = User::factory()->system()->create();
    $token = $bot->createToken('Test Token');

    $this->assertDatabaseHas('personal_access_tokens', [
        'id' => $token->accessToken->id,
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.api-tokens.destroy', $token->accessToken->id))
        ->assertRedirect()
        ->assertSessionHas('flash.message', 'API Token revoked successfully.');

    $this->assertDatabaseMissing('personal_access_tokens', [
        'id' => $token->accessToken->id,
    ]);
});

it('generates a token with custom expiration and granular abilities', function () {
    $admin = User::factory()->platformAdmin()->create();

    $response = $this->actingAs($admin)
        ->post(route('admin.api-tokens.store'), [
            'name' => 'Approvals Webhook',
            'expiry' => '30',
            'abilities' => ['approvals:index', 'approvals:show'],
        ]);

    $response->assertRedirect()
        ->assertSessionHas('flash.api_token');

    $bot = User::where('email', 'bot.approvals-webhook@system.local')->first();
    expect($bot->tokens)->toHaveCount(1);

    $token = $bot->tokens->first();
    expect($token->abilities)->toEqual(['approvals:index', 'approvals:show']);
    expect($token->expires_at)->not->toBeNull();
    expect($token->expires_at->isFuture())->toBeTrue();
});

it('enforces token abilities on api routes allowing authorized abilities and denying unauthorized abilities', function () {
    $bot = User::factory()->system()->create();

    // Create token with only 'notifications:index' ability
    $plainToken = $bot->createToken('Limited Token', ['notifications:index'])->plainTextToken;

    // Allowed endpoint
    $this->withHeader('Authorization', 'Bearer '.$plainToken)
        ->getJson('/api/v1/notifications')
        ->assertOk();

    // Unauthorized endpoint (requires 'auth:me')
    $this->withHeader('Authorization', 'Bearer '.$plainToken)
        ->getJson('/api/v1/me')
        ->assertForbidden();
});

it('rejects api requests with expired tokens', function () {
    $bot = User::factory()->system()->create();

    // Create already expired token
    $plainToken = $bot->createToken('Expired Token', ['*'], now()->subHour())->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$plainToken)
        ->getJson('/api/v1/me')
        ->assertUnauthorized();
});

it('filters and searches api tokens in datatable', function () {
    $admin = User::factory()->platformAdmin()->create();
    $bot = User::factory()->system()->create(['name' => 'Custom Bot', 'email' => 'bot.custom@system.local']);

    $activeToken = $bot->createToken('Active Sync Token', ['*'], now()->addDays(30));
    $expiredToken = $bot->createToken('Old Expired Token', ['*'], now()->subDay());

    // Search by token name
    $this->actingAs($admin)
        ->get(route('admin.api-tokens.index', ['search' => 'Sync']))
        ->assertOk()
        ->assertSee('Active Sync Token')
        ->assertDontSee('Old Expired Token');

    // Filter by expired status
    $this->actingAs($admin)
        ->get(route('admin.api-tokens.index', ['status' => 'expired']))
        ->assertOk()
        ->assertSee('Old Expired Token')
        ->assertDontSee('Active Sync Token');
});
