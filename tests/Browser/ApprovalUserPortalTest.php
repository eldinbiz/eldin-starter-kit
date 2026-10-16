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

if (! function_exists('createApprovalPortalFixtures')) {
    /**
     * Set up an active company with a manager reporting line, approval template, and users.
     *
     * @return array{
     *     company: Company,
     *     manager: User,
     *     employee: User,
     *     colleague: User,
     *     template: WorkflowTemplate
     * }
     */
    function createApprovalPortalFixtures(): array
    {
        $company = Company::factory()->create([
            'name' => 'Acme Corporation',
            'slug' => 'acme-corp',
            'is_active' => true,
        ]);

        Role::factory()->admin()->create();
        $userRole = Role::factory()->user()->create();

        $manager = User::factory()->create([
            'name' => 'Mary Manager',
            'email' => 'mary.manager@example.com',
            'password' => 'password',
            'auth_method' => 'local',
        ]);

        $employee = User::factory()->create([
            'name' => 'Evan Employee',
            'email' => 'evan.employee@example.com',
            'password' => 'password',
            'auth_method' => 'local',
        ]);

        $colleague = User::factory()->create([
            'name' => 'Colin Colleague',
            'email' => 'colin.colleague@example.com',
            'password' => 'password',
            'auth_method' => 'local',
        ]);

        // Attach to company with hierarchical reporting line: employee -> manager
        $company->users()->attach($manager->id, [
            'status' => 'active',
            'job_title' => 'Engineering Manager',
            'manager_id' => null,
        ]);
        $company->users()->attach($employee->id, [
            'status' => 'active',
            'job_title' => 'Software Engineer',
            'manager_id' => $manager->id,
        ]);
        $company->users()->attach($colleague->id, [
            'status' => 'active',
            'job_title' => 'Peer Reviewer',
            'manager_id' => $manager->id,
        ]);

        $manager->companyRoles()->attach($userRole->id, ['company_id' => $company->id]);
        $employee->companyRoles()->attach($userRole->id, ['company_id' => $company->id]);
        $colleague->companyRoles()->attach($userRole->id, ['company_id' => $company->id]);

        $template = WorkflowTemplate::factory()->create([
            'company_id' => $company->id,
            'name' => 'Standard Expense Workflow',
            'is_active' => true,
        ]);

        $group = WorkflowGroup::factory()->create([
            'template_id' => $template->id,
            'name' => 'Manager Review Group',
            'order' => 1,
            'type' => 'sequential',
        ]);

        WorkflowStep::factory()->create([
            'group_id' => $group->id,
            'label' => 'Direct Manager Review',
            'resolver_type' => ResolverType::NPlus1,
            'order' => 0,
        ]);

        return [
            'company' => $company,
            'manager' => $manager,
            'employee' => $employee,
            'colleague' => $colleague,
            'template' => $template,
        ];
    }
}

if (! function_exists('createRestrictedApprovalUser')) {
    /**
     * Create an active company and a user stripped of all approval permissions.
     *
     * @return array{0: User, 1: Company}
     */
    function createRestrictedApprovalUser(): array
    {
        $company = Company::factory()->create([
            'name' => 'Restricted Corp',
            'is_active' => true,
        ]);

        Role::factory()->admin()->create();
        $restrictedRole = Role::factory()->create([
            'name' => 'restricted',
            'permissions' => ['view-org-chart'],
        ]);

        $user = User::factory()->create([
            'name' => 'Restricted Rob',
            'email' => 'rob.restricted@example.com',
            'password' => 'password',
            'auth_method' => 'local',
        ]);

        $user->companies()->attach($company->id, [
            'status' => 'active',
            'job_title' => 'Guest Operator',
        ]);
        $user->companyRoles()->attach($restrictedRole->id, ['company_id' => $company->id]);

        return [$user, $company];
    }
}

