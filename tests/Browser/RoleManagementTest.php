<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

if (! function_exists('createRoleAdminUserWithCompany')) {
    /**
     * Helper to create an active company and an administrator user with required roles.
     *
     * @return array{0: User, 1: Company}
     */
    function createRoleAdminUserWithCompany(): array
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
}

if (! function_exists('createRoleRegularUserWithCompany')) {
    /**
     * Helper to create an active company and a standard user with only the 'user' role.
     *
     * @return array{0: User, 1: Company}
     */
    function createRoleRegularUserWithCompany(): array
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
}

if (! function_exists('loginRoleAdmin')) {
    /**
     * Helper to authenticate an administrator through the UI and land on the dashboard.
     */
    function loginRoleAdmin(User $admin): mixed
    {
        session()->forget('url.intended');

        return visit('/login')
            ->assertSee('Sign In')
            ->type('#email', $admin->email)
            ->click('@next-button')
            ->type('#password', 'password')
            ->click('@login-button')
            ->assertSee('Dashboard');
    }
}

// ---------------------------------------------------------------------------
// 1. Authorization & Access Control
// ---------------------------------------------------------------------------

test('guest is redirected to login when accessing role management', function (): void {
    visit('/admin/roles')
        ->assertPathIs('/login')
        ->assertSee('Sign In');
})->group('browser');

test('unauthorized user without manage-roles permission cannot see sidebar link and is denied direct access', function (): void {
    [$user, $company] = createRoleRegularUserWithCompany();

    loginRoleAdmin($user)
        // User Roles link must not be present in the sidebar for standard users
        ->assertNotPresent('@nav-user-roles')
        // Attempting direct URL access must result in 403 Access Denied
        ->navigate('/admin/roles')
        ->assertSee('Access Denied')
        ->assertSee('You do not have permission to access this resource.');
})->group('browser');

// ---------------------------------------------------------------------------
// 2. Role Listing, Search & Filters
// ---------------------------------------------------------------------------

test('admin can view role list, search by name, and restore list on clear', function (): void {
    [$admin, $company] = createRoleAdminUserWithCompany();

    Role::factory()->create([
        'name' => 'auditor',
        'is_active' => true,
    ]);

    Role::factory()->create([
        'name' => 'editor',
        'is_active' => true,
    ]);

    loginRoleAdmin($admin)
        ->click('@nav-user-roles')
        ->assertPathIs('/admin/roles')
        ->assertSee('All Roles')
        ->assertSee('Admin')
        ->assertSee('User')
        ->assertSee('Auditor')
        ->assertSee('Editor')
        // Search for "Auditor"
        ->wait(0.5)
        ->typeSlowly('@datatable-search-input', 'Auditor')
        ->wait(1)
        ->assertSee('Auditor')
        ->assertDontSee('Editor')
        // Clear search input before searching for non-existent role query to assert empty state
        ->type('@datatable-search-input', '')
        ->wait(0.5)
        ->typeSlowly('@datatable-search-input', 'NonExistentRoleXYZ999')
        ->wait(1)
        ->assertSee('No roles found')
        ->assertSee('No role records matched your search query or filter criteria.')
        ->assertDontSee('Auditor')
        ->assertDontSee('Editor')
        // Clear search input restores all records
        ->type('@datatable-search-input', '')
        ->wait(1)
        ->assertSee('Auditor')
        ->assertSee('Editor')
        ->assertDontSee('No roles found');
})->group('browser');

test('admin can toggle filter panel and filter by active and disabled status', function (): void {
    [$admin, $company] = createRoleAdminUserWithCompany();

    Role::factory()->create([
        'name' => 'active_specialist',
        'is_active' => true,
    ]);

    Role::factory()->create([
        'name' => 'inactive_archived',
        'is_active' => false,
    ]);

    loginRoleAdmin($admin)
        ->click('@nav-user-roles')
        ->assertPathIs('/admin/roles')
        ->assertSee('active_specialist')
        ->assertSee('inactive_archived')
        // Open filter panel
        ->click('@datatable-filter-toggle')
        ->assertSee('Status')
        // Filter by disabled only
        ->select('@status-filter-select', 'disabled')
        ->wait(1)
        ->assertSee('inactive_archived')
        ->assertDontSee('active_specialist')
        // Filter by active only
        ->select('@status-filter-select', 'active')
        ->wait(1)
        ->assertSee('active_specialist')
        ->assertDontSee('inactive_archived')
        // Close filter panel
        ->click('@datatable-filter-toggle')
        ->wait(0.5);
})->group('browser');

// ---------------------------------------------------------------------------
// 3. Role Creation (Positive & Negative)
// ---------------------------------------------------------------------------

