<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Helper to create an active company and an administrator user with required roles.
 *
 * @return array{0: User, 1: Company}
 */
function createAdminUserWithCompany(): array
{
    $company = Company::factory()->create([
        'name' => 'Acme Corporation',
        'is_active' => true,
    ]);

    Role::factory()->admin()->create();
    Role::factory()->user()->create();

    $admin = User::factory()->create([
        'name' => 'Alice Admin',
        'email' => 'alice.admin@example.com',
        'password' => 'password',
        'auth_method' => 'local',
    ]);

    $admin->companies()->attach($company->id, [
        'status' => 'active',
        'job_title' => 'Chief Admin',
    ]);

    $adminRoleId = Role::where('name', 'admin')->value('id');
    $admin->companyRoles()->attach($adminRoleId, [
        'company_id' => $company->id,
    ]);

    return [$admin, $company];
}

/**
 * Helper to create an active company and a standard user with only the 'user' role.
 *
 * @return array{0: User, 1: Company}
 */
function createRegularUserWithCompany(): array
{
    $company = Company::factory()->create([
        'name' => 'Acme Corporation',
        'is_active' => true,
    ]);

    Role::factory()->admin()->create();
    $userRole = Role::factory()->user()->create();

    $user = User::factory()->create([
        'name' => 'Regular Randy',
        'email' => 'regular.randy@example.com',
        'password' => 'password',
        'auth_method' => 'local',
    ]);

    $user->companies()->attach($company->id, [
        'status' => 'active',
        'job_title' => 'Staff Member',
    ]);

    $user->companyRoles()->attach($userRole->id, [
        'company_id' => $company->id,
    ]);

    return [$user, $company];
}

/**
 * Helper to authenticate an administrator through the UI and land on the dashboard.
 */
function loginAdmin(User $admin): mixed
{
    return visit('/login')
        ->assertSee('Sign In')
        ->type('#email', $admin->email)
        ->click('@next-button')
        ->type('#password', 'password')
        ->click('@login-button')
        ->assertSee('Dashboard');
}

test('guest is redirected to login when accessing user management', function (): void {
    visit('/admin/users')
        ->assertPathIs('/login')
        ->assertSee('Sign In');
})->group('browser');

test('admin can view user list, search by name, and toggle filter panel', function (): void {
    [$admin, $company] = createAdminUserWithCompany();

    $userA = User::factory()->create([
        'name' => 'Alice Wonder',
        'email' => 'alice.wonder@example.com',
        'password' => 'password',
        'auth_method' => 'local',
    ]);
    $userA->companies()->attach($company->id, ['status' => 'active']);
    $userRoleId = Role::where('name', 'user')->value('id');
    $userA->companyRoles()->attach($userRoleId, ['company_id' => $company->id]);

    $userB = User::factory()->create([
        'name' => 'Bob Builder',
        'email' => 'bob.builder@example.com',
        'password' => 'password',
        'auth_method' => 'local',
    ]);
    $userB->companies()->attach($company->id, ['status' => 'active']);
    $userB->companyRoles()->attach($userRoleId, ['company_id' => $company->id]);

    loginAdmin($admin)
        ->click('@nav-user-management')
        ->assertPathIs('/admin/users')
        ->assertSee('All Users')
        ->assertSee('Alice Wonder')
        ->assertSee('Bob Builder')
        // Livewire Search: search for "Bob"
        ->type('@datatable-search-input', 'Bob')
        ->assertSee('Bob Builder')
        ->assertDontSee('Alice Wonder')
        // Clear search to restore all records
        ->type('@datatable-search-input', '')
        ->assertSee('Alice Wonder')
        ->assertSee('Bob Builder')
        // Toggle filters panel
        ->click('@datatable-filter-toggle')
        ->assertSee('Roles')
        ->click('@datatable-filter-toggle');
})->group('browser');

test('admin can create a new user', function (): void {
    [$admin, $company] = createAdminUserWithCompany();

    loginAdmin($admin)
        ->click('@nav-user-management')
        ->assertPathIs('/admin/users')
        ->click('@create-user-button')
        ->assertPathIs('/admin/users/create')
        ->assertSee('Create New User')
        // Fill valid new user details
        ->type('#name', 'Charlie Brown')
        ->type('#email', 'charlie.brown@example.com')
        ->type('#password', 'password123')
        ->click('@create-user-submit-button')
        // Verify redirected to user listing with new user shown in datatable
        ->assertPathIs('/admin/users')
        ->assertSee('Charlie Brown')
        ->assertSee('charlie.brown@example.com');

    expect(User::where('email', 'charlie.brown@example.com')->exists())->toBeTrue();
})->group('browser');

