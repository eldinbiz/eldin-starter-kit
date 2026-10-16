<?php

declare(strict_types=1);

use App\Enums\ApprovalStatus;
use App\Enums\ResolverType;
use App\Enums\StepState;
use App\Models\Approval\ApprovalRequest;
use App\Models\Approval\ApprovalRequestStep;
use App\Models\Approval\WorkflowGroup;
use App\Models\Approval\WorkflowStep;
use App\Models\Approval\WorkflowTemplate;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use App\Services\ApprovalEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FakeApprovable;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

if (! function_exists('createApprovalAdminFixtures')) {
    /**
     * Set up an active company with an admin user, regular staff, and workflow template.
     *
     * @return array{
     *     company: Company,
     *     admin: User,
     *     staff: User,
     *     colleague: User,
     *     template: WorkflowTemplate
     * }
     */
    function createApprovalAdminFixtures(): array
    {
        $company = Company::factory()->create([
            'name' => 'Acme Corporation',
            'slug' => 'acme-corp',
            'is_active' => true,
        ]);

        $adminRole = Role::factory()->admin()->create();
        $userRole = Role::factory()->user()->create();

        $admin = User::factory()->create([
            'name' => 'Alice Admin',
            'email' => 'alice.admin@example.com',
            'password' => 'password',
            'auth_method' => 'local',
        ]);

        $staff = User::factory()->create([
            'name' => 'Sam Staff',
            'email' => 'sam.staff@example.com',
            'password' => 'password',
            'auth_method' => 'local',
        ]);

        $colleague = User::factory()->create([
            'name' => 'Carol Colleague',
            'email' => 'carol.colleague@example.com',
            'password' => 'password',
            'auth_method' => 'local',
        ]);

        $admin->companies()->attach($company->id, [
            'status' => 'active',
            'job_title' => 'System Administrator',
        ]);
        $staff->companies()->attach($company->id, [
            'status' => 'active',
            'job_title' => 'Operations Analyst',
            'manager_id' => $admin->id,
        ]);
        $colleague->companies()->attach($company->id, [
            'status' => 'active',
            'job_title' => 'Finance Analyst',
        ]);

        $admin->companyRoles()->attach($adminRole->id, ['company_id' => $company->id]);
        $staff->companyRoles()->attach($userRole->id, ['company_id' => $company->id]);
        $colleague->companyRoles()->attach($userRole->id, ['company_id' => $company->id]);

        $template = WorkflowTemplate::factory()->create([
            'company_id' => $company->id,
            'name' => 'General Purchase Template',
            'description' => 'Standard template for organizational expenses',
            'is_active' => true,
        ]);

        $group = WorkflowGroup::factory()->create([
            'template_id' => $template->id,
            'name' => 'Manager Group',
            'order' => 1,
            'type' => 'sequential',
        ]);

        WorkflowStep::factory()->create([
            'group_id' => $group->id,
            'label' => 'Primary Approval',
            'resolver_type' => ResolverType::NPlus1,
            'order' => 0,
        ]);

        return [
            'company' => $company,
            'admin' => $admin,
            'staff' => $staff,
            'colleague' => $colleague,
            'template' => $template,
        ];
    }
}

if (! function_exists('loginApprovalAdminUser')) {
    /**
     * Authenticate an admin user via the UI login flow and land on dashboard.
     */
    function loginApprovalAdminUser(User $user): mixed
    {
        session()->forget('url.intended');

        return visit('/login')
            ->assertSee('Sign In')
            ->type('#email', $user->email)
            ->click('@next-button')
            ->type('#password', 'password')
            ->click('@login-button')
            ->assertPathIs('/')
            ->wait(3)
            ->assertSee('Dashboard');
    }
}

// ---------------------------------------------------------------------------
// 1. Authorization & Access Control
// ---------------------------------------------------------------------------

test('regular user without workflow permissions is denied access to admin workflow management', function (): void {
    $fixtures = createApprovalAdminFixtures();

    loginApprovalAdminUser($fixtures['staff'])
        ->navigate('/admin/approval-workflows')
        ->assertSee('Access Denied')
        ->assertSee('You do not have permission to access this resource.')
        ->navigate('/admin/approval-management')
        ->assertSee('Access Denied');
})->group('browser');

// ---------------------------------------------------------------------------
// 2. Sidebar Navigation for Admin
// ---------------------------------------------------------------------------

