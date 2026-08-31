<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire\Approvals;

use App\Actions\Approvals\ApproveStepAction;
use App\Actions\Approvals\CancelApprovalRequestAction;
use App\Actions\Approvals\DelegateApprovalStepAction;
use App\Actions\Approvals\RejectStepAction;
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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create(['name' => 'Acme Corp', 'is_active' => true]);

    $this->adminRole = Role::firstOrCreate(['name' => 'admin', 'label' => 'Administrator', 'is_active' => true], ['permissions' => ['*']]);
    $this->adminRole->update(['permissions' => ['*']]);

    $this->requester = User::factory()->create(['name' => 'Requester User', 'email' => 'requester@acme.com']);
    $this->requester->companies()->attach($this->company->id, ['status' => 'active']);

    $this->approver = User::factory()->create(['name' => 'Approver User', 'email' => 'approver@acme.com']);
    $this->approver->companies()->attach($this->company->id, ['status' => 'active']);

    $this->colleague = User::factory()->create(['name' => 'Colleague User', 'email' => 'colleague@acme.com']);
    $this->colleague->companies()->attach($this->company->id, ['status' => 'active']);

    $this->template = WorkflowTemplate::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Standard Workflow',
        'is_active' => true,
    ]);

    $this->group = WorkflowGroup::factory()->create([
        'template_id' => $this->template->id,
        'name' => 'Reviewers',
        'order' => 1,
    ]);

    $this->wStep = WorkflowStep::factory()->create([
        'group_id' => $this->group->id,
        'order' => 1,
    ]);
});

it('renders approval inbox with pending steps for logged in approver', function () {
    $this->actingAs($this->approver);
    session(['active_company_id' => $this->company->id]);

    $approvalRequest = ApprovalRequest::create([
        'company_id' => $this->company->id,
        'requestor_id' => $this->requester->id,
        'approvable_type' => 'App\Models\Company',
        'approvable_id' => $this->company->id,
        'workflow_template_id' => $this->template->id,
        'workflow_snapshot' => ['groups' => []],
        'status' => ApprovalStatus::Pending,
        'submitted_at' => now(),
    ]);

    $step = ApprovalRequestStep::create([
        'approval_request_id' => $approvalRequest->id,
        'group_name' => 'Reviewers',
        'step_label' => 'Manager Review',
        'resolver_type' => ResolverType::SpecificUser,
        'resolver_payload' => ['user_id' => $this->approver->id],
        'approver_id' => $this->approver->id,
        'state' => StepState::Pending,
        'order' => 1,
    ]);

    Livewire::test('approvals.approval-inbox')
        ->assertSuccessful()
        ->assertSee('Pending Approvals')
        ->assertSee('Requester User')
        ->assertSee('Awaiting Your Action');
});

it('approves a step and updates state', function () {
    $this->actingAs($this->approver);
    session(['active_company_id' => $this->company->id]);

    $approvalRequest = ApprovalRequest::create([
        'company_id' => $this->company->id,
        'requestor_id' => $this->requester->id,
        'approvable_type' => 'App\Models\Company',
        'approvable_id' => $this->company->id,
        'workflow_template_id' => $this->template->id,
        'workflow_snapshot' => ['groups' => []],
        'status' => ApprovalStatus::Pending,
        'submitted_at' => now(),
    ]);

    $step = ApprovalRequestStep::create([
        'approval_request_id' => $approvalRequest->id,
        'group_name' => 'Reviewers',
        'step_label' => 'Manager Review',
        'resolver_type' => ResolverType::SpecificUser,
        'resolver_payload' => ['user_id' => $this->approver->id],
        'approver_id' => $this->approver->id,
        'state' => StepState::Pending,
        'order' => 1,
    ]);

    Livewire::test('approvals.approval-inbox')
        ->call('openApproveModal', $step->id, 'Acme Corp')
        ->set('approvalNotes', 'Looks great, approved.')
        ->call('approveStep')
        ->assertDispatched('notify');

    $step->refresh();
    expect($step->state)->toBe(StepState::Approved)
        ->and($step->notes)->toBe('Looks great, approved.');
});

