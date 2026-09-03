<?php

declare(strict_types=1);

use App\Enums\GroupType;
use App\Enums\StepState;
use App\Models\Approval\WorkflowGroup;
use App\Models\Approval\WorkflowStep;
use App\Models\Approval\WorkflowTemplate;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use App\Services\ApprovalEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FakeApprovable;
use Tests\TestCase;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function attachUserRoleIfMissing(User $user, Company $company): void
{
    if ($user->companyRoles()->wherePivot('company_id', $company->id)->doesntExist()) {
        $userRole = Role::where('name', 'user')->first() ?? Role::factory()->user()->create();
        $user->companyRoles()->attach($userRole->id, ['company_id' => $company->id]);
    }
}

function createApprovalSetup(): array
{
    $company = Company::factory()->create(['is_active' => true]);

    $requestor = User::factory()->create();
    $manager = User::factory()->create();

    $company->users()->attach($requestor->id, ['manager_id' => $manager->id, 'status' => 'active']);
    $company->users()->attach($manager->id, ['manager_id' => null, 'status' => 'active']);

    attachUserRoleIfMissing($requestor, $company);
    attachUserRoleIfMissing($manager, $company);

    $template = WorkflowTemplate::factory()->create(['company_id' => $company->id, 'name' => 'Test Workflow']);
    $group = WorkflowGroup::factory()->create(['template_id' => $template->id, 'name' => 'Group 1', 'order' => 0]);
    WorkflowStep::factory()->nPlus1()->create(['group_id' => $group->id, 'label' => 'Manager Approval', 'order' => 0]);

    $approvable = FakeApprovable::createFor($requestor, $company);
    $request = app(ApprovalEngine::class)->submit($approvable, $template);
    $step = $request->steps()->first();

    return [$company, $requestor, $manager, $template, $request, $step, $approvable];
}

function createAwaitingPreviousSetup(): array
{
    $company = Company::factory()->create(['is_active' => true]);

    $requestor = User::factory()->create();
    $manager = User::factory()->create();

    $company->users()->attach($requestor->id, ['manager_id' => $manager->id, 'status' => 'active']);
    $company->users()->attach($manager->id, ['manager_id' => null, 'status' => 'active']);

    attachUserRoleIfMissing($requestor, $company);
    attachUserRoleIfMissing($manager, $company);

    $template = WorkflowTemplate::factory()->create(['company_id' => $company->id]);
    $group = WorkflowGroup::factory()->create(['template_id' => $template->id]);
    WorkflowStep::factory()->nPlus1()->create(['group_id' => $group->id, 'order' => 0]);
    WorkflowStep::factory()->nPlus1OfPrevious()->create(['group_id' => $group->id, 'order' => 1]);

    $approvable = FakeApprovable::createFor($requestor, $company);
    $request = app(ApprovalEngine::class)->submit($approvable, $template);
    $steps = $request->steps()->orderBy('order')->get();

    return [$company, $requestor, $manager, $request, $steps, $approvable];
}

function actAsApprovalUser(TestCase $test, User $user, Company $company): void
{
    attachUserRoleIfMissing($user, $company);
    $test->actingAs($user);
    session(['active_company_id' => $company->id]);
}

beforeEach(function (): void {
    FakeApprovable::ensureTable();
});

// ---------------------------------------------------------------------------
// Inbox
// ---------------------------------------------------------------------------

it('shows pending steps where authenticated user is the approver', function () {
    [$company, $requestor, $manager, $template, $request] = createApprovalSetup();
    actAsApprovalUser($this, $manager, $company);

    $this->get(route('approvals.inbox'))
        ->assertSuccessful()
        ->assertSee('Approval Inbox');

    $this->get(route('approvals.inbox'), ['X-DataTable-Partial' => 'true'])
        ->assertSuccessful()
        ->assertSee("Fake Approvable #{$request->approvable_id}");
});

it('shows empty state when user has no pending steps', function () {
    $company = Company::factory()->create(['is_active' => true]);
    $user = User::factory()->create();
    $company->users()->attach($user->id, ['status' => 'active']);
    actAsApprovalUser($this, $user, $company);

    $this->get(route('approvals.inbox'))
        ->assertSuccessful()
        ->assertSee('Approval Inbox');

    $this->get(route('approvals.inbox'), ['X-DataTable-Partial' => 'true'])
        ->assertSuccessful()
        ->assertSee('No results match your filters.');
});

it('requires authentication — redirects to login when guest', function () {
    $this->get(route('approvals.inbox'))->assertRedirect(route('login'));
    $this->get(route('approvals.history'))->assertRedirect(route('login'));
    $this->get(route('approvals.requests'))->assertRedirect(route('login'));
});

