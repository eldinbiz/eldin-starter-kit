<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can log in successfully through the 2-step authentication flow', function (): void {
    $company = Company::factory()->create([
        'name' => 'Acme Corporation',
        'is_active' => true,
    ]);

    $user = User::factory()->create([
        'email' => 'jane@example.com',
        'password' => 'password',
        'auth_method' => 'local',
    ]);

    $user->companies()->attach($company->id, [
        'status' => 'active',
        'job_title' => 'Staff Engineer',
    ]);

    // Step 1: Visit login page and submit email
    visit('/login')
        ->assertSee('Sign In')
        ->type('#email', $user->email)
        ->click('@next-button')
        // Step 2: Enter password (auto-waited by Playwright) and submit
        ->type('#password', 'password')
        ->click('@login-button')
        // Verify dashboard reached
        ->assertPathIs('/')
        ->wait(3)
        ->assertSee('Dashboard');
})->group('browser');

test('user sees error alert when submitting invalid credentials', function (): void {
    $company = Company::factory()->create(['is_active' => true]);

    $user = User::factory()->create([
        'email' => 'jane@example.com',
        'password' => 'password',
        'auth_method' => 'local',
    ]);

    $user->companies()->attach($company->id, [
        'status' => 'active',
        'job_title' => 'Staff Engineer',
    ]);

    visit('/login')
        ->assertSee('Sign In')
        ->type('#email', $user->email)
        ->click('@next-button')
        ->type('#password', 'wrong-password')
        ->click('@login-button')
        ->assertPathIs('/login')
        ->assertSee('The provided credentials do not match our records.');
})->group('browser');

test('user sees warning alert when authentication provider is disabled', function (): void {
    Company::factory()->create(['is_active' => true]);

    $user = User::factory()->create([
        'email' => 'ldap_user@example.com',
        'auth_method' => 'ldap',
    ]);

    visit('/login')
        ->assertSee('Sign In')
        ->type('#email', $user->email)
        ->click('@next-button')
        ->assertSee('is currently disabled by system administrator');
})->group('browser');