test('admin can navigate to workflow templates and approval management from sidebar', function (): void {
    $fixtures = createApprovalAdminFixtures();

    loginApprovalAdminUser($fixtures['admin'])
        ->assertSee('APPROVALS')
        // Navigate to Workflow Templates
        ->click('a[href*="/admin/approval-workflows"]')
        ->assertPathIs('/admin/approval-workflows')
        ->assertSee('Workflow Templates')
        ->assertSee('Configure multi-step approval workflow templates and routing rules.')
        // Navigate to Manage Approvals
        ->click('a[href*="/admin/approval-management"]')
        ->assertPathIs('/admin/approval-management')
        ->assertSee('Manage Approvals');
})->group('browser');

// ---------------------------------------------------------------------------
// 3. Workflow Templates Listing, Search & Status Toggle
// ---------------------------------------------------------------------------

test('admin can search workflow templates and toggle active/disabled status', function (): void {
    $fixtures = createApprovalAdminFixtures();

    // Create a second template
    WorkflowTemplate::factory()->create([
        'company_id' => $fixtures['company']->id,
        'name' => 'Capex Investment Workflow',
        'is_active' => true,
    ]);

    loginApprovalAdminUser($fixtures['admin'])
        ->navigate('/admin/approval-workflows')
        ->assertSee('Workflow Templates')
        ->assertSee('General Purchase Template')
        ->assertSee('Capex Investment Workflow')
        // Search for "Capex"
        ->type('input[wire\:model\.live\.debounce\.300ms="search"]', 'Capex')
        ->wait(2)
        ->assertSee('Capex Investment Workflow')
        ->assertDontSee('General Purchase Template')
        // Clear search
        ->type('input[wire\:model\.live\.debounce\.300ms="search"]', '')
        ->wait(2)
        ->assertSee('General Purchase Template')
        ->assertSee('Capex Investment Workflow')
        // Toggle Active status to Disabled
        ->click('button[wire\:click="toggleStatus('.$fixtures['template']->id.')"]')
        ->wait(2)
        ->assertSee('Disabled');
})->group('browser');

// ---------------------------------------------------------------------------
// 4. Workflow Template Soft-Delete, Trash & Restore
// ---------------------------------------------------------------------------

test('admin can soft delete a workflow template and restore it from trashed view', function (): void {
    $fixtures = createApprovalAdminFixtures();

    loginApprovalAdminUser($fixtures['admin'])
        ->navigate('/admin/approval-workflows')
        ->assertSee('General Purchase Template')
        // Must disable template before it can be trashed
        ->click('button[wire\:click="toggleStatus('.$fixtures['template']->id.')"]')
        ->wait(2)
        ->assertSee('Disabled')
        // Click trash action
        ->click('button:has-text("Trash")')
        ->wait(1)
        ->assertSee('Trash Workflow Template')
        ->click('button:has-text("Confirm")')
        ->wait(2)
        ->assertDontSee('General Purchase Template')
        ->assertSee('No workflow templates found')
        // Navigate to trashed templates
        ->navigate('/admin/approval-workflows?trashed=1')
        ->assertSee('General Purchase Template')
        ->assertSee('Trashed')
        // Restore template
        ->click('button:has-text("Restore")')
        ->wait(2)
        // Return to active templates
        ->navigate('/admin/approval-workflows')
        ->assertSee('General Purchase Template');
})->group('browser');

// ---------------------------------------------------------------------------
// 5. Workflow Template Builder: Create Multi-Group Multi-Step Template
// ---------------------------------------------------------------------------

test('admin can build and save a multi-tier approval workflow template via builder', function (): void {
    $fixtures = createApprovalAdminFixtures();

    loginApprovalAdminUser($fixtures['admin'])
        ->navigate('/admin/approval-workflows/create')
        ->assertSee('New Workflow Template')
        ->assertSee('Define multi-tier approval steps, approver resolvers, and execution hierarchy.')
        // Attempt empty submission -> validation error
        ->click('button:has-text("Create Workflow Template")')
        ->wait(1)
        ->assertSee('The name field is required.')
        // Fill Template Details
        ->type('input[wire\:model="name"]', 'Capital Expenditure Procurement')
        ->type('textarea[wire\:model="description"]', 'Multi-tier executive and departmental approvals for heavy assets.')
        // Scope radio
        ->check('input[value="this_company"]')
        // Group 1: Rename group
        ->type('input[wire\:model="groups.0.name"]', 'Department Head Review')
        // Click Add Step Group
        ->click('button:has-text("Add Step Group")')
        ->wait(1)
        ->assertSee('Group 2')
        // Rename Group 2
        ->type('input[wire\:model="groups.1.name"]', 'Finance Committee Review')
        // Set Group 2 mode to Parallel
        ->select('select[wire\:model\.live="groups.1.type"]', 'parallel')
        ->wait(1)
        ->assertSee('Min Approvals:')
        // Submit form
        ->click('button:has-text("Create Workflow Template")')
        ->wait(3)
        // Assert redirected to index with template listed
        ->assertPathIs('/admin/approval-workflows')
        ->assertSee('Capital Expenditure Procurement');

    $created = WorkflowTemplate::where('name', 'Capital Expenditure Procurement')->first();
    expect($created)->not->toBeNull()
        ->and($created->groups()->count())->toBe(2);
})->group('browser');

