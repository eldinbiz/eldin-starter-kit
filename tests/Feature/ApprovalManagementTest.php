<?php

declare(strict_types=1);

use App\Enums\ApprovalStatus;
use App\Enums\StepState;
use App\Models\Approval\ApprovalRequest;
use App\Models\Approval\ApprovalRequestStep;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers are shared from ApprovalWorkflowAdminTest.php
// ---------------------------------------------------------------------------

// ---------------------------------------------------------------------------
// Index
// ---------------------------------------------------------------------------

it('admin can view the manage approvals page', function () {
    [$user, $company] = actAsApprovalAdmin($this);

    $request = ApprovalRequest::factory()->create([
        'company_id' => $company->id,
        'requestor_id' => $user->id,
        'status' => ApprovalStatus::Pending,
    ]);

    $step = ApprovalRequestStep::factory()->pending()->create([
        'approval_request_id' => $request->id,
        'approver_id' => $user->id,
        'step_label' => 'Manager Approval',
    ]);

    $this->get(route('admin.approval-management.index'))
        ->assertSuccessful()
        ->assertSee('Manager Approval');
});

it('non-admin receives 403 on index', function () {
    actAsApprovalRegularUser($this);

    $this->get(route('admin.approval-management.index'))->assertForbidden();
});

it('unauthenticated user is redirected to login', function () {
    $this->get(route('admin.approval-management.index'))->assertRedirect(route('login'));
});

it('admin sees steps from their company only', function () {
    [$user, $company] = actAsApprovalAdmin($this);

    $otherCompany = Company::factory()->create(['is_active' => true]);

    $myRequest = ApprovalRequest::factory()->create([
        'company_id' => $company->id,
        'requestor_id' => $user->id,
        'status' => ApprovalStatus::Pending,
    ]);
    $myStep = ApprovalRequestStep::factory()->pending()->create([
        'approval_request_id' => $myRequest->id,
        'step_label' => 'My Step',
    ]);

    $otherRequest = ApprovalRequest::factory()->create([
        'company_id' => $otherCompany->id,
        'status' => ApprovalStatus::Pending,
    ]);
    $otherStep = ApprovalRequestStep::factory()->pending()->create([
        'approval_request_id' => $otherRequest->id,
        'step_label' => 'Other Step',
    ]);

    $this->get(route('admin.approval-management.index'))
        ->assertSuccessful()
        ->assertSee('My Step')
        ->assertDontSee('Other Step');
});

// ---------------------------------------------------------------------------
// Delegate
// ---------------------------------------------------------------------------

it('admin can delegate a pending step', function () {
    [$user, $company] = actAsApprovalAdmin($this);

    $approver = User::factory()->create();
    $company->users()->attach($approver->id, ['status' => 'active']);

    $newApprover = User::factory()->create();
    $company->users()->attach($newApprover->id, ['status' => 'active']);

    $request = ApprovalRequest::factory()->create([
        'company_id' => $company->id,
        'requestor_id' => $user->id,
        'status' => ApprovalStatus::Pending,
    ]);

    $step = ApprovalRequestStep::factory()->pending()->create([
        'approval_request_id' => $request->id,
        'approver_id' => $approver->id,
        'step_label' => 'Manager Approval',
    ]);

    $this->post(route('admin.approval-management.delegate', $step->id), [
        'approver_id' => $newApprover->id,
    ])->assertRedirect()->assertSessionHas('success');

    $step->refresh();
    expect($step->approver_id)->toBe($newApprover->id)
        ->and($step->delegated_from_id)->toBe($user->id);
});

it('delegating a non-pending step returns error', function () {
    [$user, $company] = actAsApprovalAdmin($this);

    $approver = User::factory()->create();
    $company->users()->attach($approver->id, ['status' => 'active']);

    $request = ApprovalRequest::factory()->create([
        'company_id' => $company->id,
        'requestor_id' => $user->id,
        'status' => ApprovalStatus::Pending,
    ]);

    $step = ApprovalRequestStep::factory()->approved()->create([
        'approval_request_id' => $request->id,
        'approver_id' => $approver->id,
    ]);

    $newApprover = User::factory()->create();
    $company->users()->attach($newApprover->id, ['status' => 'active']);

    $this->post(route('admin.approval-management.delegate', $step->id), [
        'approver_id' => $newApprover->id,
    ])->assertStatus(500);
});

// ---------------------------------------------------------------------------
// Skip
// ---------------------------------------------------------------------------

it('admin can skip a pending step', function () {
    [$user, $company] = actAsApprovalAdmin($this);

    $request = ApprovalRequest::factory()->create([
        'company_id' => $company->id,
        'requestor_id' => $user->id,
        'status' => ApprovalStatus::Pending,
    ]);

    $step = ApprovalRequestStep::factory()->pending()->create([
        'approval_request_id' => $request->id,
        'approver_id' => $user->id,
        'step_label' => 'Manager Approval',
    ]);

    $this->post(route('admin.approval-management.skip', $step->id))
        ->assertRedirect()->assertSessionHas('success');

    $step->refresh();
    expect($step->state)->toBe(StepState::Skipped);
});

