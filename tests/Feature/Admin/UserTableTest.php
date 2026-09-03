<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create(['name' => 'Acme Corp', 'is_active' => true]);
    $this->otherCompany = Company::factory()->create(['name' => 'Other Corp', 'is_active' => true]);

    $this->adminRole = Role::firstOrCreate(['name' => 'admin', 'label' => 'Administrator']);
    $this->userRole = Role::firstOrCreate(['name' => 'user', 'label' => 'Standard User']);

    $this->admin = User::factory()->create(['name' => 'Admin User', 'email' => 'admin@acme.com']);
    $this->admin->companies()->attach($this->company->id, ['status' => 'active']);
    $this->admin->companyRoles()->attach($this->adminRole->id, ['company_id' => $this->company->id]);

    $this->userA = User::factory()->create(['name' => 'Alice Johnson', 'email' => 'alice@acme.com']);
    $this->userA->companies()->attach($this->company->id, ['status' => 'active']);
    $this->userA->companyRoles()->attach($this->userRole->id, ['company_id' => $this->company->id]);

    $this->userB = User::factory()->create(['name' => 'Bob Smith', 'email' => 'bob@other.com']);
    $this->userB->companies()->attach($this->otherCompany->id, ['status' => 'active']);
    $this->userB->companyRoles()->attach($this->userRole->id, ['company_id' => $this->otherCompany->id]);
});

it('renders user table component with user list', function () {
    $this->actingAs($this->admin);
    session(['active_company_id' => $this->company->id]);

    Livewire::test('admin.users.user-table')
        ->assertSuccessful()
        ->assertSee('Admin User')
        ->assertSee('Alice Johnson');
});

it('filters users by search query reactively', function () {
    $this->actingAs($this->admin);
    session(['active_company_id' => $this->company->id]);

    Livewire::test('admin.users.user-table')
        ->set('search', 'Alice')
        ->assertSee('Alice Johnson')
        ->assertDontSee('Admin User');
});

it('toggles column sorting direction', function () {
    $this->actingAs($this->admin);
    session(['active_company_id' => $this->company->id]);

    Livewire::test('admin.users.user-table')
        ->set('sortField', 'name')
        ->set('sortDirection', 'asc')
        ->call('sortBy', 'name')
        ->assertSet('sortDirection', 'desc')
        ->call('sortBy', 'created_at')
        ->assertSet('sortField', 'created_at')
        ->assertSet('sortDirection', 'asc');
});

it('filters users by role selection', function () {
    $this->actingAs($this->admin);
    session(['active_company_id' => $this->company->id]);

    Livewire::test('admin.users.user-table')
        ->set('filterValues.roles', ['admin'])
        ->assertSee('Admin User')
        ->assertDontSee('Alice Johnson');
});

it('resets all active filters', function () {
    $this->actingAs($this->admin);
    session(['active_company_id' => $this->company->id]);

    Livewire::test('admin.users.user-table')
        ->set('search', 'Alice')
        ->set('filterValues.roles', ['user'])
        ->call('resetFilters')
        ->assertSet('search', '')
        ->assertSet('filterValues', ['roles' => []]);
});

it('initializes roles filter as an array and handles checking multiple roles independently', function () {
    $this->actingAs($this->admin);
    session(['active_company_id' => $this->company->id]);

    $component = Livewire::test('admin.users.user-table')
        ->assertSet('filterValues.roles', []);

    // Check single role
    $component->set('filterValues.roles', ['admin'])
        ->assertSet('filterValues.roles', ['admin'])
        ->assertSee('Admin User')
        ->assertDontSee('Alice Johnson');

    // Check second role
    $component->set('filterValues.roles', ['admin', 'user'])
        ->assertSet('filterValues.roles', ['admin', 'user'])
        ->assertSee('Admin User')
        ->assertSee('Alice Johnson');

    // Uncheck first role
    $component->set('filterValues.roles', ['user'])
        ->assertSet('filterValues.roles', ['user'])
        ->assertSee('Alice Johnson')
        ->assertDontSee('Admin User');
});

it('prompts delete modal and deletes user', function () {
    $this->actingAs($this->admin);
    session(['active_company_id' => $this->company->id]);

    Livewire::test('admin.users.user-table')
        ->call('promptDelete', $this->userA->id)
        ->assertSet('userToDeleteId', $this->userA->id)
        ->assertSet('userToDeleteName', 'Alice Johnson')
        ->call('deleteUser')
        ->assertSet('userToDeleteId', null)
        ->assertSee('deleted successfully');

    expect(User::find($this->userA->id))->toBeNull();
});

it('prevents admin from deleting themselves', function () {
    $this->actingAs($this->admin);
    session(['active_company_id' => $this->company->id]);

    Livewire::test('admin.users.user-table')
        ->call('promptDelete', $this->admin->id)
        ->call('deleteUser')
        ->assertSee('You cannot delete your own account');

    expect(User::find($this->admin->id))->not->toBeNull();
});
