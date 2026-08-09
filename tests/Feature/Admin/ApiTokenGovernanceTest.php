<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;

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
