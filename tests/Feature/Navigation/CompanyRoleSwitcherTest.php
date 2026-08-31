<?php

declare(strict_types=1);

namespace Tests\Feature\Navigation;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->companyA = Company::factory()->create(['name' => 'Company Alpha']);
    $this->companyB = Company::factory()->create(['name' => 'Company Beta']);
    $this->unauthorizedCompany = Company::factory()->create(['name' => 'Secret Corp']);

    $this->adminRole = Role::firstOrCreate(['name' => 'admin', 'label' => 'Administrator']);
    $this->userRole = Role::firstOrCreate(['name' => 'user', 'label' => 'Standard User']);

    $this->user = User::factory()->create();

    // User is member of Company A and Company B
    $this->user->companies()->attach([$this->companyA->id, $this->companyB->id]);

    // In Company A, user has both admin and user roles
    $this->user->companyRoles()->attach($this->adminRole->id, ['company_id' => $this->companyA->id]);
    $this->user->companyRoles()->attach($this->userRole->id, ['company_id' => $this->companyA->id]);

    // In Company B, user has only user role
    $this->user->companyRoles()->attach($this->userRole->id, ['company_id' => $this->companyB->id]);
});

it('renders company and role options in the switcher component', function () {
    $this->actingAs($this->user);
    session(['active_company_id' => $this->companyA->id]);

    Livewire::test('navigation.company-role-switcher')
        ->assertSuccessful()
        ->call('toggleCompanyDropdown')
        ->assertSee('Company Alpha')
        ->assertSee('Company Beta')
        ->assertDontSee('Secret Corp')
        ->call('toggleRoleDropdown')
        ->assertSee('Admin')
        ->assertSee('User');
});

it('switches the active company successfully via Livewire action', function () {
    $this->actingAs($this->user);
    session(['active_company_id' => $this->companyA->id]);

    Livewire::test('navigation.company-role-switcher')
        ->call('switchCompany', $this->companyB->id)
        ->assertRedirect(route('dashboard'));

    expect(session('active_company_id'))->toBe($this->companyB->id);
});

it('rejects switching to an unauthorized company', function () {
    $this->actingAs($this->user);
    session(['active_company_id' => $this->companyA->id]);

    Livewire::test('navigation.company-role-switcher')
        ->call('switchCompany', $this->unauthorizedCompany->id)
        ->assertForbidden();
});

it('switches the active role successfully via Livewire action', function () {
    $this->actingAs($this->user);
    session(['active_company_id' => $this->companyA->id]);

    Livewire::test('navigation.company-role-switcher')
        ->call('switchRole', 'admin')
        ->assertRedirect(route('dashboard'));

    expect(session("active_role.{$this->companyA->id}"))->toBe('admin');
});