test('admin can create a new role with permissions matrix and always-enabled permissions are protected', function (): void {
    [$admin, $company] = createRoleAdminUserWithCompany();

    loginRoleAdmin($admin)
        ->click('@nav-user-roles')
        ->assertPathIs('/admin/roles')
        ->click('@create-role-button')
        ->assertPathIs('/admin/roles/create')
        ->assertSee('Create New Role')
        ->assertSee('Permissions Matrix')
        // Always enabled permissions (e.g. view-dashboard) must have disabled input
        ->assertDisabled('@permission-input-view-dashboard')
        // Enter valid new role name
        ->typeSlowly('@role-name-input', 'compliance_officer')
        // Toggle user management group permissions via Select All button
        ->click('@toggle-group-user-management')
        ->wait(0.5)
        ->click('@role-submit-button')
        ->wait(0.5)
        // Verify redirected to listing with flash message and role listed
        ->assertPathIs('/admin/roles')
        ->assertSee('Role created successfully.')
        ->assertSee('compliance_officer');

    $role = Role::where('name', 'compliance_officer')->first();
    expect($role)->not->toBeNull();
    expect($role->hasPermission('view-dashboard'))->toBeTrue();
    expect($role->hasPermission('manage-users'))->toBeTrue();
})->group('browser');

test('role creation fails with validation error for duplicate role name', function (): void {
    [$admin, $company] = createRoleAdminUserWithCompany();

    loginRoleAdmin($admin)
        ->click('@nav-user-roles')
        ->click('@create-role-button')
        ->assertPathIs('/admin/roles/create')
        // Attempt to create a role with name "admin" which already exists
        ->typeSlowly('@role-name-input', 'admin')
        ->click('@role-submit-button')
        ->wait(0.5)
        ->assertSee('The name has already been taken.');

    expect(Role::where('name', 'admin')->count())->toBe(1);
})->group('browser');

test('role creation fails with validation error for empty name when form validation is submitted', function (): void {
    [$admin, $company] = createRoleAdminUserWithCompany();

    $page = loginRoleAdmin($admin)
        ->click('@nav-user-roles')
        ->click('@create-role-button')
        ->assertPathIs('/admin/roles/create');

    // Remove client-side HTML5 'required' attribute to test server Livewire validation
    $page->script("document.getElementById('role_name').removeAttribute('required');");

    $page->click('@role-submit-button')
        ->wait(0.5)
        ->assertSee('The name field is required.');
})->group('browser');

// ---------------------------------------------------------------------------
// 4. Role Editing (Positive & Negative)
// ---------------------------------------------------------------------------

test('admin can edit an existing custom role details and permissions', function (): void {
    [$admin, $company] = createRoleAdminUserWithCompany();

    $customRole = Role::factory()->create([
        'name' => 'tech_lead',
        'permissions' => ['manage-users'],
        'is_active' => true,
    ]);

    loginRoleAdmin($admin)
        ->click('@nav-user-roles')
        ->assertPathIs('/admin/roles')
        ->assertSee('tech_lead')
        ->click('@edit-role-link-'.$customRole->id)
        ->assertPathIs('/admin/roles/'.$customRole->id.'/edit')
        ->assertSee('Edit Role: Tech_lead')
        // Update role name
        ->type('@role-name-input', 'senior_tech_lead')
        ->click('@role-submit-button')
        // Verify redirected to listing with flash message and updated name
        ->assertPathIs('/admin/roles')
        ->assertSee('Role updated successfully.')
        ->assertSee('senior_tech_lead');

    expect($customRole->fresh()->name)->toBe('senior_tech_lead');
})->group('browser');

test('editing role fails with validation error when renaming to an existing other role name', function (): void {
    [$admin, $company] = createRoleAdminUserWithCompany();

    $existingRole = Role::factory()->create([
        'name' => 'lead_auditor',
        'is_active' => true,
    ]);

    $roleToEdit = Role::factory()->create([
        'name' => 'junior_auditor',
        'is_active' => true,
    ]);

    loginRoleAdmin($admin)
        ->click('@nav-user-roles')
        ->click('@edit-role-link-'.$roleToEdit->id)
        ->assertPathIs('/admin/roles/'.$roleToEdit->id.'/edit')
        // Attempt renaming to existing other role's name
        ->type('@role-name-input', 'lead_auditor')
        ->click('@role-submit-button')
        ->wait(0.5)
        ->assertSee('The name has already been taken.');

    expect($roleToEdit->fresh()->name)->toBe('junior_auditor');
})->group('browser');

// ---------------------------------------------------------------------------
// 5. Role Status Toggling & System Role Protection (Positive & Negative)
// ---------------------------------------------------------------------------

