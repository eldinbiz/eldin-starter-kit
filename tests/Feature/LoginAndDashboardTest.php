<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows the login page', function () {
    $this->get(route('login'))
        ->assertSuccessful();
});

it('allows a user to login and access the dashboard', function () {
    $user = User::factory()->create([
        'password' => bcrypt('password'),
    ]);

    // Prime the session by visiting the login page first
    $this->get('/login');

    $response = $this->post('/login', [
        '_token' => session()->token(),
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect('/');

    $this->assertAuthenticatedAs($user);
});

it('rejects invalid credentials', function () {
    $user = User::factory()->create();

    // Prime the session by visiting the login page first
    $this->get('/login');

    $response = $this->post('/login', [
        '_token' => session()->token(),
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors('email');

    $this->assertGuest();
});