// ---------------------------------------------------------------------------
// History
// ---------------------------------------------------------------------------

it('shows approved and rejected requests where user was approver', function () {
    [$company, $requestor, $manager, $template] = createApprovalSetup();
    $engine = app(ApprovalEngine::class);

    $approvableA = FakeApprovable::createFor($requestor, $company);
    $requestA = $engine->submit($approvableA, $template);
    $engine->approve($requestA->steps()->first(), $manager);

    $approvableB = FakeApprovable::createFor($requestor, $company);
    $requestB = $engine->submit($approvableB, $template);
    $engine->reject($requestB->steps()->first(), $manager, 'Not approved');

    actAsApprovalUser($this, $manager, $company);

    $this->get(route('approvals.history'))
        ->assertSuccessful()
        ->assertSee('Approval History');

    $this->get(route('approvals.history'), ['X-DataTable-Partial' => 'true'])
        ->assertSuccessful()
        ->assertSee("Fake Approvable #{$requestA->approvable_id}")
        ->assertSee("Fake Approvable #{$requestB->approvable_id}");
});

it('shows requests where user was delegate', function () {
    [$company, $requestor, $manager, $template, $request, $step, $approvable] = createApprovalSetup();
    $delegate = User::factory()->create();
    $company->users()->attach($delegate->id, ['status' => 'active']);

    $engine = app(ApprovalEngine::class);
    $engine->delegate($step, $delegate, $manager);
    $engine->approve($step->refresh(), $delegate);

    actAsApprovalUser($this, $manager, $company);

    $this->get(route('approvals.history'), ['X-DataTable-Partial' => 'true'])
        ->assertSuccessful()
        ->assertSee("Fake Approvable #{$approvable->id}");
});

// ---------------------------------------------------------------------------
// My Requests
// ---------------------------------------------------------------------------

it('shows requests submitted by the authenticated user', function () {
    [$company, $requestor, $manager, $template, $request] = createApprovalSetup();
    actAsApprovalUser($this, $requestor, $company);

    $this->get(route('approvals.requests'))
        ->assertSuccessful()
        ->assertSee('My Requests');

    $this->get(route('approvals.requests'), ['X-DataTable-Partial' => 'true'])
        ->assertSuccessful()
        ->assertSee("Fake Approvable #{$request->approvable_id}");
});

// ---------------------------------------------------------------------------
// Show
// ---------------------------------------------------------------------------

it('displays approval request detail with workflow progress table', function () {
    [$company, $requestor, $manager, $template, $request] = createApprovalSetup();
    actAsApprovalUser($this, $manager, $company);

    $this->get(route('approvals.show', $request))
        ->assertSuccessful()
        ->assertSee('Workflow Progress')
        ->assertSee('Fake Approvable');
});

it('returns 403 when request belongs to a different company', function () {
    [$company, $requestor, $manager, $template, $request] = createApprovalSetup();
    $otherCompany = Company::factory()->create(['is_active' => true]);
    $otherUser = User::factory()->create();
    $otherCompany->users()->attach($otherUser->id, ['status' => 'active']);
    actAsApprovalUser($this, $otherUser, $otherCompany);

    $this->get(route('approvals.show', $request))->assertForbidden();
});

it('shows approve and reject buttons when user is the current approver', function () {
    [$company, $requestor, $manager, $template, $request] = createApprovalSetup();
    actAsApprovalUser($this, $manager, $company);

    $this->get(route('approvals.show', $request))
        ->assertSuccessful()
        ->assertSee('You are the current approver.');
});

it('shows cancel button when user is the requestor and request is pending', function () {
    [$company, $requestor, $manager, $template, $request] = createApprovalSetup();
    actAsApprovalUser($this, $requestor, $company);

    $this->get(route('approvals.show', $request))
        ->assertSuccessful()
        ->assertSee('Cancel Request');
});

it('does NOT show action buttons when request is already finalized', function () {
    [$company, $requestor, $manager, $template, $request, $step] = createApprovalSetup();
    app(ApprovalEngine::class)->reject($step, $manager, 'Rejected for test');

    actAsApprovalUser($this, $manager, $company);

    $this->get(route('approvals.show', $request))
        ->assertSuccessful()
        ->assertSee('Rejected')
        ->assertDontSee('You are the current approver.');
});

// ---------------------------------------------------------------------------
// Approve
// ---------------------------------------------------------------------------