it('admin can skip an AwaitingPrevious step', function () {
    [$user, $company] = actAsApprovalAdmin($this);

    $request = ApprovalRequest::factory()->create([
        'company_id' => $company->id,
        'requestor_id' => $user->id,
        'status' => ApprovalStatus::Pending,
    ]);

    $step = ApprovalRequestStep::factory()->awaitingPrevious()->create([
        'approval_request_id' => $request->id,
        'step_label' => 'Director Approval',
    ]);

    $this->post(route('admin.approval-management.skip', $step->id))
        ->assertRedirect()->assertSessionHas('success');

    $step->refresh();
    expect($step->state)->toBe(StepState::Skipped);
});

it('skipping advances state machine when last step in group is skipped', function () {
    [$user, $company] = actAsApprovalAdmin($this);

    $request = ApprovalRequest::factory()->create([
        'company_id' => $company->id,
        'requestor_id' => $user->id,
        'status' => ApprovalStatus::Pending,
        'workflow_snapshot' => [
            'template_name' => 'Test',
            'groups' => [
                [
                    'name' => 'Group 1',
                    'type' => 'parallel',
                    'steps' => [
                        ['label' => 'Step 1', 'order' => 0, 'resolver_type' => 'N+1', 'resolver_payload' => []],
                    ],
                ],
            ],
        ],
    ]);

    $step = ApprovalRequestStep::factory()->pending()->create([
        'approval_request_id' => $request->id,
        'approver_id' => $user->id,
        'group_name' => 'Group 1',
        'step_label' => 'Step 1',
        'order' => 0,
    ]);

    $this->post(route('admin.approval-management.skip', $step->id))
        ->assertRedirect()->assertSessionHas('success');

    $request->refresh();
    expect($request->status)->toBe(ApprovalStatus::Approved);
});

// ---------------------------------------------------------------------------
// Filters
// ---------------------------------------------------------------------------

it('search filter works by approver name', function () {
    [$user, $company] = actAsApprovalAdmin($this);

    $approver = User::factory()->create(['name' => 'Jane Doe']);
    $company->users()->attach($approver->id, ['status' => 'active']);

    $request = ApprovalRequest::factory()->create([
        'company_id' => $company->id,
        'requestor_id' => $user->id,
        'status' => ApprovalStatus::Pending,
    ]);

    ApprovalRequestStep::factory()->pending()->create([
        'approval_request_id' => $request->id,
        'approver_id' => $approver->id,
        'step_label' => 'HR Review',
    ]);

    $otherApprover = User::factory()->create(['name' => 'Bob Smith']);
    $company->users()->attach($otherApprover->id, ['status' => 'active']);

    $otherRequest = ApprovalRequest::factory()->create([
        'company_id' => $company->id,
        'requestor_id' => $user->id,
        'status' => ApprovalStatus::Pending,
    ]);
    ApprovalRequestStep::factory()->pending()->create([
        'approval_request_id' => $otherRequest->id,
        'approver_id' => $otherApprover->id,
        'step_label' => 'Manager Approval',
    ]);

    // Verify the DataTable query filters correctly (ORM level)
    $janeCount = ApprovalRequestStep::query()
        ->whereHas('request', fn ($q) => $q->where('company_id', $company->id))
        ->where(function ($sub) {
            $sub->where('step_label', 'like', '%Jane%')
                ->orWhereHas('approver', fn ($sq) => $sq->where('name', 'like', '%Jane%'));
        })
        ->count();
    expect($janeCount)->toBe(1);

    // Verify page shows only the matching step
    $this->get(route('admin.approval-management.index', ['search' => 'Jane']))
        ->assertSuccessful()
        ->assertSee('HR Review')
        ->assertDontSee('Manager Approval');
});

it('step_state filter works', function () {
    [$user, $company] = actAsApprovalAdmin($this);

    $request = ApprovalRequest::factory()->create([
        'company_id' => $company->id,
        'requestor_id' => $user->id,
        'status' => ApprovalStatus::Pending,
    ]);

    $pendingStep = ApprovalRequestStep::factory()->pending()->create([
        'approval_request_id' => $request->id,
        'step_label' => 'Pending Step',
    ]);

    $approvedStep = ApprovalRequestStep::factory()->approved()->create([
        'approval_request_id' => $request->id,
        'step_label' => 'Approved Step',
    ]);

    $this->get(route('admin.approval-management.index', ['step_state' => 'approved']))
        ->assertSuccessful()
        ->assertSee('Approved Step')
        ->assertDontSee('Pending Step');
});

it('request_status filter works', function () {
    [$user, $company] = actAsApprovalAdmin($this);

    $pendingRequest = ApprovalRequest::factory()->create([
        'company_id' => $company->id,
        'requestor_id' => $user->id,
        'status' => ApprovalStatus::Pending,
    ]);
    ApprovalRequestStep::factory()->pending()->create([
        'approval_request_id' => $pendingRequest->id,
        'step_label' => 'Pending Request Step',
    ]);

    $approvedRequest = ApprovalRequest::factory()->approved()->create([
        'company_id' => $company->id,
        'requestor_id' => $user->id,
    ]);
    ApprovalRequestStep::factory()->approved()->create([
        'approval_request_id' => $approvedRequest->id,
        'step_label' => 'Approved Request Step',
    ]);

    $this->get(route('admin.approval-management.index', ['request_status' => 'approved']))
        ->assertSuccessful()
        ->assertSee('Approved Request Step')
        ->assertDontSee('Pending Request Step');
});
