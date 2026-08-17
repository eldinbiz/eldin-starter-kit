<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the api documentation interface', function () {
    $admin = User::factory()->platformAdmin()->create();

    $response = $this->actingAs($admin)->get('/docs/api');

    $response->assertOk();
});

it('serves openapi json specification matching api v1 endpoints', function () {
    $admin = User::factory()->platformAdmin()->create();

    $response = $this->actingAs($admin)->get('/docs/api.json');

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/json');

    $data = $response->json();

    expect($data)->toHaveKey('openapi')
        ->and($data)->toHaveKey('info')
        ->and($data)->toHaveKey('paths')
        ->and($data['paths'])->not->toBeEmpty();

    // Verify key v1 endpoints are documented in the specification
    $paths = array_keys($data['paths']);
    $expectedEndpoints = [
        'me',
        'notifications',
        'users',
        'roles',
        'companies',
        'approvals',
        'purchases',
    ];

    foreach ($expectedEndpoints as $endpoint) {
        $found = false;
        foreach ($paths as $path) {
            if (str_contains($path, $endpoint)) {
                $found = true;
                break;
            }
        }
        expect($found)->toBeTrue("Expected endpoint matching '{$endpoint}' in OpenAPI paths.");
    }
});
