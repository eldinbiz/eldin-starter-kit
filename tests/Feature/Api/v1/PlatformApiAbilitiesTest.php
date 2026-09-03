<?php

declare(strict_types=1);

namespace Tests\Feature\Api\v1;

use App\Actions\ApiTokens\CreateApiTokenAction;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows token with users:index ability to list users without platform-admin role', function () {
    $company = Company::factory()->create();
    $bot = User::factory()->system()->create();
    $bot->companies()->attach($company->id, ['status' => 'active']);

    // Bot has NO web role, but token has 'users:index' ability
    $token = $bot->createToken('User Directory Token', ['users:index'])->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/users');

    $response->assertOk();
});

it('denies token without users:index ability from listing users', function () {
    $company = Company::factory()->create();
    $bot = User::factory()->system()->create();
    $bot->companies()->attach($company->id, ['status' => 'active']);

    // Token has only 'notifications:index' ability
    $token = $bot->createToken('Limited Token', ['notifications:index'])->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/users');

    $response->assertForbidden();
});

it('creates an api token without assigning a platform-admin wildcard role', function () {
    $admin = User::factory()->sysadmin()->create();
    $company = $admin->getActiveCompany();

    $action = app(CreateApiTokenAction::class);
    $token = $action->execute([
        'name' => 'Accounting Sync',
        'expiry' => '30',
        'abilities' => ['notifications:index'],
    ], $admin);

    $bot = User::where('email', 'bot.accounting-sync@system.local')->first();
    expect($bot)->not->toBeNull()
        ->and($bot->is_system)->toBeTrue()
        ->and($bot->roles)->toBeEmpty()
        ->and($bot->hasPlatformAccess())->toBeFalse();

    // Ensure platform-admin role with wildcard was NOT created
    $platformRole = Role::where('name', 'platform-admin')->first();
    expect($platformRole)->toBeNull();
});

it('enforces tenant isolation so bot cannot access other companies via x-company-id', function () {
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    $bot = User::factory()->system()->create();
    $bot->companies()->attach($companyA->id, ['status' => 'active']);

    $token = $bot->createToken('Company A Token', ['approvals:index'])->plainTextToken;

    // Allowed for Company A
    $this->withHeader('Authorization', 'Bearer '.$token)
        ->withHeader('X-Company-ID', (string) $companyA->id)
        ->getJson('/api/v1/approvals')
        ->assertOk();

    // Denied for Company B (403 Forbidden)
    $this->withHeader('Authorization', 'Bearer '.$token)
        ->withHeader('X-Company-ID', (string) $companyB->id)
        ->getJson('/api/v1/approvals')
        ->assertForbidden();
});
