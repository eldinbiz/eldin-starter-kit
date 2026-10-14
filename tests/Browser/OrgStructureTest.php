<?php

declare(strict_types=1);

use App\Actions\OrgChart\GetOrgChartTreeAction;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

if (! function_exists('createOrgHierarchyScenario')) {
    /**
     * Helper to create an active company and a multi-level organizational reporting hierarchy.
     *
     * Hierarchy structure:
     * - Alice CEO (Root, SysAdmin, manage-org-chart)
     *   ├── Bob Director (Director of Engineering, reports to CEO)
     *   │   └── David Senior (Senior Developer, reports to Bob Director)
     *   └── Carol Director (Director of Product, reports to CEO)
     *
     * @return array{
     *     company: Company,
     *     ceo: User,
     *     engDirector: User,
     *     prodDirector: User,
     *     seniorDev: User
     * }
     */
    function createOrgHierarchyScenario(): array
    {
        $company = Company::factory()->create([
            'name' => 'Acme Global',
            'slug' => 'acme-global',
            'is_active' => true,
        ]);

        $adminRole = Role::factory()->admin()->create();
        $userRole = Role::factory()->user()->create();

        // 1. CEO / Platform Admin (Root level)
        $ceo = User::factory()->create([
            'name' => 'Alice CEO',
            'email' => 'alice.ceo@example.com',
            'password' => 'password',
            'auth_method' => 'local',
        ]);
        $ceo->companies()->attach($company->id, [
            'status' => 'active',
            'job_title' => 'Chief Executive Officer',
            'manager_id' => null,
        ]);
        $ceo->companyRoles()->attach($adminRole->id, [
            'company_id' => $company->id,
        ]);

        // 2. Engineering Director (Reports to CEO)
        $engDirector = User::factory()->create([
            'name' => 'Bob Director',
            'email' => 'bob.director@example.com',
            'password' => 'password',
            'auth_method' => 'local',
        ]);
        $engDirector->companies()->attach($company->id, [
            'status' => 'active',
            'job_title' => 'Director of Engineering',
            'manager_id' => $ceo->id,
        ]);
        $engDirector->companyRoles()->attach($adminRole->id, [
            'company_id' => $company->id,
        ]);

        // 3. Product Director (Reports to CEO - separate branch)
        $prodDirector = User::factory()->create([
            'name' => 'Carol Director',
            'email' => 'carol.director@example.com',
            'password' => 'password',
            'auth_method' => 'local',
        ]);
        $prodDirector->companies()->attach($company->id, [
            'status' => 'active',
            'job_title' => 'Director of Product',
            'manager_id' => $ceo->id,
        ]);
        $prodDirector->companyRoles()->attach($userRole->id, [
            'company_id' => $company->id,
        ]);

        // 4. Senior Developer (Reports to Bob Director - descendant of Bob)
        $seniorDev = User::factory()->create([
            'name' => 'David Senior',
            'email' => 'david.senior@example.com',
            'password' => 'password',
            'auth_method' => 'local',
        ]);
        $seniorDev->companies()->attach($company->id, [
            'status' => 'active',
            'job_title' => 'Senior Developer',
            'manager_id' => $engDirector->id,
        ]);
        $seniorDev->companyRoles()->attach($userRole->id, [
            'company_id' => $company->id,
        ]);

        return [
            'company' => $company,
            'ceo' => $ceo,
            'engDirector' => $engDirector,
            'prodDirector' => $prodDirector,
            'seniorDev' => $seniorDev,
        ];
    }
}

if (! function_exists('createOrgViewerUserWithHierarchy')) {
    /**
     * Helper to create a user with view-only org-chart permission.
     *
     * @return array{0: User, 1: Company}
     */
    function createOrgViewerUserWithHierarchy(): array
    {
        $company = Company::factory()->create([
            'name' => 'Acme Global',
            'slug' => 'acme-global',
            'is_active' => true,
        ]);

        $viewerRole = Role::factory()->create([
            'name' => 'viewer',
            'permissions' => ['view-org-chart'],
        ]);

        $viewer = User::factory()->create([
            'name' => 'Victor Viewer',
            'email' => 'victor.viewer@example.com',
            'password' => 'password',
            'auth_method' => 'local',
        ]);
        $viewer->companies()->attach($company->id, [
            'status' => 'active',
            'job_title' => 'Auditor',
            'manager_id' => null,
        ]);
        $viewer->companyRoles()->attach($viewerRole->id, [
            'company_id' => $company->id,
        ]);

        return [$viewer, $company];
    }
}

if (! function_exists('createOrgUnauthorizedUser')) {
    /**
     * Helper to create a user without any org chart permissions.
     *
     * @return array{0: User, 1: Company}
     */
    function createOrgUnauthorizedUser(): array
    {
        $company = Company::factory()->create([
            'name' => 'Acme Global',
            'slug' => 'acme-global',
            'is_active' => true,
        ]);

        $emptyRole = Role::factory()->create([
            'name' => 'unauthorized',
            'permissions' => [],
        ]);

        $user = User::factory()->create([
            'name' => 'No Perms User',
            'email' => 'no.perms@example.com',
            'password' => 'password',
            'auth_method' => 'local',
        ]);
        $user->companies()->attach($company->id, [
            'status' => 'active',
            'job_title' => 'Staff Member',
            'manager_id' => null,
        ]);
        $user->companyRoles()->attach($emptyRole->id, [
            'company_id' => $company->id,
        ]);

        return [$user, $company];
    }
}

