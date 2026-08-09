<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns the authenticated user profile', function () {
    $user = User::factory()->system()->create([
        'name' => 'API Bot',
    ]);
    
    $token = $user->createToken('System API')->plainTextToken;

    $response = $this->getJson('/api/v1/me', [
        'Authorization' => 'Bearer ' . $token,
    ]);

    $response->assertOk()
        ->assertJson([
            'data' => [
                'name' => 'API Bot',
                'is_system' => true,
            ]
        ]);
});

it('rejects unauthenticated requests', function () {
    $response = $this->getJson('/api/v1/me');

    $response->assertStatus(401);
});