// ---------------------------------------------------------------------------
// 6. Workflow Template Editor: Update Existing Template
// ---------------------------------------------------------------------------

test('admin can edit an existing workflow template and update its configuration', function (): void {
    $fixtures = createApprovalAdminFixtures();

    loginApprovalAdminUser($fixtures['admin'])
        ->navigate('/admin/approval-workflows/'.$fixtures['template']->id.'/edit')
        ->assertSee('Edit Workflow Template')
        ->assertValue('input[wire\:model="name"]', 'General Purchase Template')
        // Update name
        ->type('input[wire\:model="name"]', 'Updated General Purchase Template')
        // Save
        ->click('button:has-text("Update Workflow Template")')
        ->wait(3)
        ->assertPathIs('/admin/approval-workflows')
        ->assertSee('Updated General Purchase Template');

    $fixtures['template']->refresh();
    expect($fixtures['template']->name)->toBe('Updated General Purchase Template');
})->group('browser');

// ---------------------------------------------------------------------------
// 7. Central Administrative Approval Management Oversight
// ---------------------------------------------------------------------------

test('admin can view centralized approval management table and filter records', function (): void {
    $fixtures = createApprovalAdminFixtures();

    $approvable = FakeApprovable::createFor($fixtures['staff'], $fixtures['company']);
    $request = app(ApprovalEngine::class)->submit($approvable, $fixtures['template']);

    loginApprovalAdminUser($fixtures['admin'])
        ->navigate('/admin/approval-management')
        ->assertSee('Manage Approvals')
        ->assertSee('Sam Staff')
        ->assertSee('Primary Approval')
        ->assertSee('Pending')
        // Filter by Step State
        ->click('@datatable-filter-toggle')
        ->wait(1)
        ->assertSee('Step State')
        ->select('select[wire\:model\.live="filterValues.step_state"]', 'pending')
        ->wait(2)
        ->assertSee('Sam Staff');
})->group('browser');

// ---------------------------------------------------------------------------
// 8. Admin Override: Delegate Stuck Step
// ---------------------------------------------------------------------------

test('admin can reassign an active step to a different approver via admin oversight', function (): void {
    $fixtures = createApprovalAdminFixtures();

    $approvable = FakeApprovable::createFor($fixtures['staff'], $fixtures['company']);
    $request = app(ApprovalEngine::class)->submit($approvable, $fixtures['template']);
    $step = $request->steps()->first();

    loginApprovalAdminUser($fixtures['admin'])
        ->navigate('/admin/approval-management')
        ->assertSee('Manage Approvals')
        ->assertSee('Primary Approval')
        // Click Delegate button in table row
        ->click('table button[wire\:click*="openDelegateModal"]')
        ->wait(1)
        ->assertSee('Reassign Approver')
        // Select Colleague
        ->select('select[wire\:model\.live="selectedApproverId"]', (string) $fixtures['colleague']->id)
        ->wait(1)
        ->click('button[wire\:click="delegate"]')
        ->wait(2)
        ->assertSee('Carol Colleague');

    $step->refresh();
    expect($step->approver_id)->toBe($fixtures['colleague']->id);
})->group('browser');

// ---------------------------------------------------------------------------
// 9. Admin Override: Skip Step
// ---------------------------------------------------------------------------

test('admin can force-skip an approval step to advance the workflow', function (): void {
    $fixtures = createApprovalAdminFixtures();

    $approvable = FakeApprovable::createFor($fixtures['staff'], $fixtures['company']);
    $request = app(ApprovalEngine::class)->submit($approvable, $fixtures['template']);
    $step = $request->steps()->first();

    loginApprovalAdminUser($fixtures['admin'])
        ->navigate('/admin/approval-management')
        ->assertSee('Manage Approvals')
        ->assertSee('Primary Approval')
        // Click Skip button
        ->click('table button[wire\:click*="openSkipModal"]')
        ->wait(1)
        ->assertSee('Skip Step')
        ->click('button[wire\:click="skip"]')
        ->wait(2)
        ->assertSee('Skipped');

    $step->refresh();
    expect($step->state)->toBe(StepState::Skipped);
})->group('browser');