it('approves step successfully and redirects back with success message', function () {
    [$company, $requestor, $manager, $template, $request, $step] = createApprovalSetup();
    actAsApprovalUser($this, $manager, $company);

    $this->get(route('approvals.show', $request));

    $this->from(route('approvals.show', $request))->post(route('approvals.approve', $step), [
        '_token' => session()->token(),
        'notes' => 'Approved in test',
    ])->assertRedirect(route('approvals.show', $request))
        ->assertSessionHas('success', 'Step approved.');

    $this->assertDatabaseHas('approval_request_steps', ['id' => $step->id, 'state' => 'approved']);
});

it('returns 403 when approving with a different user', function () {
    [$company, $requestor, $manager, $template, $request, $step] = createApprovalSetup();
    actAsApprovalUser($this, $requestor, $company);

    $this->get(route('approvals.show', $request));

    $this->post(route('approvals.approve', $step), ['_token' => session()->token()])->assertForbidden();
});

it('redirects back with error when step is already processed', function () {
    [$company, $requestor, $manager, $template, $request, $step] = createApprovalSetup();
    $step->update(['state' => StepState::Approved]);
    actAsApprovalUser($this, $manager, $company);

    $this->get(route('approvals.show', $request));

    $this->from(route('approvals.show', $request))->post(route('approvals.approve', $step), [
        '_token' => session()->token(),
    ])->assertSessionHas('error', 'This step has already been processed. The approval request status may have changed.');
});

// ---------------------------------------------------------------------------
// Reject
// ---------------------------------------------------------------------------

it('rejects request successfully and redirects back with success message', function () {
    [$company, $requestor, $manager, $template, $request, $step] = createApprovalSetup();
    actAsApprovalUser($this, $manager, $company);

    $this->get(route('approvals.show', $request));

    $this->from(route('approvals.show', $request))->post(route('approvals.reject', $step), [
        '_token' => session()->token(),
        'reason' => 'Not sufficient detail',
    ])->assertSessionHas('success', 'Request rejected.');

    $this->assertDatabaseHas('approval_request_steps', ['id' => $step->id, 'state' => 'rejected']);
    $this->assertDatabaseHas('approval_requests', ['id' => $request->id, 'status' => 'rejected']);
});

it('returns 403 when rejecting with a different user', function () {
    [$company, $requestor, $manager, $template, $request, $step] = createApprovalSetup();
    actAsApprovalUser($this, $requestor, $company);

    $this->get(route('approvals.show', $request));

    $this->post(route('approvals.reject', $step), [
        '_token' => session()->token(),
        'reason' => 'x',
    ])->assertForbidden();
});

it('returns validation error when reason is missing', function () {
    [$company, $requestor, $manager, $template, $request, $step] = createApprovalSetup();
    actAsApprovalUser($this, $manager, $company);

    $this->get(route('approvals.show', $request));

    $this->post(route('approvals.reject', $step), [
        '_token' => session()->token(),
        'reason' => '',
    ])->assertSessionHasErrors('reason');
});

// ---------------------------------------------------------------------------
// Cancel
// ---------------------------------------------------------------------------

it('cancels request successfully and redirects to requests index', function () {
    [$company, $requestor, $manager, $template, $request] = createApprovalSetup();
    actAsApprovalUser($this, $requestor, $company);

    $this->get(route('approvals.show', $request));

    $this->post(route('approvals.cancel', $request), ['_token' => session()->token()])
        ->assertRedirect(route('approvals.requests'))
        ->assertSessionHas('success', 'Approval request canceled.');

    $this->assertDatabaseHas('approval_requests', ['id' => $request->id, 'status' => 'canceled']);
});

it('returns 403 when authenticated user is not the requestor', function () {
    [$company, $requestor, $manager, $template, $request] = createApprovalSetup();
    actAsApprovalUser($this, $manager, $company);

    $this->get(route('approvals.show', $request));

    $this->post(route('approvals.cancel', $request), ['_token' => session()->token()])->assertForbidden();
});

// ---------------------------------------------------------------------------
// Delegate
// ---------------------------------------------------------------------------

it('delegates step successfully and redirects back', function () {
    [$company, $requestor, $manager, $template, $request, $step] = createApprovalSetup();
    $delegate = User::factory()->create();
    $company->users()->attach($delegate->id, ['status' => 'active']);
    actAsApprovalUser($this, $manager, $company);

    $this->get(route('approvals.show', $request));

    $this->from(route('approvals.show', $request))->post(route('approvals.delegate', $step), [
        '_token' => session()->token(),
        'approver_id' => $delegate->id,
    ])->assertSessionHas('success', 'Step delegated successfully.');

    $this->assertDatabaseHas('approval_request_steps', ['id' => $step->id, 'approver_id' => $delegate->id]);
});