if (! function_exists('loginApprovalPortalUser')) {
    /**
     * Authenticate a user via the UI login flow and land on dashboard.
     */
    function loginApprovalPortalUser(User $user): mixed
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

test('guest is redirected to login when accessing approval inbox', function (): void {
    visit('/approvals/inbox')
        ->assertPathIs('/login')
        ->assertSee('Sign In');
})->group('browser');

test('unauthorized user without approval permissions cannot see approval links and is denied direct access', function (): void {
    [$user, $company] = createRestrictedApprovalUser();

    loginApprovalPortalUser($user)
        ->assertMissing('.menu-item[href*="/approvals/inbox"]')
        ->assertMissing('.menu-item[href*="/approvals/history"]')
        ->assertMissing('.menu-item[href*="/approvals/requests"]')
        ->navigate('/approvals/inbox')
        ->assertSee('Access Denied')
        ->assertSee('You do not have permission to access this resource.');
})->group('browser');

// ---------------------------------------------------------------------------
// 2. Sidebar Navigation
// ---------------------------------------------------------------------------

test('user with approval permissions can navigate between inbox, history, and my requests via sidebar', function (): void {
    $fixtures = createApprovalPortalFixtures();

    loginApprovalPortalUser($fixtures['manager'])
        ->assertSee('APPROVALS')
        // Navigate to Inbox
        ->click('.menu-item[href*="/approvals/inbox"]')
        ->assertPathIs('/approvals/inbox')
        ->assertSee('Approval Inbox')
        ->assertSee('Review and process pending approval requests assigned to you.')
        // Navigate to History
        ->click('.menu-item[href*="/approvals/history"]')
        ->assertPathIs('/approvals/history')
        ->assertSee('Approval History')
        // Navigate to My Requests
        ->click('.menu-item[href*="/approvals/requests"]')
        ->assertPathIs('/approvals/requests')
        ->assertSee('My Approval Requests');
})->group('browser');

// ---------------------------------------------------------------------------
// 3. Approval Inbox: Empty State & Search Filter
// ---------------------------------------------------------------------------

test('approver sees empty state when zero approvals are pending', function (): void {
    $fixtures = createApprovalPortalFixtures();

    loginApprovalPortalUser($fixtures['manager'])
        ->navigate('/approvals/inbox')
        ->assertSee('Approval Inbox')
        ->assertSee('No approval items found')
        ->assertSee('You have zero pending approvals waiting for your review.');
})->group('browser');

test('approver sees pending request card and can filter by search query', function (): void {
    $fixtures = createApprovalPortalFixtures();

    $approvable = FakeApprovable::createFor($fixtures['employee'], $fixtures['company']);
    $request = app(ApprovalEngine::class)->submit($approvable, $fixtures['template']);

    loginApprovalPortalUser($fixtures['manager'])
        ->navigate('/approvals/inbox')
        ->assertSee('Approval Inbox')
        ->assertSee('Awaiting Your Action')
        ->assertSee('Direct Manager Review')
        ->assertSee('Evan Employee')
        ->assertSee("Fake Approvable #{$approvable->id}")
        // Livewire Debounced Search
        ->type('input[wire\:model\.live\.debounce\.300ms="search"]', 'Evan')
        ->wait(2)
        ->assertSee("Fake Approvable #{$approvable->id}")
        // Search for non-existent text
        ->type('input[wire\:model\.live\.debounce\.300ms="search"]', 'NonExistentXYZ999')
        ->wait(2)
        ->assertSee('No approval items found')
        ->assertDontSee("Fake Approvable #{$approvable->id}")
        // Clear search
        ->type('input[wire\:model\.live\.debounce\.300ms="search"]', '')
        ->wait(2)
        ->assertSee("Fake Approvable #{$approvable->id}");
})->group('browser');

// ---------------------------------------------------------------------------
// 4. Action Modals: Approve Step
// ---------------------------------------------------------------------------

test('approver can approve pending step with optional notes through interactive modal', function (): void {
    $fixtures = createApprovalPortalFixtures();

    $approvable = FakeApprovable::createFor($fixtures['employee'], $fixtures['company']);
    $request = app(ApprovalEngine::class)->submit($approvable, $fixtures['template']);

    loginApprovalPortalUser($fixtures['manager'])
        ->navigate('/approvals/inbox')
        ->assertSee("Fake Approvable #{$approvable->id}")
        // Click Approve button to open modal
        ->click('button[wire\:click*="openApproveModal"]')
        ->wait(1)
        ->assertSee('Confirm Step Approval')
        // Enter optional notes
        ->type('textarea[wire\:model="approvalNotes"]', 'Approved according to departmental budget policy.')
        // Confirm Approval
        ->click('button[wire\:click="approveStep"]')
        ->wait(2)
        // Assert modal closed and item removed from pending inbox
        ->assertSee('No approval items found');

    $request->refresh();
    expect($request->status)->toBe(ApprovalStatus::Approved);
})->group('browser');

// ---------------------------------------------------------------------------
// 5. Action Modals: Reject Step with Validation
// ---------------------------------------------------------------------------

test('approver must provide rejection reason and rejecting step updates request status', function (): void {
    $fixtures = createApprovalPortalFixtures();

    $approvable = FakeApprovable::createFor($fixtures['employee'], $fixtures['company']);
    $request = app(ApprovalEngine::class)->submit($approvable, $fixtures['template']);

    loginApprovalPortalUser($fixtures['manager'])
        ->navigate('/approvals/inbox')
        ->assertSee("Fake Approvable #{$approvable->id}")
        // Click Reject button
        ->click('button[wire\:click*="openRejectModal"]')
        ->wait(1)
        ->assertSee('Reject Approval Request')
        // Submit without reason -> validation error
        ->click('button[wire\:click="rejectStep"]')
        ->wait(1)
        ->assertSee('The rejection reason field is required.')
        // Provide rejection reason
        ->type('textarea[wire\:model="rejectionReason"]', 'Exceeds authorized limit for this quarter.')
        ->click('button[wire\:click="rejectStep"]')
        ->wait(2)
        ->assertSee('No approval items found');

    $request->refresh();
    expect($request->status)->toBe(ApprovalStatus::Rejected);
})->group('browser');

// ---------------------------------------------------------------------------
// 6. Action Modals: Delegate Step to Colleague
// ---------------------------------------------------------------------------

test('approver can delegate pending step to another company colleague', function (): void {
    $fixtures = createApprovalPortalFixtures();

    $approvable = FakeApprovable::createFor($fixtures['employee'], $fixtures['company']);
    $request = app(ApprovalEngine::class)->submit($approvable, $fixtures['template']);

    loginApprovalPortalUser($fixtures['manager'])
        ->navigate('/approvals/inbox')
        ->assertSee("Fake Approvable #{$approvable->id}")
        // Open Delegate modal
        ->click('button[wire\:click*="openDelegateModal"]')
        ->wait(1)
        ->assertSee('Delegate Approval Step')
        // Select Colleague
        ->select('select[wire\:model="delegateUserId"]', (string) $fixtures['colleague']->id)
        ->click('button[wire\:click="delegateStep"]')
        ->wait(2)
        ->assertSee('No approval items found');

    $step = $request->steps()->first();
    $step->refresh();
    expect($step->approver_id)->toBe($fixtures['colleague']->id)
        ->and($step->delegated_from_id)->toBe($fixtures['manager']->id);
})->group('browser');

// ---------------------------------------------------------------------------
// 7. Approval History
// ---------------------------------------------------------------------------

test('approver can view historical decisions with audit badges and notes', function (): void {
    $fixtures = createApprovalPortalFixtures();

    $approvable = FakeApprovable::createFor($fixtures['employee'], $fixtures['company']);
    $request = app(ApprovalEngine::class)->submit($approvable, $fixtures['template']);
    $step = $request->steps()->first();

    // Mark step as approved by manager
    $step->update([
        'state' => StepState::Approved,
        'action_at' => now(),
        'notes' => 'Authorized by department lead.',
    ]);
    $request->update(['status' => ApprovalStatus::Approved]);

    loginApprovalPortalUser($fixtures['manager'])
        ->navigate('/approvals/history')
        ->assertSee('Approval History')
        ->assertSee("Fake Approvable #{$approvable->id}")
        ->assertSee('Approved by You')
        ->assertSee('Authorized by department lead.')
        ->assertSee('Evan Employee');
})->group('browser');

// ---------------------------------------------------------------------------
// 8. My Requests Tab & Cancellation Flow
// ---------------------------------------------------------------------------

test('requester can view their submitted requests and cancel pending ones', function (): void {
    $fixtures = createApprovalPortalFixtures();

    $approvable = FakeApprovable::createFor($fixtures['employee'], $fixtures['company']);
    $request = app(ApprovalEngine::class)->submit($approvable, $fixtures['template']);

    loginApprovalPortalUser($fixtures['employee'])
        ->navigate('/approvals/requests')
        ->assertSee('My Approval Requests')
        ->assertSee("Fake Approvable #{$approvable->id}")
        ->assertSee('In Progress')
        ->assertSee('Cancel Request')
        // Cancel request
        ->click('button[wire\:click*="openCancelModal"]')
        ->wait(1)
        ->assertSee('Cancel Approval Request')
        ->click('button[wire\:click="cancelRequest"]')
        ->wait(2)
        ->assertSee('Canceled')
        ->assertDontSee('Cancel Request');

    $request->refresh();
    expect($request->status)->toBe(ApprovalStatus::Canceled);
})->group('browser');

// ---------------------------------------------------------------------------
// 9. Request Detail Viewer & Visual Step States
// ---------------------------------------------------------------------------

test('user can view request details with workflow progress timeline and execute inline approval', function (): void {
    $fixtures = createApprovalPortalFixtures();

    $approvable = FakeApprovable::createFor($fixtures['employee'], $fixtures['company']);
    $request = app(ApprovalEngine::class)->submit($approvable, $fixtures['template']);

    loginApprovalPortalUser($fixtures['manager'])
        ->navigate('/approvals/'.$request->id)
        ->assertSee('Workflow Progress & Step States')
        ->assertSee('You are the current approver.')
        ->assertSee('Manager Review Group')
        ->assertSee('Direct Manager Review')
        ->assertSee('Pending')
        // Inline Approve button on detail page
        ->click('button[wire\:click*="openApprove"]')
        ->wait(1)
        ->assertSee('Confirm Step Approval')
        ->click('button[wire\:click="approve"]')
        ->wait(2)
        ->assertSee('Approved');

    $request->refresh();
    expect($request->status)->toBe(ApprovalStatus::Approved);
})->group('browser');

// ---------------------------------------------------------------------------
// 10. Multi-Tenant Company Isolation
// ---------------------------------------------------------------------------

test('user from another company cannot view approval request belonging to different company', function (): void {
    $fixtures = createApprovalPortalFixtures();

    $approvable = FakeApprovable::createFor($fixtures['employee'], $fixtures['company']);
    $request = app(ApprovalEngine::class)->submit($approvable, $fixtures['template']);

    // Another company and user
    $otherCompany = Company::factory()->create(['name' => 'Foreign Corp', 'is_active' => true]);
    $userRole = Role::where('name', 'user')->first();

    $foreignUser = User::factory()->create([
        'email' => 'foreign.user@example.com',
        'password' => 'password',
        'auth_method' => 'local',
    ]);
    $foreignUser->companies()->attach($otherCompany->id, ['status' => 'active']);
    $foreignUser->companyRoles()->attach($userRole->id, ['company_id' => $otherCompany->id]);

    loginApprovalPortalUser($foreignUser)
        ->navigate('/approvals/'.$request->id)
        ->assertSee('Access Denied');
})->group('browser');
