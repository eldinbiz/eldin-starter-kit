<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helper Functions
// ---------------------------------------------------------------------------

function createRoleAdminUser(): array
{
    $company = Company::factory()->create(['is_active' => true]);
    $adminRole = Role::factory()->admin()->create();
    Role::factory()->user()->create();

    $user = User::factory()->create();
    $user->companies()->attach($company->id, ['status' => 'active']);
    $user->companyRoles()->attach($adminRole->id, ['company_id' => $company->id]);

    return [$user, $company];
}

function actAsRoleAdmin(TestCase $test): array
{
    [$user, $company] = createRoleAdminUser();

    $test->actingAs($user);
    session(['active_company_id' => $company->id]);

    return [$user, $company];
}

function actAsRoleRegularUser(TestCase $test): User
{
    $company = Company::factory()->create(['is_active' => true]);
    $userRole = Role::factory()->user()->create();

    $user = User::factory()->create();
    $user->companies()->attach($company->id, ['status' => 'active']);
    $user->companyRoles()->attach($userRole->id, ['company_id' => $company->id]);

    $test->actingAs($user);
    session(['active_company_id' => $company->id]);

    return $user;
}

// ---------------------------------------------------------------------------
// Authentication & Authorization Tests
// ---------------------------------------------------------------------------

it('redirects guests from role management routes', function (): void {
    $role = Role::factory()->create(['name' => 'editor']);

    $this->get(route('admin.roles.index'))->assertRedirect(route('login'));
    $this->get(route('admin.roles.create'))->assertRedirect(route('login'));
    $this->post(route('admin.roles.store'), ['name' => 'new-role'])->assertRedirect(route('login'));
    $this->get(route('admin.roles.edit', $role))->assertRedirect(route('login'));
    $this->put(route('admin.roles.update', $role), ['name' => 'updated-name'])->assertRedirect(route('login'));
    $this->delete(route('admin.roles.destroy', $role))->assertRedirect(route('login'));
    $this->post(route('admin.roles.restore', $role))->assertRedirect(route('login'));
    $this->delete(route('admin.roles.soft-delete', $role))->assertRedirect(route('login'));
});

it('blocks non-admin users from role management routes', function (): void {
    actAsRoleRegularUser($this);
    $role = Role::factory()->create(['name' => 'editor']);

    $this->get(route('admin.roles.index'))->assertForbidden();
    $this->get(route('admin.roles.create'))->assertForbidden();
    $this->post(route('admin.roles.store'), ['name' => 'new-role'])->assertForbidden();
    $this->get(route('admin.roles.edit', $role))->assertForbidden();
    $this->put(route('admin.roles.update', $role), ['name' => 'updated-name'])->assertForbidden();
    $this->delete(route('admin.roles.destroy', $role))->assertForbidden();
    $this->post(route('admin.roles.restore', $role))->assertForbidden();
    $this->delete(route('admin.roles.soft-delete', $role))->assertForbidden();
});

// ---------------------------------------------------------------------------
// Index
// ---------------------------------------------------------------------------

it('allows admin to view the roles listing page', function (): void {
    actAsRoleAdmin($this);

    Role::factory()->create(['name' => 'manager']);

    $this->get(route('admin.roles.index'))
        ->assertSuccessful()
        ->assertSee('manager');
});

it('allows admin to view soft-deleted roles with trashed filter', function (): void {
    actAsRoleAdmin($this);

    $disabledRole = Role::factory()->create(['name' => 'archived-role', 'is_active' => false]);
    $disabledRole->delete(); // Soft delete

    $this->get(route('admin.roles.index', ['trashed' => '1']))
        ->assertSuccessful()
        ->assertSee('archived-role');
});

// ---------------------------------------------------------------------------
// Create & Store
// ---------------------------------------------------------------------------

it('allows admin to see the create role form', function (): void {
    actAsRoleAdmin($this);

    $this->get(route('admin.roles.create'))->assertSuccessful();
});

it('allows admin to store a new role with permissions', function (): void {
    actAsRoleAdmin($this);

    // Prime session for CSRF
    $this->get(route('admin.roles.create'));

    $this->post(route('admin.roles.store'), [
        '_token' => session()->token(),
        'name' => 'auditor',
        'permissions' => ['users.view', 'reports.view'],
    ])->assertRedirect(route('admin.roles.index'))
        ->assertSessionHas('success', 'Role created successfully.');

    $this->assertDatabaseHas('roles', [
        'name' => 'auditor',
    ]);

    $createdRole = Role::where('name', 'auditor')->first();
    expect($createdRole->permissions)->toContain('users.view', 'reports.view');
});

it('merges always-enabled permissions automatically when storing a role', function (): void {
    actAsRoleAdmin($this);

    $this->get(route('admin.roles.create'));

    $this->post(route('admin.roles.store'), [
        '_token' => session()->token(),
        'name' => 'custom-role',
        'permissions' => [],
    ])->assertRedirect(route('admin.roles.index'));

    $role = Role::where('name', 'custom-role')->first();

    // Check if any configured always_enabled permissions were merged
    foreach (config('permissions.list', []) as $key => $definition) {
        if ($definition['always_enabled'] ?? false) {
            expect($role->permissions)->toContain($key);
        }
    }
});

it('validates role creation required and unique fields', function (): void {
    actAsRoleAdmin($this);
    Role::factory()->create(['name' => 'existing-role']);

    $this->get(route('admin.roles.create'));

    // Missing name
    $this->post(route('admin.roles.store'), [
        '_token' => session()->token(),
        'name' => '',
    ])->assertSessionHasErrors(['name']);

    // Duplicate name
    $this->post(route('admin.roles.store'), [
        '_token' => session()->token(),
        'name' => 'existing-role',
    ])->assertSessionHasErrors(['name']);
});