test('admin can edit an existing user details', function (): void {
    [$admin, $company] = createAdminUserWithCompany();

    $userToEdit = User::factory()->create([
        'name' => 'Diana Prince',
        'email' => 'diana.prince@example.com',
        'password' => 'password',
        'auth_method' => 'local',
    ]);
    $userToEdit->companies()->attach($company->id, ['status' => 'active']);
    $userRoleId = Role::where('name', 'user')->value('id');
    $userToEdit->companyRoles()->attach($userRoleId, ['company_id' => $company->id]);

    loginAdmin($admin)
        ->click('@nav-user-management')
        ->assertPathIs('/admin/users')
        ->assertSee('Diana Prince')
        ->click('@edit-user-link-'.$userToEdit->id)
        ->assertPathIs('/admin/users/'.$userToEdit->id.'/edit')
        ->assertSee('Edit User Details')
        // Update user name and email
        ->type('#name', 'Diana Updated')
        ->type('#email', 'diana.updated@example.com')
        ->click('@update-user-submit-button')
        // Verify redirected back to listing with updated details in datatable
        ->assertPathIs('/admin/users')
        ->assertSee('Diana Updated')
        ->assertSee('diana.updated@example.com');

    expect($userToEdit->fresh()->name)->toBe('Diana Updated');
    expect($userToEdit->fresh()->email)->toBe('diana.updated@example.com');
})->group('browser');

test('admin can assign and remove a company in user edit view', function (): void {
    [$admin, $company] = createAdminUserWithCompany();

    $secondCompany = Company::factory()->create([
        'name' => 'Beta Logistics',
        'is_active' => true,
    ]);

    $targetUser = User::factory()->create([
        'name' => 'Edward Norton',
        'email' => 'edward.norton@example.com',
        'password' => 'password',
        'auth_method' => 'local',
    ]);
    $targetUser->companies()->attach($company->id, ['status' => 'active']);
    $userRoleId = Role::where('name', 'user')->value('id');
    $targetUser->companyRoles()->attach($userRoleId, ['company_id' => $company->id]);

    loginAdmin($admin)
        ->click('@nav-user-management')
        ->click('@edit-user-link-'.$targetUser->id)
        ->assertPathIs('/admin/users/'.$targetUser->id.'/edit')
        ->assertSee('Company Assignments')
        // Assign to second company
        ->select('company_id', (string) $secondCompany->id)
        ->check('#assign_role_'.$userRoleId)
        ->type('job_title', 'Operations Lead')
        ->click('@assign-company-submit-button')
        ->assertSee('Beta Logistics')
        ->assertSee('Operations Lead')
        // Remove second company via type-to-confirm modal
        ->click('@remove-company-button-'.$secondCompany->id)
        ->assertSee('Remove from Beta Logistics?')
        ->type('@confirm-company-name-input-'.$secondCompany->id, 'Beta Logistics')
        ->click('@confirm-remove-company-button-'.$secondCompany->id)
        ->assertDontSee('Operations Lead');

    expect($targetUser->fresh()->companies->pluck('id'))->not->toContain($secondCompany->id);
})->group('browser');

test('admin can delete another user via modal, and self-delete button is hidden', function (): void {
    [$admin, $company] = createAdminUserWithCompany();

    $userToDelete = User::factory()->create([
        'name' => 'Franklin Pierce',
        'email' => 'franklin.pierce@example.com',
        'password' => 'password',
        'auth_method' => 'local',
    ]);
    $userToDelete->companies()->attach($company->id, ['status' => 'active']);
    $userRoleId = Role::where('name', 'user')->value('id');
    $userToDelete->companyRoles()->attach($userRoleId, ['company_id' => $company->id]);

    loginAdmin($admin)
        ->click('@nav-user-management')
        ->assertPathIs('/admin/users')
        // Self-delete button must be hidden for authenticated admin
        ->assertNotPresent('@delete-user-button-'.$admin->id)
        // Verify delete button is present for target user
        ->assertSee('Franklin Pierce')
        ->click('@delete-user-button-'.$userToDelete->id)
        ->assertSee('Delete User?')
        ->assertSee('Are you sure you want to permanently delete user')
        // Dismiss first via Cancel
        ->click('@cancel-delete-button')
        ->assertDontSee('Delete User?')
        ->assertSee('Franklin Pierce')
        // Open modal again and confirm delete
        ->click('@delete-user-button-'.$userToDelete->id)
        ->click('@confirm-delete-button')
        ->assertSee('deleted successfully.')
        ->assertDontSee('franklin.pierce@example.com');

    expect(User::find($userToDelete->id))->toBeNull();
})->group('browser');