it('rejects a step and updates state', function () {
    $this->actingAs($this->approver);
    session(['active_company_id' => $this->company->id]);

    $approvalRequest = ApprovalRequest::create([
        'company_id' => $this->company->id,
        'requestor_id' => $this->requester->id,
        'approvable_type' => 'App\Models\Company',
        'approvable_id' => $this->company->id,
        'workflow_template_id' => $this->template->id,
        'workflow_snapshot' => ['groups' => []],
        'status' => ApprovalStatus::Pending,
        'submitted_at' => now(),
    ]);

    $step = ApprovalRequestStep::create([
        'approval_request_id' => $approvalRequest->id,
        'group_name' => 'Reviewers',
        'step_label' => 'Manager Review',
        'resolver_type' => ResolverType::SpecificUser,
        'resolver_payload' => ['user_id' => $this->approver->id],
        'approver_id' => $this->approver->id,
        'state' => StepState::Pending,
        'order' => 1,
    ]);

    Livewire::test('approvals.approval-inbox')
        ->call('openRejectModal', $step->id, 'Acme Corp')
        ->set('rejectionReason', 'Budget allocation exceeded.')
        ->call('rejectStep')
        ->assertDispatched('notify');

    $step->refresh();
    expect($step->state)->toBe(StepState::Rejected)
        ->and($step->notes)->toBe('Budget allocation exceeded.');
});

it('delegates a step to another colleague', function () {
    $this->actingAs($this->approver);
    session(['active_company_id' => $this->company->id]);

    $approvalRequest = ApprovalRequest::create([
        'company_id' => $this->company->id,
        'requestor_id' => $this->requester->id,
        'approvable_type' => 'App\Models\Company',
        'approvable_id' => $this->company->id,
        'workflow_template_id' => $this->template->id,
        'workflow_snapshot' => ['groups' => []],
        'status' => ApprovalStatus::Pending,
        'submitted_at' => now(),
    ]);

    $step = ApprovalRequestStep::create([
        'approval_request_id' => $approvalRequest->id,
        'group_name' => 'Reviewers',
        'step_label' => 'Manager Review',
        'resolver_type' => ResolverType::SpecificUser,
        'resolver_payload' => ['user_id' => $this->approver->id],
        'approver_id' => $this->approver->id,
        'state' => StepState::Pending,
        'order' => 1,
    ]);

    Livewire::test('approvals.approval-inbox')
        ->call('openDelegateModal', $step->id, 'Acme Corp')
        ->set('delegateUserId', $this->colleague->id)
        ->call('delegateStep')
        ->assertDispatched('notify');

    $step->refresh();
    expect($step->approver_id)->toBe($this->colleague->id)
        ->and($step->delegated_from_id)->toBe($this->approver->id);
});

it('allows requester to cancel approval request', function () {
    $this->actingAs($this->requester);
    session(['active_company_id' => $this->company->id]);

    $approvalRequest = ApprovalRequest::create([
        'company_id' => $this->company->id,
        'requestor_id' => $this->requester->id,
        'approvable_type' => 'App\Models\Company',
        'approvable_id' => $this->company->id,
        'workflow_template_id' => $this->template->id,
        'workflow_snapshot' => ['groups' => []],
        'status' => ApprovalStatus::Pending,
        'submitted_at' => now(),
    ]);

    Livewire::test('approvals.approval-inbox', ['tab' => 'requests'])
        ->call('openCancelModal', $approvalRequest->id, 'Acme Corp')
        ->call('cancelRequest')
        ->assertDispatched('notify');

    $approvalRequest->refresh();
    expect($approvalRequest->status)->toBe(ApprovalStatus::Canceled);
});

it('renders approval request viewer with workflow steps timeline', function () {
    $this->actingAs($this->approver);
    session(['active_company_id' => $this->company->id]);

    $approvalRequest = ApprovalRequest::create([
        'company_id' => $this->company->id,
        'requestor_id' => $this->requester->id,
        'approvable_type' => 'App\Models\Company',
        'approvable_id' => $this->company->id,
        'workflow_template_id' => $this->template->id,
        'workflow_snapshot' => ['groups' => []],
        'status' => ApprovalStatus::Pending,
        'submitted_at' => now(),
    ]);

    $step = ApprovalRequestStep::create([
        'approval_request_id' => $approvalRequest->id,
        'group_name' => 'Reviewers',
        'step_label' => 'Manager Review',
        'resolver_type' => ResolverType::SpecificUser,
        'resolver_payload' => ['user_id' => $this->approver->id],
        'approver_id' => $this->approver->id,
        'state' => StepState::Pending,
        'order' => 1,
    ]);

    Livewire::test('approvals.approval-request-viewer', ['requestId' => $approvalRequest->id])
        ->assertSuccessful()
        ->assertSee('Workflow Progress')
        ->assertSee('Approver User')
        ->assertSee('Pending');
});
