<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire\Admin\Roles;

use App\Livewire\Admin\Roles\RoleEditor;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create(['name' => 'Acme Corp', 'is_active' => true]);

    $this->adminRole = Role::firstOrCreate(['name' => 'admin'], ['label' => 'Administrator', 'permissions' => ['*'], 'is_active' => true]);
    $this->adminRole->update(['permissions' => ['*'], 'is_active' => true]);
    $this->userRole = Role::firstOrCreate(['name' => 'user'], ['label' => 'Standard User', 'is_active' => true]);

    $this->admin = User::factory()->create(['name' => 'Admin User', 'email' => 'admin@acme.com']);
    $this->admin->companies()->attach($this->company->id, ['status' => 'active']);
    $this->admin->companyRoles()->attach($this->adminRole->id, ['company_id' => $this->company->id]);
});

it('renders create role form with permission groups', function () {
    $this->actingAs($this->admin);
    session(['active_company_id' => $this->company->id]);

    Livewire::test(RoleEditor::class)
        ->assertSuccessful()
        ->assertSee('Create New Role')
        ->assertSee('Permissions Matrix')
        ->assertSee('User Management')
        ->assertSee('System Administration');
});

it('renders edit role form with existing role attributes', function () {
    $this->actingAs($this->admin);
    session(['active_company_id' => $this->company->id]);

    $role = Role::factory()->create([
        'name' => 'moderator',
        'permissions' => ['manage-users'],
    ]);

    Livewire::test(RoleEditor::class, ['roleId' => $role->id])
        ->assertSuccessful()
        ->assertSee('Edit Role: Moderator')
        ->assertSet('name', 'moderator')
        ->assertSee('manage-users');
});

it('toggles permission group selection', function () {
    $this->actingAs($this->admin);
    session(['active_company_id' => $this->company->id]);

    $component = Livewire::test(RoleEditor::class)
        ->call('toggleGroup', 'User Management');

    // Should contain manage-users
    expect($component->get('selectedPermissions'))->toContain('manage-users');

    // Deselect group
    $component->call('toggleGroup', 'User Management');
    expect($component->get('selectedPermissions'))->not->toContain('manage-users');
});

it('always keeps dashboard permission selected', function () {
    $this->actingAs($this->admin);
    session(['active_company_id' => $this->company->id]);

    $component = Livewire::test(RoleEditor::class);
    expect($component->get('selectedPermissions'))->toContain('view-dashboard');

    // Attempt to toggle off always_enabled
    $component->call('togglePermission', 'view-dashboard');
    expect($component->get('selectedPermissions'))->toContain('view-dashboard');
});

it('validates name uniqueness and required field on role creation', function () {
    $this->actingAs($this->admin);
    session(['active_company_id' => $this->company->id]);

    Role::factory()->create(['name' => 'existing-role']);

    Livewire::test(RoleEditor::class)
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required']);

    Livewire::test(RoleEditor::class)
        ->set('name', 'existing-role')
        ->call('save')
        ->assertHasErrors(['name' => 'unique']);
});

it('stores a new role with selected permissions', function () {
    $this->actingAs($this->admin);
    session(['active_company_id' => $this->company->id]);

    Livewire::test(RoleEditor::class)
        ->set('name', 'compliance-officer')
        ->call('togglePermission', 'view-audit-logs')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.roles.index'));

    $createdRole = Role::where('name', 'compliance-officer')->first();
    expect($createdRole)->not->toBeNull()
        ->and($createdRole->permissions)->toContain('view-audit-logs', 'view-dashboard');
});

it('updates an existing role with modified permissions', function () {
    $this->actingAs($this->admin);
    session(['active_company_id' => $this->company->id]);

    $role = Role::factory()->create([
        'name' => 'analyst',
        'permissions' => ['view-org-chart'],
    ]);

    Livewire::test(RoleEditor::class, ['roleId' => $role->id])
        ->set('name', 'lead-analyst')
        ->call('togglePermission', 'view-audit-logs')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.roles.index'));

    $role->refresh();
    expect($role->name)->toBe('lead-analyst')
        ->and($role->permissions)->toContain('view-audit-logs', 'view-org-chart');
});