it('returns 403 when delegating with a different user', function () {
    [$company, $requestor, $manager, $template, $request, $step] = createApprovalSetup();
    $delegate = User::factory()->create();
    $company->users()->attach($delegate->id, ['status' => 'active']);
    actAsApprovalUser($this, $requestor, $company);

    $this->get(route('approvals.show', $request));

    $this->post(route('approvals.delegate', $step), [
        '_token' => session()->token(),
        'approver_id' => $delegate->id,
    ])->assertForbidden();
});

// ---------------------------------------------------------------------------
// Resolve Previous Manager
// ---------------------------------------------------------------------------

it('resolves previous manager step successfully for requestor', function () {
    [$company, $requestor, $manager, $request, $steps] = createAwaitingPreviousSetup();
    $newApprover = User::factory()->create();
    $company->users()->attach($newApprover->id, ['status' => 'active']);
    actAsApprovalUser($this, $requestor, $company);

    $this->get(route('approvals.show', $request));

    $this->post(route('approvals.resolve-previous-manager', $steps[1]), [
        '_token' => session()->token(),
        'approver_id' => $newApprover->id,
    ])->assertSessionHas('success', 'Approver resolved successfully.');

    $this->assertDatabaseHas('approval_request_steps', ['id' => $steps[1]->id, 'state' => 'pending', 'approver_id' => $newApprover->id]);
});

it('resolves previous manager step successfully for platform admin', function () {
    [$company, $requestor, $manager, $request, $steps] = createAwaitingPreviousSetup();
    $newApprover = User::factory()->create();
    $company->users()->attach($newApprover->id, ['status' => 'active']);

    $adminCompany = Company::factory()->create(['is_active' => true]);
    $admin = User::factory()->create();
    Role::factory()->sysadmin()->create();
    $admin->companies()->attach($adminCompany->id, ['status' => 'active']);
    $admin->companyRoles()->attach(Role::where('name', 'sysadmin')->value('id'), ['company_id' => $adminCompany->id]);
    actAsApprovalUser($this, $admin, $adminCompany);

    $this->get(route('approvals.show', $request))->assertForbidden();

    $this->post(route('approvals.resolve-previous-manager', $steps[1]), [
        '_token' => session()->token(),
        'approver_id' => $newApprover->id,
    ])->assertSessionHas('success', 'Approver resolved successfully.');

    $this->assertDatabaseHas('approval_request_steps', ['id' => $steps[1]->id, 'state' => 'pending', 'approver_id' => $newApprover->id]);
});

it('returns 403 for unauthorized users (non-requestor, non-admin)', function () {
    [$company, $requestor, $manager, $request, $steps] = createAwaitingPreviousSetup();
    $newApprover = User::factory()->create();
    $company->users()->attach($newApprover->id, ['status' => 'active']);

    $stranger = User::factory()->create();
    $company->users()->attach($stranger->id, ['status' => 'active']);
    actAsApprovalUser($this, $stranger, $company);

    $this->get(route('approvals.show', $request));

    $this->post(route('approvals.resolve-previous-manager', $steps[1]), [
        '_token' => session()->token(),
        'approver_id' => $newApprover->id,
    ])->assertForbidden();
});

it('returns 403 when step is not AwaitingPrevious', function () {
    [$company, $requestor, $manager, $request, $steps] = createAwaitingPreviousSetup();
    $newApprover = User::factory()->create();
    $company->users()->attach($newApprover->id, ['status' => 'active']);
    actAsApprovalUser($this, $requestor, $company);

    $this->get(route('approvals.show', $request));

    $this->post(route('approvals.resolve-previous-manager', $steps[0]), [
        '_token' => session()->token(),
        'approver_id' => $newApprover->id,
    ])->assertForbidden();
});

