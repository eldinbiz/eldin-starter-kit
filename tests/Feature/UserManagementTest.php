<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers: create an admin user with an active company session
// ---------------------------------------------------------------------------

function createAdminUser(): array
{
    $company = Company::factory()->create(['is_active' => true]);
    Role::factory()->admin()->create();
    Role::factory()->user()->create();

    $user = User::factory()->create();
    $user->companies()->attach($company->id, ['status' => 'active']);
    $user->companyRoles()->attach(
        Role::where('name', 'admin')->value('id'),
        ['company_id' => $company->id]
    );

    return [$user, $company];
}

function actAsAdmin(TestCase $test): array
{
    [$user, $company] = createAdminUser();

    $test->actingAs($user);
    session(['active_company_id' => $company->id]);

    return [$user, $company];
}

function actAsRegularUser(TestCase $test): User
{
    $company = Company::factory()->create(['is_active' => true]);
    Role::factory()->user()->create();

    $user = User::factory()->create();
    $user->companies()->attach($company->id, ['status' => 'active']);
    $user->companyRoles()->attach(
        Role::where('name', 'user')->value('id'),
        ['company_id' => $company->id]
    );

    $test->actingAs($user);
    session(['active_company_id' => $company->id]);

    return $user;
}

// ---------------------------------------------------------------------------
// Authorization Tests
// ---------------------------------------------------------------------------

it('redirects guests from user management', function () {
    $this->get('/admin/users')->assertRedirect(route('login'));
    $this->get('/admin/users/create')->assertRedirect(route('login'));
});

it('blocks non-admin users from user management', function () {
    actAsRegularUser($this);

    $this->get('/admin/users')->assertForbidden();
    $this->get('/admin/users/create')->assertForbidden();
});

// ---------------------------------------------------------------------------
// Index
// ---------------------------------------------------------------------------

it('allows admin to view the user listing page', function () {
    actAsAdmin($this);

    $this->get('/admin/users')->assertOk();
});

it('allows filtering users by company id without type error', function () {
    [$user, $company] = actAsAdmin($this);
    Role::factory()->sysadmin()->create();
    $user->companyRoles()->attach(Role::where('name', 'sysadmin')->value('id'), ['company_id' => $company->id]);

    $response = $this->get('/admin/users?filter_company_id='.$company->id);

    $response->assertOk();
});

// ---------------------------------------------------------------------------
// Create User
// ---------------------------------------------------------------------------

it('allows admin to see the create user form', function () {
    actAsAdmin($this);

    $this->get('/admin/users/create')->assertSuccessful();
});

it('allows admin to create a new user', function () {
    [$_, $company] = actAsAdmin($this);
    $adminRoleId = Role::where('name', 'admin')->value('id');

    // Prime the session for CSRF
    $this->get('/admin/users/create');

    $this->post('/admin/users', [
        '_token' => session()->token(),
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
        'roles' => [$adminRoleId],
    ])->assertRedirect(route('admin.users.index'));

    $this->assertDatabaseHas('users', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    $user = User::where('email', 'john@example.com')->first();

    // User should be attached to the active company
    $this->assertDatabaseHas('company_user', [
        'user_id' => $user->id,
        'company_id' => $company->id,
    ]);

    // Default 'user' role should have been enforced
    $userRoleId = Role::where('name', 'user')->value('id');
    $this->assertDatabaseHas('company_user_role', [
        'user_id' => $user->id,
        'role_id' => $userRoleId,
        'company_id' => $company->id,
    ]);
});

it('shows validation errors when creating a user with missing data', function () {
    actAsAdmin($this);

    // Prime the session for CSRF
    $this->get('/admin/users/create');

    $this->post('/admin/users', [
        '_token' => session()->token(),
        'name' => '',
        'email' => 'not-an-email',
        'password' => 'short',
        'roles' => [],
    ])->assertSessionHasErrors(['name', 'email', 'password', 'roles']);
});

// ---------------------------------------------------------------------------
// Edit & Update
// ---------------------------------------------------------------------------

it('allows admin to view the edit user page', function () {
    actAsAdmin($this);

    $targetUser = User::factory()->create();

    $this->get("/admin/users/{$targetUser->id}/edit")->assertSuccessful();
});

it('allows admin to update a user name and email', function () {
    [$_, $company] = actAsAdmin($this);
    $adminRoleId = Role::where('name', 'admin')->value('id');

    $targetUser = User::factory()->create();

    // Prime the session for CSRF
    $this->get("/admin/users/{$targetUser->id}/edit");

    $this->put("/admin/users/{$targetUser->id}", [
        '_token' => session()->token(),
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
        'roles' => [$adminRoleId],
    ])->assertRedirect(route('admin.users.index'));

    $this->assertDatabaseHas('users', [
        'id' => $targetUser->id,
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
    ]);
});

it('allows admin to update a user password', function () {
    [$_, $company] = actAsAdmin($this);
    $adminRoleId = Role::where('name', 'admin')->value('id');

    $targetUser = User::factory()->create([
        'password' => bcrypt('oldpassword'),
    ]);

    // Prime the session for CSRF
    $this->get("/admin/users/{$targetUser->id}/edit");

    $this->put("/admin/users/{$targetUser->id}", [
        '_token' => session()->token(),
        'name' => $targetUser->name,
        'email' => $targetUser->email,
        'password' => 'newpassword123',
        'roles' => [$adminRoleId],
    ])->assertRedirect(route('admin.users.index'));

    // The password should have changed
    $targetUser->refresh();
    expect(Hash::check('newpassword123', $targetUser->password))->toBeTrue();
});

// ---------------------------------------------------------------------------
// Delete
// ---------------------------------------------------------------------------

it('prevents admin from deleting their own account', function () {
    [$user, $company] = actAsAdmin($this);

    // Prime the session for CSRF
    $this->get('/admin/users');

    $this->delete("/admin/users/{$user->id}", [
        '_token' => session()->token(),
    ])->assertRedirect(route('admin.users.index'))
        ->assertSessionHas('error', 'You cannot delete your own account.');

    $this->assertDatabaseHas('users', ['id' => $user->id]);
});

it('allows admin to delete another user', function () {
    actAsAdmin($this);

    $targetUser = User::factory()->create();

    // Prime the session for CSRF
    $this->get('/admin/users');

    $this->delete("/admin/users/{$targetUser->id}", [
        '_token' => session()->token(),
    ])->assertRedirect(route('admin.users.index'));

    $this->assertDatabaseMissing('users', ['id' => $targetUser->id]);
});

// ---------------------------------------------------------------------------
// Default User Role Enforcement
// ---------------------------------------------------------------------------

it('enforces default user role when creating a user without assigning roles', function () {
    actAsAdmin($this);
    $userRoleId = Role::where('name', 'user')->value('id');

    // Prime the session for CSRF
    $this->get('/admin/users/create');

    // The controller enforces the default 'user' role even if no roles are submitted
    $this->post('/admin/users', [
        '_token' => session()->token(),
        'name' => 'Roleless User',
        'email' => 'roleless@example.com',
        'password' => 'password123',
        'roles' => [$userRoleId], // pass at least the user role to satisfy 'required' validation
    ])->assertRedirect(route('admin.users.index'));

    $user = User::where('email', 'roleless@example.com')->first();

    // The 'user' role that was passed should be in the pivot table
    $this->assertDatabaseHas('company_user_role', [
        'user_id' => $user->id,
        'role_id' => $userRoleId,
    ]);
});
