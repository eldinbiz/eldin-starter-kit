<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire\Admin\Roles;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create(['name' => 'Acme Corp', 'is_active' => true]);

    $this->adminRole = Role::firstOrCreate(['name' => 'admin', 'label' => 'Administrator', 'is_active' => true]);
    $this->userRole = Role::firstOrCreate(['name' => 'user', 'label' => 'Standard User', 'is_active' => true]);
    $this->managerRole = Role::factory()->create(['name' => 'manager', 'is_active' => true]);

    $this->admin = User::factory()->create(['name' => 'Admin User', 'email' => 'admin@acme.com']);
    $this->admin->companies()->attach($this->company->id, ['status' => 'active']);
    $this->admin->companyRoles()->attach($this->adminRole->id, ['company_id' => $this->company->id]);
});

it('renders role table component with roles list', function () {
    $this->actingAs($this->admin);
    session(['active_company_id' => $this->company->id]);

    Livewire::test('admin.roles.role-table')
        ->assertSuccessful()
        ->assertSee('Admin')
        ->assertSee('Manager');
});

it('filters roles by search query reactively', function () {
    $this->actingAs($this->admin);
    session(['active_company_id' => $this->company->id]);

    Livewire::test('admin.roles.role-table')
        ->set('search', 'manager')
        ->assertSee('Manager')
        ->assertDontSee('Admin');
});

it('toggles column sorting direction', function () {
    $this->actingAs($this->admin);
    session(['active_company_id' => $this->company->id]);

    Livewire::test('admin.roles.role-table')
        ->set('sortField', 'name')
        ->set('sortDirection', 'asc')
        ->call('sortBy', 'name')
        ->assertSet('sortDirection', 'desc')
        ->call('sortBy', 'created_at')
        ->assertSet('sortField', 'created_at')
        ->assertSet('sortDirection', 'asc');
});

it('filters roles by status', function () {
    $this->actingAs($this->admin);
    session(['active_company_id' => $this->company->id]);

    $disabledRole = Role::factory()->create(['name' => 'auditor', 'is_active' => false]);

    Livewire::test('admin.roles.role-table')
        ->set('filterValues.status', 'disabled')
        ->assertSee('Auditor')
        ->assertDontSee('Manager');
});

it('filters roles by trashed filter', function () {
    $this->actingAs($this->admin);
    session(['active_company_id' => $this->company->id]);

    $deletedRole = Role::factory()->create(['name' => 'archived-role', 'is_active' => false]);
    $deletedRole->delete();

    Livewire::test('admin.roles.role-table')
        ->set('filterValues.trashed', '1')
        ->assertSee('Archived role')
        ->assertDontSee('Manager');
});

it('disables an active role and updates status', function () {
    $this->actingAs($this->admin);
    session(['active_company_id' => $this->company->id]);

    $customRole = Role::factory()->create(['name' => 'editor', 'is_active' => true]);

    Livewire::test('admin.roles.role-table')
        ->call('toggleStatus', $customRole->id)
        ->assertSee('has been disabled');

    $customRole->refresh();
    expect($customRole->is_active)->toBeFalse();
});

it('prevents disabling the default user role', function () {
    $this->actingAs($this->admin);
    session(['active_company_id' => $this->company->id]);

    Livewire::test('admin.roles.role-table')
        ->call('toggleStatus', $this->userRole->id)
        ->assertSee('The "user" role cannot be disabled');

    $this->userRole->refresh();
    expect($this->userRole->is_active)->toBeTrue();
});

it('enables a disabled role and updates status', function () {
    $this->actingAs($this->admin);
    session(['active_company_id' => $this->company->id]);

    $disabledRole = Role::factory()->create(['name' => 'supervisor', 'is_active' => false]);

    Livewire::test('admin.roles.role-table')
        ->call('toggleStatus', $disabledRole->id)
        ->assertSee('has been enabled');

    $disabledRole->refresh();
    expect($disabledRole->is_active)->toBeTrue();
});

it('restores a soft-deleted role', function () {
    $this->actingAs($this->admin);
    session(['active_company_id' => $this->company->id]);

    $trashedRole = Role::factory()->create(['name' => 'trashed-custom', 'is_active' => false]);
    $trashedRole->delete();

    Livewire::test('admin.roles.role-table')
        ->call('restoreRole', $trashedRole->id)
        ->assertSee('has been restored');

    $trashedRole->refresh();
    expect($trashedRole->trashed())->toBeFalse()
        ->and($trashedRole->is_active)->toBeTrue();
});

it('prompts delete modal and soft-deletes a disabled role with type-to-confirm', function () {
    $this->actingAs($this->admin);
    session(['active_company_id' => $this->company->id]);

    $roleToDelete = Role::factory()->create(['name' => 'obsolete-role', 'is_active' => false]);

    Livewire::test('admin.roles.role-table')
        ->call('promptDelete', $roleToDelete->id)
        ->assertSet('roleToDeleteId', $roleToDelete->id)
        ->assertSet('roleToDeleteName', 'obsolete-role')
        ->set('deleteConfirmTypedName', 'obsolete-role')
        ->call('deleteRole')
        ->assertSet('roleToDeleteId', null)
        ->assertSee('deleted');

    expect(Role::find($roleToDelete->id))->toBeNull();
    expect(Role::withTrashed()->find($roleToDelete->id))->not->toBeNull();
});

it('prevents promptDelete and deletion of system roles', function () {
    $this->actingAs($this->admin);
    session(['active_company_id' => $this->company->id]);

    $sysadminRole = Role::factory()->sysadmin()->create();

    Livewire::test('admin.roles.role-table')
        ->call('promptDelete', $sysadminRole->id)
        ->assertSet('roleToDeleteId', null);
});
