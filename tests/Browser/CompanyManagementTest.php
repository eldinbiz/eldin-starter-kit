<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

if (! function_exists('createCompanyAdminUserWithCompany')) {
    /**
     * Helper to create an active company and an administrator with manage-companies permissions.
     *
     * @return array{0: User, 1: Company}
     */
    function createCompanyAdminUserWithCompany(): array
    {
        $company = Company::factory()->create([
            'name' => 'Acme Corporation',
            'slug' => 'acme-corp',
            'type' => 'holding',
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
            'job_title' => 'Chief Executive',
        ]);

        $adminRoleId = Role::where('name', 'admin')->value('id');
        $admin->companyRoles()->attach($adminRoleId, [
            'company_id' => $company->id,
        ]);

        return [$admin, $company];
    }
}

if (! function_exists('createCompanyViewerUserWithCompany')) {
    /**
     * Helper to create an active company and a company-admin with view-companies permissions only.
     *
     * @return array{0: User, 1: Company}
     */
    function createCompanyViewerUserWithCompany(): array
    {
        $company = Company::factory()->create([
            'name' => 'Acme Corporation',
            'slug' => 'acme-corp',
            'type' => 'holding',
            'is_active' => true,
        ]);

        Role::factory()->admin()->create();
        $companyAdminRole = Role::factory()->companyAdmin()->create();
        Role::factory()->user()->create();

        $viewer = User::factory()->create([
            'name' => 'Victor Viewer',
            'email' => 'victor.viewer@example.com',
            'password' => 'password',
            'auth_method' => 'local',
        ]);

        $viewer->companies()->attach($company->id, [
            'status' => 'active',
            'job_title' => 'Branch Auditor',
        ]);

        $viewer->companyRoles()->attach($companyAdminRole->id, [
            'company_id' => $company->id,
        ]);

        return [$viewer, $company];
    }
}