test('unauthorized user without manage-users permission cannot access user management and sidebar link is hidden', function (): void {
    [$user, $company] = createRegularUserWithCompany();

    loginAdmin($user)
        // User management link must not be present in the sidebar for standard users
        ->assertNotPresent('@nav-user-management')
        // Attempting direct URL access must result in 403 Access Denied
        ->navigate('/admin/users')
        ->assertSee('Access Denied')
        ->assertSee('You do not have permission to access this resource.');
})->group('browser');

test('user creation fails with validation error for duplicate email', function (): void {
    [$admin, $company] = createAdminUserWithCompany();

    loginAdmin($admin)
        ->click('@nav-user-management')
        ->click('@create-user-button')
        ->assertPathIs('/admin/users/create')
        ->type('#name', 'Duplicate Dave')
        ->type('#email', $admin->email)
        ->type('#password', 'password123')
        ->click('@create-user-submit-button')
        ->assertSee('The email has already been taken.');

    expect(User::where('name', 'Duplicate Dave')->exists())->toBeFalse();
})->group('browser');

test('user creation fails with validation error for short password', function (): void {
    [$admin, $company] = createAdminUserWithCompany();

    loginAdmin($admin)
        ->click('@nav-user-management')
        ->click('@create-user-button')
        ->assertPathIs('/admin/users/create')
        ->type('#name', 'Short Sam')
        ->type('#email', 'short.sam@example.com')
        ->type('#password', '12345')
        ->click('@create-user-submit-button')
        ->assertSee('The password field must be at least 8 characters.');

    expect(User::where('email', 'short.sam@example.com')->exists())->toBeFalse();
})->group('browser');

test('company removal modal button remains disabled until exact company name is typed and cancel dismisses modal', function (): void {
    [$admin, $company] = createAdminUserWithCompany();

    $secondCompany = Company::factory()->create([
        'name' => 'Beta Logistics',
        'is_active' => true,
    ]);

    $targetUser = User::factory()->create([
        'name' => 'George Washington',
        'email' => 'george.washington@example.com',
        'password' => 'password',
        'auth_method' => 'local',
    ]);
    $targetUser->companies()->attach($company->id, ['status' => 'active']);
    $targetUser->companies()->attach($secondCompany->id, ['status' => 'active', 'job_title' => 'Logistics Officer']);
    $userRoleId = Role::where('name', 'user')->value('id');
    $targetUser->companyRoles()->attach($userRoleId, ['company_id' => $company->id]);
    $targetUser->companyRoles()->attach($userRoleId, ['company_id' => $secondCompany->id]);

    loginAdmin($admin)
        ->click('@nav-user-management')
        ->click('@edit-user-link-'.$targetUser->id)
        ->assertPathIs('/admin/users/'.$targetUser->id.'/edit')
        ->assertSee('Beta Logistics')
        // Trigger remove company modal
        ->click('@remove-company-button-'.$secondCompany->id)
        ->assertSee('Remove from Beta Logistics?')
        // Button must be disabled initially
        ->assertButtonDisabled('@confirm-remove-company-button-'.$secondCompany->id)
        // Typing mismatched name keeps button disabled
        ->type('@confirm-company-name-input-'.$secondCompany->id, 'Wrong Logistics Name')
        ->assertButtonDisabled('@confirm-remove-company-button-'.$secondCompany->id)
        // Typing exact company name enables the button
        ->type('@confirm-company-name-input-'.$secondCompany->id, 'Beta Logistics')
        ->assertButtonEnabled('@confirm-remove-company-button-'.$secondCompany->id)
        // Dismiss via Cancel button
        ->click('@cancel-remove-company-button-'.$secondCompany->id)
        ->assertSee('Logistics Officer');

    expect($targetUser->fresh()->companies->pluck('id'))->toContain($secondCompany->id);
})->group('browser');