if (! function_exists('loginOrgUser')) {
    /**
     * Helper to authenticate through the SPA login form.
     */
    function loginOrgUser(User $user): mixed
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

if (! function_exists('waitForOrgTree')) {
    /**
     * Helper to wait for the D3 organization chart SVG container and nodes to render.
     */
    function waitForOrgTree(mixed $page, int|float $seconds = 3): mixed
    {
        return $page
            ->wait($seconds)
            ->assertDontSee('Loading organization chart...')
            ->assertPresent('#org-chart-container svg')
            ->assertPresent('[data-user-id]');
    }
}

// ---------------------------------------------------------------------------
// 1. Authorization & Access Control
// ---------------------------------------------------------------------------

test('guest is redirected to login when accessing org chart', function (): void {
    visit('/admin/org-chart')
        ->assertPathIs('/login')
        ->assertSee('Sign In');
})->group('browser');

test('unauthorized user without org chart permissions cannot see sidebar link and is denied direct access', function (): void {
    [$user, $company] = createOrgUnauthorizedUser();

    loginOrgUser($user)
        ->assertNotPresent('@nav-org-structure')
        ->navigate('/admin/org-chart')
        ->assertSee('Access Denied');
})->group('browser');

test('viewer with view-only permissions can view tree but cannot see edit buttons', function (): void {
    [$viewer, $company] = createOrgViewerUserWithHierarchy();

    $page = loginOrgUser($viewer)
        ->click('@nav-org-structure')
        ->assertPathIs('/admin/org-chart')
        ->assertSee('Organization Chart');

    waitForOrgTree($page, 3)
        ->assertSee('Victor Viewer')
        ->assertNotPresent('@edit-node-button-'.$viewer->id);
})->group('browser');

// ---------------------------------------------------------------------------
// 2. Sidebar Navigation
// ---------------------------------------------------------------------------

test('admin can navigate to org structure from administration sidebar', function (): void {
    $data = createOrgHierarchyScenario();

    loginOrgUser($data['ceo'])
        ->click('@nav-org-structure')
        ->assertPathIs('/admin/org-chart')
        ->assertSee('Organization Chart')
        ->assertSee('Acme Global');
})->group('browser');

// ---------------------------------------------------------------------------
// 3. Hierarchy Tree Canvas & Real-time Search
// ---------------------------------------------------------------------------

test('admin can view hierarchy tree nodes and synthetic company root', function (): void {
    $data = createOrgHierarchyScenario();

    $page = loginOrgUser($data['ceo'])
        ->click('@nav-org-structure')
        ->assertPathIs('/admin/org-chart');

    waitForOrgTree($page, 3)
        ->assertSee('Alice CEO')
        ->assertSee('Bob Director')
        ->assertSee('Carol Director')
        ->assertSee('David Senior');
})->group('browser');

test('admin can filter tree nodes in real time with search query and dim non-matching nodes', function (): void {
    $data = createOrgHierarchyScenario();

    $page = loginOrgUser($data['ceo'])
        ->click('@nav-org-structure')
        ->assertPathIs('/admin/org-chart');

    waitForOrgTree($page, 3)
        ->typeSlowly('@org-chart-search-input', 'David')
        ->wait(1);

    $davidId = $data['seniorDev']->id;
    $bobId = $data['engDirector']->id;

    // Matching card retains opacity 1, non-matching card is dimmed to 0.2
    $page->assertScript("document.querySelector('[data-user-id=\"{$davidId}\"]')?.style.opacity === '1'");
    $page->assertScript("document.querySelector('[data-user-id=\"{$bobId}\"]')?.style.opacity === '0.2'");

    // Clearing search restores all cards to full opacity
    $page->type('@org-chart-search-input', '')
        ->wait(1);

    $page->assertScript("document.querySelector('[data-user-id=\"{$davidId}\"]')?.style.opacity === '1'");
    $page->assertScript("document.querySelector('[data-user-id=\"{$bobId}\"]')?.style.opacity === '1'");
})->group('browser');

test('admin can click reset view button to reinitialize chart', function (): void {
    $data = createOrgHierarchyScenario();

    $page = loginOrgUser($data['ceo'])
        ->click('@nav-org-structure')
        ->assertPathIs('/admin/org-chart');

    waitForOrgTree($page, 3)
        ->click('@org-chart-reset-button')
        ->wait(2)
        ->assertPresent('#org-chart-container svg');
})->group('browser');

// ---------------------------------------------------------------------------
// 4. Moving Employees & Reassigning Reporting Lines
// ---------------------------------------------------------------------------

test('admin can reassign employee to a different manager (lateral move)', function (): void {
    $data = createOrgHierarchyScenario();
    $davidId = $data['seniorDev']->id;
    $carolId = $data['prodDirector']->id;

    $page = loginOrgUser($data['ceo'])
        ->click('@nav-org-structure')
        ->assertPathIs('/admin/org-chart');

    waitForOrgTree($page, 3);

    // Open edit modal for David Senior (currently reporting to Bob Director)
    $page->assertPresent('@edit-node-button-'.$davidId);
    $page->script("document.querySelector('[data-testid=\"edit-node-button-{$davidId}\"]')?.click()");

    $page->assertPresent('@edit-employee-modal')
        ->assertSee('Editing: David Senior')
        // Update job title
        ->type('@edit-job-title-input', 'Principal Architect')
        // Select Carol Director as new manager (lateral move)
        ->click('@candidate-manager-'.$carolId)
        ->click('@save-employee-button')
        ->wait(2)
        ->assertPresent('#org-chart-container svg')
        ->assertPresent('[data-user-id]');

    // Verify database pivot was updated with new manager and job title
    $pivot = $data['company']->users()->where('users.id', $davidId)->first()->pivot;
    expect($pivot->manager_id)->toBe($carolId)
        ->and($pivot->job_title)->toBe('Principal Architect');
})->group('browser');

test('admin can remove manager to decouple employee to root-level', function (): void {
    $data = createOrgHierarchyScenario();
    $bobId = $data['engDirector']->id;

    $page = loginOrgUser($data['ceo'])
        ->click('@nav-org-structure')
        ->assertPathIs('/admin/org-chart');

    waitForOrgTree($page, 3);

    // Open edit modal for Bob Director (currently reporting to CEO)
    $page->click('@edit-node-button-'.$bobId)
        ->assertPresent('@edit-employee-modal')
        ->assertSee('Editing: Bob Director')
        // Click remove manager option to promote to root-level
        ->click('@remove-manager-button')
        ->click('@save-employee-button')
        ->wait(2)
        ->assertPresent('#org-chart-container svg')
        ->assertPresent('[data-user-id]');

    // Verify database pivot reflects manager_id as null
    $pivot = $data['company']->users()->where('users.id', $bobId)->first()->pivot;
    expect($pivot->manager_id)->toBeNull();
})->group('browser');

// ---------------------------------------------------------------------------
// 5. Cycle Prevention & 'Will Be Promoted' Scenario
// ---------------------------------------------------------------------------

test('admin reassigning supervisor to subordinate sees will be promoted badge and auto-promotes subordinate to prevent cycle', function (): void {
    $data = createOrgHierarchyScenario();
    $bobId = $data['engDirector']->id;
    $davidId = $data['seniorDev']->id;

    $page = loginOrgUser($data['ceo'])
        ->click('@nav-org-structure')
        ->assertPathIs('/admin/org-chart');

    waitForOrgTree($page, 3);

    // Open edit modal for supervisor Bob Director
    $page->click('@edit-node-button-'.$bobId)
        ->assertPresent('@edit-employee-modal')
        ->assertSee('Editing: Bob Director')
        ->assertPresent('@candidate-manager-'.$davidId);

    // Assert that subordinate candidate displays "will be promoted" badge
    $page->assertPresent('@badge-will-be-promoted')
        ->assertSee('will be promoted');

    // Select subordinate (David Senior) as the new manager of Bob Director
    $page->click('@candidate-manager-'.$davidId)
        ->click('@save-employee-button')
        ->wait(2)
        ->assertPresent('#org-chart-container svg')
        ->assertPresent('[data-user-id]');

    // Verify auto-promotion in database pivot:
    // 1. Subordinate David Senior was promoted to root (manager_id = null)
    // 2. Supervisor Bob Director now reports to David Senior (manager_id = David's ID)
    $davidPivot = $data['company']->users()->where('users.id', $davidId)->first()->pivot;
    $bobPivot = $data['company']->users()->where('users.id', $bobId)->first()->pivot;

    expect($davidPivot->manager_id)->toBeNull()
        ->and($bobPivot->manager_id)->toBe($davidId);

    // Assert tree re-renders without breaking
    $page->assertPresent('#org-chart-container svg')
        ->assertPresent('[data-user-id]');
})->group('browser');

// ---------------------------------------------------------------------------
// 6. Empty State
// ---------------------------------------------------------------------------

test('admin sees empty state message when company has no members', function (): void {
    $data = createOrgHierarchyScenario();

    GetOrgChartTreeAction::$fakeResolver = fn (\App\Models\Company $company): array => [
        'company' => [
            'id' => $company->id,
            'name' => $company->name,
        ],
        'users' => collect([]),
    ];

    try {
        loginOrgUser($data['ceo'])
            ->click('@nav-org-structure')
            ->assertPathIs('/admin/org-chart')
            ->wait(2)
            ->assertSee('No organization structure found')
            ->assertSee('Assign users to this company and set reporting lines to build the chart.');
    } finally {
        GetOrgChartTreeAction::$fakeResolver = null;
    }
})->group('browser');