if (! function_exists('createCompanyRegularUserWithCompany')) {
    /**
     * Helper to create an active company and a standard user with neither view nor manage permissions.
     *
     * @return array{0: User, 1: Company}
     */
    function createCompanyRegularUserWithCompany(): array
    {
        $company = Company::factory()->create([
            'name' => 'Acme Corporation',
            'slug' => 'acme-corp',
            'type' => 'holding',
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

if (! function_exists('loginCompanyUser')) {
    /**
     * Helper to authenticate a user through the UI and land on the dashboard.
     */
    function loginCompanyUser(User $user): mixed
    {
        session()->forget('url.intended');

        return visit('/login')
            ->assertSee('Sign In')
            ->type('#email', $user->email)
            ->click('@next-button')
            ->type('#password', 'password')
            ->click('@login-button')
            ->assertSee('Dashboard');
    }
}

if (! function_exists('waitForCompanyTree')) {
    /**
     * Helper to wait for the D3 Org Chart hierarchy tree to finish its asynchronous load (>5s).
     */
    function waitForCompanyTree(mixed $page, int $seconds = 8): mixed
    {
        return $page
            ->wait($seconds)
            ->assertDontSee('Loading company hierarchy...')
            ->assertPresent('#org-chart-container svg')
            ->assertPresent('[data-company-id]');
    }
}

// ---------------------------------------------------------------------------
// 1. Authorization & Access Control
// ---------------------------------------------------------------------------

test('guest is redirected to login when accessing company management', function (): void {
    visit('/admin/companies')
        ->assertPathIs('/login')
        ->assertSee('Sign In');
})->group('browser');

test('unauthorized user without company permissions cannot see sidebar link and is denied direct access', function (): void {
    [$user, $company] = createCompanyRegularUserWithCompany();

    loginCompanyUser($user)
        // Company Management link must not be present in sidebar
        ->assertNotPresent('@nav-company-management')
        // Direct URL access must return 403 Forbidden
        ->navigate('/admin/companies')
        ->assertSee('Access Denied')
        ->assertSee('You do not have permission to access this resource.');
})->group('browser');

test('company admin with view-only permissions can view tree but cannot see management buttons', function (): void {
    [$viewer, $company] = createCompanyViewerUserWithCompany();

    $page = loginCompanyUser($viewer)
        ->click('@nav-company-management')
        ->assertPathIs('/admin/companies')
        ->assertSee('Company Hierarchy');

    waitForCompanyTree($page, 15)
        ->assertSee('Acme Corporation')
        // Add Company button must NOT be rendered for view-only users
        ->assertNotPresent('@add-company-button')
        // Node mutation buttons must NOT be rendered
        ->assertNotPresent('@edit-company-link-'.$company->id)
        ->assertNotPresent('@toggle-company-status-button-'.$company->id)
        ->assertNotPresent('@add-child-company-link-'.$company->id)
        // Direct navigation to create page must be rejected with 403
        ->navigate('/admin/companies/create')
        ->assertSee('Access Denied');
})->group('browser');

// ---------------------------------------------------------------------------
// 2. Sidebar Navigation
// ---------------------------------------------------------------------------

test('admin can navigate to company management from administration sidebar', function (): void {
    [$admin, $company] = createCompanyAdminUserWithCompany();

    loginCompanyUser($admin)
        ->click('@nav-company-management')
        ->assertPathIs('/admin/companies')
        ->assertSee('Company Hierarchy')
        ->assertSee('Manage the organizational structure of companies');
})->group('browser');

// ---------------------------------------------------------------------------
// 3. Hierarchy Tree Canvas & Real-time Search (>5s Async Handling)
// ---------------------------------------------------------------------------

test('admin can view single root company in hierarchy tree when no subsidiaries exist', function (): void {
    [$admin, $company] = createCompanyAdminUserWithCompany();

    $page = loginCompanyUser($admin)
        ->click('@nav-company-management')
        ->assertPathIs('/admin/companies');

    waitForCompanyTree($page, 15)
        ->assertSee('Acme Corporation')
        ->assertDontSee('Alpha Logistics');
})->group('browser');

test('admin can view hierarchy tree and filter nodes with search input', function (): void {
    [$admin, $parent] = createCompanyAdminUserWithCompany();

    $childAlpha = Company::factory()->create([
        'name' => 'Alpha Logistics',
        'slug' => 'alpha-logistics',
        'type' => 'operating',
        'parent_id' => $parent->id,
        'is_active' => true,
    ]);

    $childBeta = Company::factory()->create([
        'name' => 'Beta Software',
        'slug' => 'beta-software',
        'type' => 'operating',
        'parent_id' => $parent->id,
        'is_active' => true,
    ]);

    $page = loginCompanyUser($admin)
        ->click('@nav-company-management')
        ->assertPathIs('/admin/companies');

    waitForCompanyTree($page, 15)
        ->assertSee('Acme Corporation')
        ->assertSee('Alpha Logistics')
        ->assertSee('Beta Software')
        // Filter search input for "Beta"
        ->typeSlowly('@company-search-input', 'Beta')
        ->wait(1);

    // Matching card retains opacity 1, non-matching card is dimmed to 0.2
    $page->assertScript("document.querySelector('[data-company-id=\"{$childBeta->id}\"]')?.style.opacity === '1'");
    $page->assertScript("document.querySelector('[data-company-id=\"{$childAlpha->id}\"]')?.style.opacity === '0.2'");

    // Clearing search restores all cards to full opacity
    $page->type('@company-search-input', '')
        ->wait(1);

    $page->assertScript("document.querySelector('[data-company-id=\"{$childBeta->id}\"]')?.style.opacity === '1'");
    $page->assertScript("document.querySelector('[data-company-id=\"{$childAlpha->id}\"]')?.style.opacity === '1'");
})->group('browser');

test('admin can toggle company active status and is prevented from disabling parent with active children', function (): void {
    [$admin, $parent] = createCompanyAdminUserWithCompany();

    $child = Company::factory()->create([
        'name' => 'Active Subsidiary',
        'slug' => 'active-subsidiary',
        'type' => 'operating',
        'parent_id' => $parent->id,
        'is_active' => true,
    ]);

    $page = loginCompanyUser($admin)
        ->click('@nav-company-management')
        ->assertPathIs('/admin/companies');

    waitForCompanyTree($page, 15);

    // Attempting to disable parent company while child is active must fail
    $page->click('@toggle-company-status-button-'.$parent->id)
        ->waitForText('All child companies must be disabled before disabling this company.', 10)
        ->assertSee('All child companies must be disabled before disabling this company.');

    expect($parent->fresh()->is_active)->toBeTrue();

    // Disabling leaf child company succeeds
    $page->click('@toggle-company-status-button-'.$child->id)
        ->waitForText('Company disabled.', 10)
        ->assertSee('Company disabled.');

    expect($child->fresh()->is_active)->toBeFalse();
})->group('browser');

// ---------------------------------------------------------------------------
// 4. Company Creation Flow
// ---------------------------------------------------------------------------

test('admin can create a top-level company and a child company with prefilled parent', function (): void {
    [$admin, $company] = createCompanyAdminUserWithCompany();

    $page = loginCompanyUser($admin)
        ->click('@nav-company-management')
        ->assertPathIs('/admin/companies');

    waitForCompanyTree($page, 15);

    // 1. Create a new top-level holding company
    $page->click('@add-company-button')
        ->assertPathIs('/admin/companies/create')
        ->assertSee('Create Company')
        ->type('#name', 'Global Holdings')
        ->type('#slug', 'global-holdings')
        ->select('#type', 'holding')
        ->select('#parent_id', '')
        ->click('@create-company-submit-button')
        ->wait(2)
        ->assertPathIs('/admin/companies');

    $globalHoldings = Company::where('slug', 'global-holdings')->first();
    expect($globalHoldings)->not->toBeNull();
    expect($globalHoldings->parent_id)->toBeNull();

    // Wait for hierarchy tree to refresh
    waitForCompanyTree($page, 15)
        ->assertSee('Global Holdings');

    // 2. Click +Child on Global Holdings card to create a nested subsidiary
    $page->click('@add-child-company-link-'.$globalHoldings->id)
        ->assertPathIs('/admin/companies/create')
        ->assertSelected('#parent_id', (string) $globalHoldings->id)
        ->type('#name', 'Global Cloud Services')
        ->type('#slug', 'global-cloud-services')
        ->select('#type', 'operating')
        ->click('@create-company-submit-button')
        ->wait(2)
        ->assertPathIs('/admin/companies');

    waitForCompanyTree($page, 15)
        ->assertSee('Global Cloud Services');

    $cloudServices = Company::where('slug', 'global-cloud-services')->first();
    expect($cloudServices)->not->toBeNull();
    expect($cloudServices->parent_id)->toBe($globalHoldings->id);
})->group('browser');

test('company creation fails with validation error for duplicate slug', function (): void {
    [$admin, $company] = createCompanyAdminUserWithCompany();

    loginCompanyUser($admin)
        ->navigate('/admin/companies/create')
        ->assertPathIs('/admin/companies/create')
        ->assertSee('Create Company')
        // Attempt to create with existing company's slug
        ->type('#name', 'Duplicate Candidate')
        ->type('#slug', $company->slug)
        ->select('#type', 'operating')
        ->click('@create-company-submit-button')
        ->wait(2)
        ->assertPathIs('/admin/companies/create');

    expect(Company::where('slug', $company->slug)->count())->toBe(1);
    expect(Company::where('name', 'Duplicate Candidate')->exists())->toBeFalse();
})->group('browser');

test('company creation form cancel button returns to hierarchy page', function (): void {
    [$admin, $company] = createCompanyAdminUserWithCompany();

    loginCompanyUser($admin)
        ->click('@nav-company-management')
        ->assertPathIs('/admin/companies')
        ->click('@add-company-button')
        ->assertPathIs('/admin/companies/create')
        ->assertSee('Create Company')
        ->click('@create-company-cancel-button')
        ->assertPathIs('/admin/companies')
        ->assertSee('Company Hierarchy');
})->group('browser');

// ---------------------------------------------------------------------------
// 5. Edit Company Page & User Membership Management (/admin/companies/{id}/edit)
// ---------------------------------------------------------------------------

test('admin can edit company details and parent dropdown excludes self and subsidiaries', function (): void {
    [$admin, $parent] = createCompanyAdminUserWithCompany();

    $child = Company::factory()->create([
        'name' => 'Child Division',
        'slug' => 'child-division',
        'type' => 'sub-holding',
        'parent_id' => $parent->id,
        'is_active' => true,
    ]);

    $grandchild = Company::factory()->create([
        'name' => 'Grandchild Unit',
        'slug' => 'grandchild-unit',
        'type' => 'operating',
        'parent_id' => $child->id,
        'is_active' => true,
    ]);

    $independent = Company::factory()->create([
        'name' => 'Independent Corp',
        'slug' => 'independent-corp',
        'type' => 'holding',
        'is_active' => true,
    ]);

    $page = loginCompanyUser($admin)
        ->click('@nav-company-management')
        ->assertPathIs('/admin/companies');

    waitForCompanyTree($page, 15)
        ->click('@edit-company-link-'.$parent->id)
        ->assertPathIs('/admin/companies/'.$parent->id.'/edit')
        ->assertSee('Edit Company')
        ->assertSee($parent->name);

    // Assert cyclic protection: self, child, and grandchild must NOT be selectable parents
    $page->assertSourceInMissing('#parent_id', $parent->name)
        ->assertSourceInMissing('#parent_id', $child->name)
        ->assertSourceInMissing('#parent_id', $grandchild->name)
        ->assertSourceInHas('#parent_id', $independent->name);

    // Update company details
    $page->type('#name', 'Acme Corporation International')
        ->click('@update-company-submit-button')
        ->wait(2)
        ->assertPathIs('/admin/companies');

    waitForCompanyTree($page, 15)
        ->assertSee('Acme Corporation International');

    expect($parent->fresh()->name)->toBe('Acme Corporation International');
})->group('browser');

test('editing company fails with validation error for duplicate slug', function (): void {
    [$admin, $companyA] = createCompanyAdminUserWithCompany();

    $companyB = Company::factory()->create([
        'name' => 'Bravo Technologies',
        'slug' => 'bravo-tech',
        'is_active' => true,
    ]);

    $adminRoleId = Role::where('name', 'admin')->value('id');
    $admin->companies()->attach($companyB->id, ['status' => 'active']);
    $admin->companyRoles()->attach($adminRoleId, ['company_id' => $companyB->id]);

    loginCompanyUser($admin)
        ->navigate('/admin/companies/'.$companyB->id.'/edit')
        ->assertPathIs('/admin/companies/'.$companyB->id.'/edit')
        ->assertSee('Edit Company')
        ->assertSee('Bravo Technologies')
        // Attempt to rename slug to companyA's slug
        ->clear('#slug')
        ->type('#slug', $companyA->slug)
        ->click('@update-company-submit-button')
        ->wait(2)
        ->assertPathIs('/admin/companies/'.$companyB->id.'/edit');

    expect($companyB->fresh()->slug)->toBe('bravo-tech');
})->group('browser');

test('admin can assign an unassigned user with role and job title on the company edit page', function (): void {
    [$admin, $company] = createCompanyAdminUserWithCompany();

    $newUser = User::factory()->create([
        'name' => 'Dana Scully',
        'email' => 'dana.scully@example.com',
        'password' => 'password',
        'auth_method' => 'local',
    ]);

    $userRole = Role::where('name', 'user')->first();

    $page = loginCompanyUser($admin)
        ->navigate('/admin/companies/'.$company->id.'/edit')
        ->assertPathIs('/admin/companies/'.$company->id.'/edit')
        ->assertSee('Assign a User')
        // Select unassigned user
        ->select('select[name="user_id"]', (string) $newUser->id)
        // Check role
        ->check('input[value="'.$userRole->id.'"]')
        // Input job title
        ->type('input[name="job_title"]', 'Senior Field Investigator')
        ->click('@assign-user-submit-button')
        ->wait(2)
        ->assertPathIs('/admin/companies/'.$company->id.'/edit')
        ->assertSee('Dana Scully')
        ->assertSee('Senior Field Investigator');

    expect($newUser->companies()->where('company_id', $company->id)->exists())->toBeTrue();
})->group('browser');

test('already assigned users are excluded from the assign user dropdown on the company edit page', function (): void {
    [$admin, $company] = createCompanyAdminUserWithCompany();

    $unassignedUser = User::factory()->create([
        'name' => 'Bob Unassigned',
        'email' => 'bob.unassigned@example.com',
        'password' => 'password',
        'auth_method' => 'local',
    ]);

    // 1. With an unassigned user present, dropdown renders and excludes already assigned Alice Admin
    $page = loginCompanyUser($admin)
        ->navigate('/admin/companies/'.$company->id.'/edit')
        ->assertPathIs('/admin/companies/'.$company->id.'/edit')
        ->assertSee('Assign a User')
        ->assertSourceInHas('select[name="user_id"]', 'Bob Unassigned')
        ->assertSourceInMissing('select[name="user_id"]', 'Alice Admin');

    // 2. When all users are assigned to this company, assign form is hidden with notice
    $userRoleId = Role::where('name', 'user')->value('id');
    $unassignedUser->companies()->attach($company->id, ['status' => 'active']);
    $unassignedUser->companyRoles()->attach($userRoleId, ['company_id' => $company->id]);

    $page->refresh()
        ->assertPathIs('/admin/companies/'.$company->id.'/edit')
        ->assertSee('All users are already assigned to this company.')
        ->assertNotPresent('select[name="user_id"]')
        ->assertNotPresent('@assign-user-submit-button');
})->group('browser');

test('admin can remove a user from company and direct reportees cascade to company level', function (): void {
    [$admin, $company] = createCompanyAdminUserWithCompany();

    $manager = User::factory()->create([
        'name' => 'Fox Mulder',
        'email' => 'fox.mulder@example.com',
        'password' => 'password',
        'auth_method' => 'local',
    ]);

    $reportee = User::factory()->create([
        'name' => 'Walter Skinner',
        'email' => 'walter.skinner@example.com',
        'password' => 'password',
        'auth_method' => 'local',
    ]);

    $company->users()->attach($manager->id, [
        'status' => 'active',
        'job_title' => 'Special Agent',
    ]);

    $company->users()->attach($reportee->id, [
        'status' => 'active',
        'job_title' => 'Assistant Director',
        'manager_id' => $manager->id,
    ]);

    $page = loginCompanyUser($admin)
        ->navigate('/admin/companies/'.$company->id.'/edit')
        ->assertPathIs('/admin/companies/'.$company->id.'/edit')
        ->assertSee('Fox Mulder')
        ->assertSee('Walter Skinner');

    // Bypass browser confirm dialog for headless execution
    $page->script('window.confirm = () => true;');

    $page->click('@remove-company-user-button-'.$manager->id)
        ->wait(2)
        ->assertPathIs('/admin/companies/'.$company->id.'/edit')
        ->assertDontSee('Fox Mulder')
        ->assertSee('Walter Skinner');

    // Manager detached from company
    expect($manager->companies()->where('company_id', $company->id)->exists())->toBeFalse();

    // Reportee's manager_id reset to null
    $pivot = $reportee->companies()->where('company_id', $company->id)->first()?->pivot;
    expect($pivot->manager_id)->toBeNull();
})->group('browser');