// ---------------------------------------------------------------------------
// Edit & Update
// ---------------------------------------------------------------------------

it('allows admin to see the edit role form', function (): void {
    actAsRoleAdmin($this);

    $role = Role::factory()->create(['name' => 'supervisor']);

    $this->get(route('admin.roles.edit', $role))->assertSuccessful();
});

it('allows admin to update a role name and permissions', function (): void {
    actAsRoleAdmin($this);

    $role = Role::factory()->create([
        'name' => 'supervisor',
        'permissions' => ['users.view'],
    ]);

    $this->get(route('admin.roles.edit', $role));

    $this->put(route('admin.roles.update', $role), [
        '_token' => session()->token(),
        'name' => 'senior-supervisor',
        'permissions' => ['users.view', 'users.create'],
    ])->assertRedirect(route('admin.roles.index'))
        ->assertSessionHas('success', 'Role updated successfully.');

    $this->assertDatabaseHas('roles', [
        'id' => $role->id,
        'name' => 'senior-supervisor',
    ]);

    $role->refresh();
    expect($role->permissions)->toContain('users.view', 'users.create');
});

it('prevents updating a role name to an existing duplicate name', function (): void {
    actAsRoleAdmin($this);

    Role::factory()->create(['name' => 'existing-name']);
    $targetRole = Role::factory()->create(['name' => 'target-name']);

    $this->get(route('admin.roles.edit', $targetRole));

    $this->put(route('admin.roles.update', $targetRole), [
        '_token' => session()->token(),
        'name' => 'existing-name',
    ])->assertSessionHasErrors(['name']);
});

// ---------------------------------------------------------------------------
// Destroy (Disable Role)
// ---------------------------------------------------------------------------

it('allows admin to disable an active role', function (): void {
    actAsRoleAdmin($this);

    $role = Role::factory()->create(['name' => 'temporary-role', 'is_active' => true]);

    $this->get(route('admin.roles.index'));

    $this->delete(route('admin.roles.destroy', $role), [
        '_token' => session()->token(),
    ])->assertRedirect(route('admin.roles.index'))
        ->assertSessionHas('success', 'Role "temporary-role" has been disabled.');

    $role->refresh();
    expect($role->is_active)->toBeFalse();
});

it('prevents disabling the default user role', function (): void {
    actAsRoleAdmin($this);

    $userRole = Role::where('name', 'user')->first();

    $this->get(route('admin.roles.index'));

    $this->delete(route('admin.roles.destroy', $userRole), [
        '_token' => session()->token(),
    ])->assertRedirect(route('admin.roles.index'))
        ->assertSessionHas('error', 'The "user" role cannot be disabled.');

    $userRole->refresh();
    expect($userRole->is_active)->toBeTrue();
});

// ---------------------------------------------------------------------------
// Restore / Enable Role
// ---------------------------------------------------------------------------

it('allows admin to enable a disabled role', function (): void {
    actAsRoleAdmin($this);

    $role = Role::factory()->create(['name' => 'disabled-role', 'is_active' => false]);

    $this->get(route('admin.roles.index'));

    $this->post(route('admin.roles.restore', $role), [
        '_token' => session()->token(),
    ])->assertRedirect(route('admin.roles.index'))
        ->assertSessionHas('success', 'Role "disabled-role" has been enabled.');

    $role->refresh();
    expect($role->is_active)->toBeTrue();
});

it('allows admin to restore a soft-deleted role', function (): void {
    actAsRoleAdmin($this);

    $role = Role::factory()->create(['name' => 'trashed-role', 'is_active' => false]);
    $role->delete(); // soft delete

    $this->get(route('admin.roles.index'));

    $this->post(route('admin.roles.restore', $role), [
        '_token' => session()->token(),
    ])->assertRedirect(route('admin.roles.index'))
        ->assertSessionHas('success', 'Role "trashed-role" has been restored.');

    $role->refresh();
    expect($role->trashed())->toBeFalse()
        ->and($role->is_active)->toBeTrue();
});

// ---------------------------------------------------------------------------
// Soft-Delete Role
// ---------------------------------------------------------------------------

it('allows admin to soft-delete a disabled role', function (): void {
    actAsRoleAdmin($this);

    $role = Role::factory()->create(['name' => 'to-be-deleted', 'is_active' => false]);

    $this->get(route('admin.roles.index'));

    $this->delete(route('admin.roles.soft-delete', $role), [
        '_token' => session()->token(),
    ])->assertRedirect(route('admin.roles.index'))
        ->assertSessionHas('success', 'Role "to-be-deleted" has been deleted.');

    $this->assertSoftDeleted('roles', ['id' => $role->id]);
});

it('prevents soft-deleting an active role without disabling it first', function (): void {
    actAsRoleAdmin($this);

    $role = Role::factory()->create(['name' => 'active-role', 'is_active' => true]);

    $this->get(route('admin.roles.index'));

    $this->delete(route('admin.roles.soft-delete', $role), [
        '_token' => session()->token(),
    ])->assertRedirect(route('admin.roles.index'))
        ->assertSessionHas('error', 'Role "active-role" must be disabled before it can be deleted.');

    $this->assertDatabaseHas('roles', ['id' => $role->id, 'deleted_at' => null]);
});

it('prevents soft-deleting the default user role', function (): void {
    actAsRoleAdmin($this);

    $userRole = Role::where('name', 'user')->first();

    $this->get(route('admin.roles.index'));

    $this->delete(route('admin.roles.soft-delete', $userRole), [
        '_token' => session()->token(),
    ])->assertRedirect(route('admin.roles.index'))
        ->assertSessionHas('error', 'The "user" role cannot be deleted.');

    $this->assertDatabaseHas('roles', ['id' => $userRole->id, 'deleted_at' => null]);
});
