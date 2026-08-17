<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('renders organization chart without javascript errors and displays employee nodes', function (): void {
    $company = Company::factory()->create(['name' => 'Acme Global', 'is_active' => true]);

    $adminRole = Role::factory()->admin()->create();

    $manager = User::factory()->create([
        'name' => 'Sarah Chief',
        'email' => 'sarah@example.com',
        'password' => bcrypt('password'),
    ]);
    $employee = User::factory()->create([
        'name' => 'John Developer',
        'email' => 'john@example.com',
        'password' => bcrypt('password'),
    ]);

    $company->users()->attach($manager->id, [
        'status' => 'active',
        'job_title' => 'Chief Executive',
        'manager_id' => null,
    ]);
    $company->users()->attach($employee->id, [
        'status' => 'active',
        'job_title' => 'Software Engineer',
        'manager_id' => $manager->id,
    ]);

    $manager->companyRoles()->attach($adminRole->id, ['company_id' => $company->id]);

    visit('/login')
        ->type('email', $manager->email)
        ->type('password', 'password')
        ->click('Log in')
        ->assertPathIs('/')
        ->visit('/admin/org-chart')
        ->assertPathIs('/admin/org-chart')
        ->assertSee('Organization Chart')
        ->waitFor('#org-chart-container')
        ->assertSee('Sarah Chief')
        ->assertSee('John Developer');
})->group('browser');
