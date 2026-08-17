<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;

uses(RefreshDatabase::class);

it('requires x-company-id header for company scoped api routes', function () {
    $user = User::factory()->system()->create();
    $token = $user->createToken('System API')->plainTextToken;

    $response = $this->getJson('/api/v1/approvals', [
        'Authorization' => 'Bearer '.$token,
    ]);

    $response->assertStatus(400)
        ->assertJson(['message' => 'The X-Company-ID header is required for this endpoint.']);
});

it('denies access if user is not a member of the requested company', function () {
    $user = User::factory()->system()->create();
    $company = Company::factory()->create();
    $token = $user->createToken('System API')->plainTextToken;

    // User is NOT attached to the company and NOT a platform admin

    $response = $this->getJson('/api/v1/approvals', [
        'Authorization' => 'Bearer '.$token,
        'X-Company-ID' => (string) $company->id,
    ]);

    $response->assertStatus(403)
        ->assertJson(['message' => "User does not have access to company {$company->id}."]);
});

it('allows access if user is a member of the requested company', function () {
    $user = User::factory()->system()->create();
    $company = Company::factory()->create();
    $user->companies()->attach($company->id);

    $token = $user->createToken('System API')->plainTextToken;

    $response = $this->getJson('/api/v1/approvals', [
        'Authorization' => 'Bearer '.$token,
        'X-Company-ID' => (string) $company->id,
    ]);

    $response->assertOk();
    expect(App::get('active_company_id'))->toBe($company->id);
});

it('allows access to any company if user is a platform admin', function () {
    $user = User::factory()->system()->platformAdmin()->create();
    $company = Company::factory()->create();

    $token = $user->createToken('System API')->plainTextToken;

    $response = $this->getJson('/api/v1/approvals', [
        'Authorization' => 'Bearer '.$token,
        'X-Company-ID' => (string) $company->id,
    ]);

    $response->assertOk();
    expect(App::get('active_company_id'))->toBe($company->id);
});