test('user datatable displays empty state when search query matches no users and restores on clear', function (): void {
    [$admin, $company] = createAdminUserWithCompany();

    $user = User::factory()->create([
        'name' => 'Zachary Taylor',
        'email' => 'zachary.taylor@example.com',
        'password' => 'password',
        'auth_method' => 'local',
    ]);
    $user->companies()->attach($company->id, ['status' => 'active']);
    $userRoleId = Role::where('name', 'user')->value('id');
    $user->companyRoles()->attach($userRoleId, ['company_id' => $company->id]);

    loginAdmin($admin)
        ->click('@nav-user-management')
        ->assertPathIs('/admin/users')
        ->assertSee('Zachary Taylor')
        // Search for non-existent user query
        ->type('@datatable-search-input', 'NonExistentUserXYZ999')
        ->assertSee('No users found')
        ->assertSee('No user records matched your search query or filter selection.')
        ->assertDontSee('Zachary Taylor')
        // Clear search input restores original user list
        ->type('@datatable-search-input', '')
        ->assertSee('Zachary Taylor')
        ->assertDontSee('No users found');
})->group('browser');

test('editing user email to an existing email triggers validation error', function (): void {
    [$admin, $company] = createAdminUserWithCompany();

    $userA = User::factory()->create([
        'name' => 'Existing Person',
        'email' => 'existing.person@example.com',
        'password' => 'password',
        'auth_method' => 'local',
    ]);
    $userA->companies()->attach($company->id, ['status' => 'active']);
    $userRoleId = Role::where('name', 'user')->value('id');
    $userA->companyRoles()->attach($userRoleId, ['company_id' => $company->id]);

    $userToEdit = User::factory()->create([
        'name' => 'Edit Candidate',
        'email' => 'edit.candidate@example.com',
        'password' => 'password',
        'auth_method' => 'local',
    ]);
    $userToEdit->companies()->attach($company->id, ['status' => 'active']);
    $userToEdit->companyRoles()->attach($userRoleId, ['company_id' => $company->id]);

    loginAdmin($admin)
        ->click('@nav-user-management')
        ->click('@edit-user-link-'.$userToEdit->id)
        ->assertPathIs('/admin/users/'.$userToEdit->id.'/edit')
        ->assertSee('Edit User Details')
        // Attempt to change email to userA's email
        ->type('#email', 'existing.person@example.com')
        ->click('@update-user-submit-button')
        // Assert validation error appears on edit page
        ->assertSee('The email has already been taken.');

    expect($userToEdit->fresh()->email)->toBe('edit.candidate@example.com');
})->group('browser');

test('assigning user to an already assigned company is prevented by UI and rejected by server', function (): void {
    [$admin, $company] = createAdminUserWithCompany();

    $secondCompany = Company::factory()->create([
        'name' => 'Beta Logistics',
        'is_active' => true,
    ]);

    $targetUser = User::factory()->create([
        'name' => 'Multi Company User',
        'email' => 'multi.company@example.com',
        'password' => 'password',
        'auth_method' => 'local',
    ]);
    $targetUser->companies()->attach($company->id, ['status' => 'active']);
    $userRoleId = Role::where('name', 'user')->value('id');
    $targetUser->companyRoles()->attach($userRoleId, ['company_id' => $company->id]);

    // 1. In the UI, the dropdown only lists unassigned companies
    $page = loginAdmin($admin)
        ->click('@nav-user-management')
        ->click('@edit-user-link-'.$targetUser->id)
        ->assertPathIs('/admin/users/'.$targetUser->id.'/edit')
        ->assertSourceInHas('select[name="company_id"]', 'Beta Logistics')
        ->assertSourceInMissing('select[name="company_id"]', 'Acme Corporation');

    // 2. When assigned to all active companies, form is hidden with notice
    $targetUser->companies()->attach($secondCompany->id, ['status' => 'active']);
    $targetUser->companyRoles()->attach($userRoleId, ['company_id' => $secondCompany->id]);

    $page->refresh()
        ->assertSee('This user is already assigned to all active companies.')
        ->assertNotPresent('@assign-company-submit-button');

    // 3. Server-side Domain Action guard rejects duplicate company assignment
    expect(fn () => app(\App\Actions\Users\AssignCompanyUserAction::class)->execute(
        $targetUser,
        ['company_id' => $company->id, 'roles' => [$userRoleId]]
    ))->toThrow(\InvalidArgumentException::class, 'This user is already assigned to that company.');
})->group('browser');