it('shows approve and reject action buttons to step 2 approver after step 1 is approved in sequential group', function () {
    $company = Company::factory()->create();
    $requestor = User::factory()->create();
    $manager1 = User::factory()->create();
    $manager2 = User::factory()->create();

    $company->users()->attach($requestor->id, ['manager_id' => $manager1->id, 'status' => 'active']);
    $company->users()->attach($manager1->id, ['manager_id' => $manager2->id, 'status' => 'active']);
    $company->users()->attach($manager2->id, ['manager_id' => null, 'status' => 'active']);

    $template = WorkflowTemplate::factory()->create(['company_id' => $company->id]);
    $group = WorkflowGroup::factory()->create(['template_id' => $template->id, 'order' => 0, 'type' => GroupType::Sequential]);
    WorkflowStep::factory()->nPlus1()->create(['group_id' => $group->id, 'order' => 0]);
    WorkflowStep::factory()->nPlus1OfPrevious()->create(['group_id' => $group->id, 'order' => 1]);

    $approvable = FakeApprovable::createFor($requestor, $company);
    $engine = app(ApprovalEngine::class);
    $approvalRequest = $engine->submit($approvable, $template);

    // Manager 1 approves Step 1
    $step1 = $approvalRequest->steps()->where('order', 0)->first();
    $engine->approve($step1, $manager1, 'Step 1 Approved');

    // Manager 2 visits the approval request page
    actAsApprovalUser($this, $manager2, $company);

    $response = $this->get(route('approvals.show', $approvalRequest));

    $response->assertOk();
    $response->assertSee('Approve');
    $response->assertSee('Reject');
    $response->assertSee('You are the current approver.');
});

it('activates parallel group steps and notifies all parallel approvers only after sequential group 1 completes', function () {
    $company = Company::factory()->create();
    $requestor = User::factory()->create();
    $manager1 = User::factory()->create();
    $manager2 = User::factory()->create();
    $parallel1 = User::factory()->create();
    $parallel2 = User::factory()->create();
    $parallel3 = User::factory()->create();

    $company->users()->attach($requestor->id, ['manager_id' => $manager1->id, 'status' => 'active']);
    $company->users()->attach($manager1->id, ['manager_id' => $manager2->id, 'status' => 'active']);
    $company->users()->attach($manager2->id, ['manager_id' => null, 'status' => 'active']);
    $company->users()->attach($parallel1->id, ['status' => 'active']);
    $company->users()->attach($parallel2->id, ['status' => 'active']);
    $company->users()->attach($parallel3->id, ['status' => 'active']);

    $template = WorkflowTemplate::factory()->create(['company_id' => $company->id]);
    $g1 = WorkflowGroup::factory()->create(['template_id' => $template->id, 'name' => 'Group 1', 'order' => 0, 'type' => GroupType::Sequential]);
    WorkflowStep::factory()->nPlus1()->create(['group_id' => $g1->id, 'order' => 0]);
    WorkflowStep::factory()->nPlus1OfPrevious()->create(['group_id' => $g1->id, 'order' => 1]);

    $g2 = WorkflowGroup::factory()->create(['template_id' => $template->id, 'name' => 'Group 2', 'order' => 1, 'type' => GroupType::Parallel]);
    WorkflowStep::factory()->specificUser($parallel1->id)->create(['group_id' => $g2->id, 'order' => 0]);
    WorkflowStep::factory()->specificUser($parallel2->id)->create(['group_id' => $g2->id, 'order' => 1]);
    WorkflowStep::factory()->specificUser($parallel3->id)->create(['group_id' => $g2->id, 'order' => 2]);

    $approvable = FakeApprovable::createFor($requestor, $company);
    $engine = app(ApprovalEngine::class);
    $approvalRequest = $engine->submit($approvable, $template);

    // Initial state: Group 2 steps are AwaitingPrevious
    expect($parallel1->unreadNotifications()->count())->toBe(0);
    expect($parallel2->unreadNotifications()->count())->toBe(0);
    expect($parallel3->unreadNotifications()->count())->toBe(0);

    $g1Steps = $approvalRequest->steps()->where('group_name', 'Group 1')->get();
    $engine->approve($g1Steps->where('order', 0)->first(), $manager1);

    // Group 1 Step 1 done, Group 1 Step 2 pending for Manager 2
    expect($parallel1->unreadNotifications()->count())->toBe(0);

    // Manager 2 approves Group 1 Step 2 -> Group 1 completes! Group 2 activates!
    $g1Step2 = $approvalRequest->steps()->where('group_name', 'Group 1')->where('order', 1)->first();
    $engine->approve($g1Step2, $manager2);

    // All 3 parallel approvers in Group 2 receive notification
    expect($parallel1->unreadNotifications()->count())->toBe(1);
    expect($parallel2->unreadNotifications()->count())->toBe(1);
    expect($parallel3->unreadNotifications()->count())->toBe(1);

    // All 3 parallel approvers in Group 2 see Approve & Reject buttons
    foreach ([$parallel1, $parallel2, $parallel3] as $pUser) {
        actAsApprovalUser($this, $pUser, $company);
        $res = $this->get(route('approvals.show', $approvalRequest));
        $res->assertOk();
        $res->assertSee('Approve');
        $res->assertSee('Reject');
        $res->assertSee('You are the current approver.');
    }
});