test('admin can toggle custom role between active and disabled status', function (): void {
    [$admin, $company] = createRoleAdminUserWithCompany();

    $customRole = Role::factory()->create([
        'name' => 'field_agent',
        'is_active' => true,
    ]);

    loginRoleAdmin($admin)
        ->click('@nav-user-roles')
        ->assertPathIs('/admin/roles')
        ->assertSee('field_agent')
        // Click toggle status to disable
        ->click('@toggle-role-status-button-'.$customRole->id)
        ->assertSee('Role "field_agent" has been disabled.')
        ->assertSee('Disabled')
        // Click toggle status again on the active page to re-enable
        ->click('@toggle-role-status-button-'.$customRole->id)
        ->assertSee('Role "field_agent" has been enabled.')
        ->assertSee('Active');

    expect($customRole->fresh()->is_active)->toBeTrue();
})->group('browser');

test('attempting to disable protected system user role is blocked with error message', function (): void {
    [$admin, $company] = createRoleAdminUserWithCompany();

    $userRole = Role::where('name', 'user')->firstOrFail();

    loginRoleAdmin($admin)
        ->click('@nav-user-roles')
        ->assertPathIs('/admin/roles')
        // Click toggle status button on the protected 'user' role
        ->click('@toggle-role-status-button-'.$userRole->id)
        ->assertSee('The "user" role cannot be disabled.');

    expect($userRole->fresh()->is_active)->toBeTrue();
})->group('browser');

// ---------------------------------------------------------------------------
// 6. Role Soft-Deletion via Type-to-Confirm Modal (Positive & Negative)
// ---------------------------------------------------------------------------

test('delete button is hidden for active roles, requires exact typed name in modal, and dismisses on cancel', function (): void {
    [$admin, $company] = createRoleAdminUserWithCompany();

    $customRole = Role::factory()->create([
        'name' => 'temp_assistant',
        'is_active' => true,
    ]);

    loginRoleAdmin($admin)
        ->click('@nav-user-roles')
        ->assertPathIs('/admin/roles')
        ->assertSee('temp_assistant')
        // Active role must not have delete button present
        ->assertNotPresent('@delete-role-button-'.$customRole->id)
        // Disable role first to allow deletion
        ->click('@toggle-role-status-button-'.$customRole->id)
        ->assertSee('Role "temp_assistant" has been disabled.')
        // Delete button is now present for disabled role
        ->assertPresent('@delete-role-button-'.$customRole->id)
        ->click('@delete-role-button-'.$customRole->id)
        ->assertSee('Delete Role?')
        ->assertSee('Type temp_assistant to confirm.')
        // Confirm delete button is disabled initially
        ->assertButtonDisabled('@confirm-delete-role-button')
        // Typing mismatched name keeps button disabled
        ->type('@delete-confirm-name-input', 'wrong_name')
        ->assertButtonDisabled('@confirm-delete-role-button')
        // Dismiss via Cancel button
        ->click('@cancel-delete-role-button')
        ->assertDontSee('Delete Role?')
        ->assertSee('temp_assistant');

    expect($customRole->fresh())->not->toBeNull();
    expect($customRole->fresh()->trashed())->toBeFalse();
})->group('browser');

test('admin can soft-delete a disabled role after typing exact name, and restore it from trash', function (): void {
    [$admin, $company] = createRoleAdminUserWithCompany();

    $roleToDelete = Role::factory()->create([
        'name' => 'legacy_operator',
        'is_active' => false,
    ]);

    $page = loginRoleAdmin($admin)
        ->click('@nav-user-roles')
        ->assertPathIs('/admin/roles')
        ->assertSee('legacy_operator')
        // Open delete modal
        ->click('@delete-role-button-'.$roleToDelete->id)
        ->assertSee('Delete Role?')
        // Type exact role name to enable confirmation button
        ->type('@delete-confirm-name-input', 'legacy_operator')
        ->assertButtonEnabled('@confirm-delete-role-button')
        ->click('@confirm-delete-role-button')
        // Verify success flash and role action buttons are removed from standard listing
        ->assertSee('Role "legacy_operator" has been deleted.')
        ->assertNotPresent('@delete-role-button-'.$roleToDelete->id)
        ->assertNotPresent('@edit-role-link-'.$roleToDelete->id);

    expect(Role::onlyTrashed()->where('name', 'legacy_operator')->exists())->toBeTrue();

    // Open filter panel on current page to view trashed roles
    $page->click('@datatable-filter-toggle')
        ->click('@trashed-filter-checkbox')
        ->wait(1)
        ->assertSee('legacy_operator')
        ->assertSee('Deleted')
        // Restore role
        ->click('@restore-role-button-'.$roleToDelete->id)
        ->assertSee('Role "legacy_operator" has been restored.');

    expect(Role::onlyTrashed()->where('name', 'legacy_operator')->exists())->toBeFalse();
    expect($roleToDelete->fresh()->trashed())->toBeFalse();
    expect($roleToDelete->fresh()->is_active)->toBeTrue();
})->group('browser');
