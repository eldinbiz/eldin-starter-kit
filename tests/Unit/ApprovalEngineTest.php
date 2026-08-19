<?php

declare(strict_types=1);

use App\Enums\ApprovalStatus;
use App\Enums\GroupType;
use App\Enums\ResolverType;
use App\Enums\StepState;
use App\Events\Approval\ApprovalCanceled;
use App\Events\Approval\ApprovalCompleted;
use App\Events\Approval\ApprovalDelegated;
use App\Events\Approval\ApprovalRejected;
use App\Events\Approval\ApprovalRequested;
use App\Events\Approval\ApprovalStepCompleted;
use App\Models\Approval\ApprovalRequest;
use App\Models\Approval\ApprovalRequestStep;
use App\Models\Approval\WorkflowGroup;
use App\Models\Approval\WorkflowStep;
use App\Models\Approval\WorkflowTemplate;
use App\Models\Company;
use App\Models\User;
use App\Services\ApprovalEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Support\FakeApprovable;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function makeFakeApprovable(User $requester, Company $company): FakeApprovable
{
    FakeApprovable::ensureTable();

    return FakeApprovable::createFor($requester, $company);
}

/**
 * Create a company with a manager chain: users[0] is the requestor and
 * users[i] is the direct manager of users[i - 1] within the company.
 *
 * @return array{0: Company, 1: array<int, User>}
 */
function createApprovalManagerChain(int $depth = 1): array
{
    $company = Company::factory()->create();
    $users = collect(range(0, $depth))->map(fn (): User => User::factory()->create())->all();

    $company->users()->attach($users[0]->id, ['manager_id' => $users[1]->id, 'status' => 'active']);

    for ($i = 1; $i < $depth; $i++) {
        $company->users()->attach($users[$i]->id, ['manager_id' => $users[$i + 1]->id, 'status' => 'active']);
    }

    $company->users()->attach($users[$depth]->id, ['manager_id' => null, 'status' => 'active']);

    return [$company, $users];
}

/**
 * Create a template with a single sequential group containing one N+1 step.
 */
function createSingleStepTemplate(Company $company): WorkflowTemplate
{
    $template = WorkflowTemplate::factory()->create(['company_id' => $company->id]);
    $group = WorkflowGroup::factory()->create(['template_id' => $template->id, 'order' => 0]);
    WorkflowStep::factory()->nPlus1()->create(['group_id' => $group->id, 'order' => 0]);

    return $template;
}

/**
 * Create a template with one parallel group of N+1 steps.
 */
function createParallelTemplate(Company $company, int $steps = 2, ?int $minApprovals = null): WorkflowTemplate
{
    $template = WorkflowTemplate::factory()->create(['company_id' => $company->id]);
    $group = WorkflowGroup::factory()->parallel($minApprovals)->create(['template_id' => $template->id, 'order' => 0]);

    foreach (range(0, $steps - 1) as $order) {
        WorkflowStep::factory()->nPlus1()->create(['group_id' => $group->id, 'order' => $order]);
    }

    return $template;
}

/**
 * Create a template with two sequential groups, each holding one N+1 step.
 */
function createTwoGroupTemplate(Company $company): WorkflowTemplate
{
    $template = WorkflowTemplate::factory()->create(['company_id' => $company->id]);
    $first = WorkflowGroup::factory()->create(['template_id' => $template->id, 'order' => 0]);
    $second = WorkflowGroup::factory()->create(['template_id' => $template->id, 'order' => 1]);
    WorkflowStep::factory()->nPlus1()->create(['group_id' => $first->id, 'order' => 0]);
    WorkflowStep::factory()->nPlus1()->create(['group_id' => $second->id, 'order' => 0]);

    return $template;
}

/**
 * Create a template with a sequential group of [N+1, N+1_of_previous] steps.
 */
function createAwaitingPreviousTemplate(Company $company): WorkflowTemplate
{
    $template = WorkflowTemplate::factory()->create(['company_id' => $company->id]);
    $group = WorkflowGroup::factory()->create(['template_id' => $template->id, 'order' => 0]);
    WorkflowStep::factory()->nPlus1()->create(['group_id' => $group->id, 'order' => 0]);
    WorkflowStep::factory()->nPlus1OfPrevious()->create(['group_id' => $group->id, 'order' => 1]);

    return $template;
}

function approvalEngine(): ApprovalEngine
{
    return app(ApprovalEngine::class);
}

function submitApprovalRequest(Company $company, User $requestor, WorkflowTemplate $template): ApprovalRequest
{
    $approvable = makeFakeApprovable($requestor, $company);

    return approvalEngine()->submit($approvable, $template);
}

beforeEach(function (): void {
    FakeApprovable::ensureTable();
});

// ---------------------------------------------------------------------------
// submit()
// ---------------------------------------------------------------------------

it('creates an approval request with pending status and submitted_at', function () {
    [$company, [$requestor]] = createApprovalManagerChain();
    $template = createSingleStepTemplate($company);

    $request = submitApprovalRequest($company, $requestor, $template);

    expect($request->status)->toBe(ApprovalStatus::Pending);
    expect($request->submitted_at)->not->toBeNull();
    expect($request->workflow_template_id)->toBe($template->id);
    expect($request->company_id)->toBe($company->id);
    expect($request->requestor_id)->toBe($requestor->id);
});

it('assigns approver via N+1 resolver — uses requestor manager', function () {
    [$company, [$requestor, $manager]] = createApprovalManagerChain();
    $template = createSingleStepTemplate($company);

    $request = submitApprovalRequest($company, $requestor, $template);
    $step = $request->steps()->first();

    expect($step->state)->toBe(StepState::Pending);
    expect($step->approver_id)->toBe($manager->id);
});

it('assigns approver via specific_user resolver', function () {
    $company = Company::factory()->create();
    $requestor = User::factory()->create();
    $target = User::factory()->create();
    $company->users()->attach($requestor->id, ['status' => 'active']);
    $company->users()->attach($target->id, ['status' => 'active']);

    $template = WorkflowTemplate::factory()->create(['company_id' => $company->id]);
    $group = WorkflowGroup::factory()->create(['template_id' => $template->id]);
    WorkflowStep::factory()->specificUser($target->id)->create(['group_id' => $group->id]);

    $request = submitApprovalRequest($company, $requestor, $template);

    expect($request->steps()->first()->approver_id)->toBe($target->id);
});

it('creates step in AwaitingPrevious state for N+1_of_previous resolver', function () {
    [$company, [$requestor]] = createApprovalManagerChain();
    $template = createAwaitingPreviousTemplate($company);

    $request = submitApprovalRequest($company, $requestor, $template);
    $steps = $request->steps()->orderBy('order')->get();

    expect($steps[0]->state)->toBe(StepState::Pending);
    expect($steps[1]->state)->toBe(StepState::AwaitingPrevious);
    expect($steps[1]->approver_id)->toBeNull();
});

it('throws RuntimeException when N+1 resolver finds no manager', function () {
    $company = Company::factory()->create();
    $requestor = User::factory()->create();
    $company->users()->attach($requestor->id, ['status' => 'active']);
    $template = createSingleStepTemplate($company);

    $approvable = makeFakeApprovable($requestor, $company);

    expect(fn () => approvalEngine()->submit($approvable, $template))
        ->toThrow(RuntimeException::class, 'no manager');
});

it('throws RuntimeException when specific_user resolver has no user_id in payload', function () {
    $company = Company::factory()->create();
    $requestor = User::factory()->create();
    $company->users()->attach($requestor->id, ['status' => 'active']);

    $template = WorkflowTemplate::factory()->create(['company_id' => $company->id]);
    $group = WorkflowGroup::factory()->create(['template_id' => $template->id]);
    WorkflowStep::factory()->create([
        'group_id' => $group->id,
        'resolver_type' => ResolverType::SpecificUser,
        'resolver_payload' => [],
    ]);

    $approvable = makeFakeApprovable($requestor, $company);

    expect(fn () => approvalEngine()->submit($approvable, $template))
        ->toThrow(RuntimeException::class, 'user_id');
});

it('throws RuntimeException when specific_user does not belong to company', function () {
    $company = Company::factory()->create();
    $requestor = User::factory()->create();
    $unrelatedCompany = Company::factory()->create();
    $target = User::factory()->create();
    $unrelatedCompany->users()->attach($target->id, ['status' => 'active']);
    $company->users()->attach($requestor->id, ['status' => 'active']);

    $template = WorkflowTemplate::factory()->create(['company_id' => $company->id]);
    $group = WorkflowGroup::factory()->create(['template_id' => $template->id]);
    WorkflowStep::factory()->specificUser($target->id)->create(['group_id' => $group->id]);

    $approvable = makeFakeApprovable($requestor, $company);

    expect(fn () => approvalEngine()->submit($approvable, $template))
        ->toThrow(RuntimeException::class, 'does not belong');
});

it('builds correct workflow_snapshot on the request', function () {
    [$company, [$requestor]] = createApprovalManagerChain();

    $template = WorkflowTemplate::factory()->create(['company_id' => $company->id, 'name' => 'Snapshot Workflow']);
    $first = WorkflowGroup::factory()->create(['template_id' => $template->id, 'name' => 'Group A', 'order' => 0]);
    $second = WorkflowGroup::factory()->parallel()->create(['template_id' => $template->id, 'name' => 'Group B', 'order' => 1]);
    WorkflowStep::factory()->nPlus1()->create(['group_id' => $first->id, 'label' => 'Step One', 'order' => 0]);
    WorkflowStep::factory()->nPlus1()->create(['group_id' => $second->id, 'label' => 'Step Two', 'order' => 0]);
    WorkflowStep::factory()->nPlus1()->create(['group_id' => $second->id, 'label' => 'Step Three', 'order' => 1]);

    $request = submitApprovalRequest($company, $requestor, $template);
    $snapshot = $request->workflow_snapshot;

    expect($snapshot['template_name'])->toBe('Snapshot Workflow');
    expect($snapshot['groups'])->toHaveCount(2);
    expect($snapshot['groups'][0]['name'])->toBe('Group A');
    expect($snapshot['groups'][0]['type'])->toBe(GroupType::Sequential->value);
    expect($snapshot['groups'][0]['steps'])->toHaveCount(1);
    expect($snapshot['groups'][0]['steps'][0]['label'])->toBe('Step One');
    expect($snapshot['groups'][0]['steps'][0]['resolver_type'])->toBe('N+1');
    expect($snapshot['groups'][1]['steps'])->toHaveCount(2);
    expect($snapshot['groups'][1]['type'])->toBe(GroupType::Parallel->value);
});

it('dispatches ApprovalRequested event', function () {
    Event::fake();
    [$company, [$requestor]] = createApprovalManagerChain();
    $template = createSingleStepTemplate($company);

    $request = submitApprovalRequest($company, $requestor, $template);

    Event::assertDispatched(ApprovalRequested::class, fn (ApprovalRequested $event) => $event->approvalRequest->is($request));
});

it('runs inside a database transaction — rolls back on failure', function () {
    $company = Company::factory()->create();
    $requestor = User::factory()->create();
    $company->users()->attach($requestor->id, ['status' => 'active']);
    $template = createSingleStepTemplate($company);
    $approvable = makeFakeApprovable($requestor, $company);

    $this->assertDatabaseCount('approval_requests', 0);

    try {
        approvalEngine()->submit($approvable, $template);
        $this->fail('Expected a RuntimeException for the missing manager.');
    } catch (RuntimeException) {
        // expected
    }

    $this->assertDatabaseCount('approval_requests', 0);
    $this->assertDatabaseCount('approval_request_steps', 0);
});

// ---------------------------------------------------------------------------
// approve()
// ---------------------------------------------------------------------------

it('marks step as Approved and records action_at and notes', function () {
    [$company, [$requestor, $manager]] = createApprovalManagerChain();
    $template = createSingleStepTemplate($company);
    $request = submitApprovalRequest($company, $requestor, $template);
    $step = $request->steps()->first();

    approvalEngine()->approve($step, $manager, 'Looks good');
    $step->refresh();

    expect($step->state)->toBe(StepState::Approved);
    expect($step->action_at)->not->toBeNull();
    expect($step->notes)->toBe('Looks good');
});

it('dispatches ApprovalStepCompleted event', function () {
    Event::fake();
    [$company, [$requestor, $manager]] = createApprovalManagerChain();
    $template = createSingleStepTemplate($company);
    $request = submitApprovalRequest($company, $requestor, $template);
    $step = $request->steps()->first();

    approvalEngine()->approve($step, $manager);

    Event::assertDispatched(ApprovalStepCompleted::class, fn (ApprovalStepCompleted $event) => $event->step->is($step));
});

it('advances state machine after approval — final step completes request', function () {
    [$company, [$requestor, $manager]] = createApprovalManagerChain();
    $template = createSingleStepTemplate($company);
    $request = submitApprovalRequest($company, $requestor, $template);
    $step = $request->steps()->first();

    approvalEngine()->approve($step, $manager);
    $request->refresh();

    expect($request->status)->toBe(ApprovalStatus::Approved);
    expect($request->completed_at)->not->toBeNull();
});

it('throws RuntimeException when step is not pending (already approved)', function () {
    [$company, [$requestor, $manager]] = createApprovalManagerChain();
    $template = createSingleStepTemplate($company);
    $request = submitApprovalRequest($company, $requestor, $template);
    $step = $request->steps()->first();
    $step->update(['state' => StepState::Approved]);

    expect(fn () => approvalEngine()->approve($step, $manager))
        ->toThrow(RuntimeException::class, 'not in a pending state');
});

it('throws RuntimeException when step is not pending (already rejected)', function () {
    [$company, [$requestor, $manager]] = createApprovalManagerChain();
    $template = createSingleStepTemplate($company);
    $request = submitApprovalRequest($company, $requestor, $template);
    $step = $request->steps()->first();
    $step->update(['state' => StepState::Rejected]);

    expect(fn () => approvalEngine()->approve($step, $manager))
        ->toThrow(RuntimeException::class, 'not in a pending state');
});

it('throws RuntimeException when parent request is not pending (rejected)', function () {
    [$company, [$requestor, $manager]] = createApprovalManagerChain();
    $template = createSingleStepTemplate($company);
    $request = submitApprovalRequest($company, $requestor, $template);
    $step = $request->steps()->first();
    $request->update(['status' => ApprovalStatus::Rejected, 'completed_at' => now()]);

    expect(fn () => approvalEngine()->approve($step, $manager))
        ->toThrow(RuntimeException::class, 'no longer pending');
});

it('throws RuntimeException when parent request is not pending (canceled)', function () {
    [$company, [$requestor, $manager]] = createApprovalManagerChain();
    $template = createSingleStepTemplate($company);
    $request = submitApprovalRequest($company, $requestor, $template);
    $step = $request->steps()->first();
    $request->update(['status' => ApprovalStatus::Canceled, 'canceled_at' => now()]);

    expect(fn () => approvalEngine()->approve($step, $manager))
        ->toThrow(RuntimeException::class, 'no longer pending');
});

// ---------------------------------------------------------------------------
// reject()
// ---------------------------------------------------------------------------

it('marks step as Rejected with reason in notes and sets action_at', function () {
    [$company, [$requestor, $manager]] = createApprovalManagerChain();
    $template = createSingleStepTemplate($company);
    $request = submitApprovalRequest($company, $requestor, $template);
    $step = $request->steps()->first();

    approvalEngine()->reject($step, $manager, 'Not acceptable');
    $step->refresh();

    expect($step->state)->toBe(StepState::Rejected);
    expect($step->notes)->toBe('Not acceptable');
    expect($step->action_at)->not->toBeNull();
});

it('sets request status to Rejected with completed_at', function () {
    [$company, [$requestor, $manager]] = createApprovalManagerChain();
    $template = createSingleStepTemplate($company);
    $request = submitApprovalRequest($company, $requestor, $template);
    $step = $request->steps()->first();

    approvalEngine()->reject($step, $manager, 'No');
    $request->refresh();

    expect($request->status)->toBe(ApprovalStatus::Rejected);
    expect($request->completed_at)->not->toBeNull();
});

it('marks remaining Pending steps as Skipped', function () {
    [$company, [$requestor, $manager]] = createApprovalManagerChain();
    $template = createParallelTemplate($company, 2);
    $request = submitApprovalRequest($company, $requestor, $template);
    $steps = $request->steps()->orderBy('order')->get();

    approvalEngine()->reject($steps[0], $manager, 'No');
    $steps[1]->refresh();

    expect($steps[1]->state)->toBe(StepState::Skipped);
});

it('marks remaining AwaitingPrevious steps as Skipped', function () {
    [$company, [$requestor, $manager]] = createApprovalManagerChain();
    $template = createAwaitingPreviousTemplate($company);
    $request = submitApprovalRequest($company, $requestor, $template);
    $steps = $request->steps()->orderBy('order')->get();

    approvalEngine()->reject($steps[0], $manager, 'No');
    $steps[1]->refresh();

    expect($steps[1]->state)->toBe(StepState::Skipped);
});

it('does NOT modify already Approved steps in the same group', function () {
    [$company, [$requestor, $manager]] = createApprovalManagerChain();
    $template = createParallelTemplate($company, 3);
    $request = submitApprovalRequest($company, $requestor, $template);
    $steps = $request->steps()->orderBy('order')->get();

    $steps[0]->update(['state' => StepState::Approved, 'action_at' => now()]);

    approvalEngine()->reject($steps[1], $manager, 'No');
    $steps[0]->refresh();
    $steps[2]->refresh();

    expect($steps[0]->state)->toBe(StepState::Approved);
    expect($steps[2]->state)->toBe(StepState::Skipped);
});

it('dispatches ApprovalRejected event', function () {
    Event::fake();
    [$company, [$requestor, $manager]] = createApprovalManagerChain();
    $template = createSingleStepTemplate($company);
    $request = submitApprovalRequest($company, $requestor, $template);
    $step = $request->steps()->first();

    approvalEngine()->reject($step, $manager, 'No');

    Event::assertDispatched(ApprovalRejected::class, fn (ApprovalRejected $event) => $event->step->is($step));
});

// ---------------------------------------------------------------------------
// cancel()
// ---------------------------------------------------------------------------

it('cancels pending request and records canceled_at', function () {
    [$company, [$requestor]] = createApprovalManagerChain();
    $template = createSingleStepTemplate($company);
    $request = submitApprovalRequest($company, $requestor, $template);

    approvalEngine()->cancel($request, $requestor);
    $request->refresh();

    expect($request->status)->toBe(ApprovalStatus::Canceled);
    expect($request->canceled_at)->not->toBeNull();
});

it('dispatches ApprovalCanceled event', function () {
    Event::fake();
    [$company, [$requestor]] = createApprovalManagerChain();
    $template = createSingleStepTemplate($company);
    $request = submitApprovalRequest($company, $requestor, $template);

    approvalEngine()->cancel($request, $requestor);

    Event::assertDispatched(ApprovalCanceled::class, fn ($event) => $event->approvalRequest->is($request) && $event->canceledBy->is($requestor));
});

it('throws RuntimeException when request is already approved', function () {
    [$company, [$requestor]] = createApprovalManagerChain();
    $template = createSingleStepTemplate($company);
    $request = submitApprovalRequest($company, $requestor, $template);
    $request->update(['status' => ApprovalStatus::Approved, 'completed_at' => now()]);

    expect(fn () => approvalEngine()->cancel($request, $requestor))
        ->toThrow(RuntimeException::class, 'cannot be canceled');
});

it('throws RuntimeException when request is already rejected', function () {
    [$company, [$requestor]] = createApprovalManagerChain();
    $template = createSingleStepTemplate($company);
    $request = submitApprovalRequest($company, $requestor, $template);
    $request->update(['status' => ApprovalStatus::Rejected, 'completed_at' => now()]);

    expect(fn () => approvalEngine()->cancel($request, $requestor))
        ->toThrow(RuntimeException::class, 'cannot be canceled');
});

// ---------------------------------------------------------------------------
// delegate()
// ---------------------------------------------------------------------------

it('reassigns approver_id and records delegated_from_id', function () {
    [$company, [$requestor, $manager]] = createApprovalManagerChain();
    $template = createSingleStepTemplate($company);
    $request = submitApprovalRequest($company, $requestor, $template);
    $step = $request->steps()->first();
    $newApprover = User::factory()->create();

    approvalEngine()->delegate($step, $newApprover, $manager);
    $step->refresh();

    expect($step->approver_id)->toBe($newApprover->id);
    expect($step->delegated_from_id)->toBe($manager->id);
});

it('dispatches ApprovalDelegated event', function () {
    Event::fake();
    [$company, [$requestor, $manager]] = createApprovalManagerChain();
    $template = createSingleStepTemplate($company);
    $request = submitApprovalRequest($company, $requestor, $template);
    $step = $request->steps()->first();
    $newApprover = User::factory()->create();

    approvalEngine()->delegate($step, $newApprover, $manager);

    Event::assertDispatched(ApprovalDelegated::class, fn (ApprovalDelegated $event) => $event->step->is($step) && $event->delegatedBy->is($manager) && $event->newApprover->is($newApprover));
});

it('throws RuntimeException when step is not pending', function () {
    [$company, [$requestor, $manager]] = createApprovalManagerChain();
    $template = createSingleStepTemplate($company);
    $request = submitApprovalRequest($company, $requestor, $template);
    $step = $request->steps()->first();
    $step->update(['state' => StepState::Approved]);
    $newApprover = User::factory()->create();

    expect(fn () => approvalEngine()->delegate($step, $newApprover, $manager))
        ->toThrow(RuntimeException::class, 'not in a pending state');
});

it('throws RuntimeException when parent request is not pending', function () {
    [$company, [$requestor, $manager]] = createApprovalManagerChain();
    $template = createSingleStepTemplate($company);
    $request = submitApprovalRequest($company, $requestor, $template);
    $step = $request->steps()->first();
    $request->update(['status' => ApprovalStatus::Rejected, 'completed_at' => now()]);
    $newApprover = User::factory()->create();

    expect(fn () => approvalEngine()->delegate($step, $newApprover, $manager))
        ->toThrow(RuntimeException::class, 'no longer pending');
});

// ---------------------------------------------------------------------------
// resolvePreviousManager()
// ---------------------------------------------------------------------------

it('resolves AwaitingPrevious step to Pending with new approver', function () {
    [$company, [$requestor]] = createApprovalManagerChain();
    $template = createAwaitingPreviousTemplate($company);
    $request = submitApprovalRequest($company, $requestor, $template);
    $step = $request->steps()->orderBy('order')->get()[1];
    $newApprover = User::factory()->create();

    approvalEngine()->resolvePreviousManager($step, $requestor, $newApprover);
    $step->refresh();

    expect($step->state)->toBe(StepState::Pending);
    expect($step->approver_id)->toBe($newApprover->id);
});

it('records delegated_from_id on resolved step', function () {
    [$company, [$requestor]] = createApprovalManagerChain();
    $template = createAwaitingPreviousTemplate($company);
    $request = submitApprovalRequest($company, $requestor, $template);
    $step = $request->steps()->orderBy('order')->get()[1];
    $newApprover = User::factory()->create();

    approvalEngine()->resolvePreviousManager($step, $requestor, $newApprover);
    $step->refresh();

    expect($step->delegated_from_id)->toBe($requestor->id);
});

it('dispatches ApprovalDelegated when resolving previous manager', function () {
    Event::fake();
    [$company, [$requestor]] = createApprovalManagerChain();
    $template = createAwaitingPreviousTemplate($company);
    $request = submitApprovalRequest($company, $requestor, $template);
    $step = $request->steps()->orderBy('order')->get()[1];
    $newApprover = User::factory()->create();

    approvalEngine()->resolvePreviousManager($step, $requestor, $newApprover);

    Event::assertDispatched(ApprovalDelegated::class, fn (ApprovalDelegated $event) => $event->step->is($step) && $event->delegatedBy->is($requestor) && $event->newApprover->is($newApprover));
});

it('throws RuntimeException when step is not AwaitingPrevious', function () {
    [$company, [$requestor]] = createApprovalManagerChain();
    $template = createAwaitingPreviousTemplate($company);
    $request = submitApprovalRequest($company, $requestor, $template);
    $step = $request->steps()->orderBy('order')->get()[0];
    $newApprover = User::factory()->create();

    expect(fn () => approvalEngine()->resolvePreviousManager($step, $requestor, $newApprover))
        ->toThrow(RuntimeException::class, 'not awaiting previous approver resolution');
});

// ---------------------------------------------------------------------------
// advanceStateMachine()
// ---------------------------------------------------------------------------

it('completes request when last step in single sequential group is approved', function () {
    [$company, [$requestor, $manager]] = createApprovalManagerChain();
    $template = createSingleStepTemplate($company);
    $request = submitApprovalRequest($company, $requestor, $template);
    $step = $request->steps()->first();

    approvalEngine()->approve($step, $manager);
    $request->refresh();

    expect($request->status)->toBe(ApprovalStatus::Approved);
});

it('completes request only when ALL steps in parallel group are approved', function () {
    [$company, [$requestor, $manager]] = createApprovalManagerChain();
    $template = createParallelTemplate($company, 2);
    $request = submitApprovalRequest($company, $requestor, $template);
    $steps = $request->steps()->orderBy('order')->get();

    approvalEngine()->approve($steps[0], $manager);
    $request->refresh();
    expect($request->status)->toBe(ApprovalStatus::Pending);

    approvalEngine()->approve($steps[1], $manager);
    $request->refresh();
    expect($request->status)->toBe(ApprovalStatus::Approved);
});

it('advances to next group when current sequential group is fully approved', function () {
    [$company, [$requestor, $manager]] = createApprovalManagerChain();
    $template = createTwoGroupTemplate($company);
    $request = submitApprovalRequest($company, $requestor, $template);
    $steps = $request->steps()->orderBy('id')->get();

    approvalEngine()->approve($steps[0], $manager);
    $request->refresh();
    expect($request->status)->toBe(ApprovalStatus::Pending);

    approvalEngine()->approve($steps[1]->fresh(), $manager);
    $request->refresh();
    expect($request->status)->toBe(ApprovalStatus::Approved);
});

it('resolves N+1_of_previous step when its predecessor in sequential group is approved', function () {
    [$company, [$requestor, $manager, $seniorManager]] = createApprovalManagerChain(2);
    $template = createAwaitingPreviousTemplate($company);
    $request = submitApprovalRequest($company, $requestor, $template);
    $steps = $request->steps()->orderBy('order')->get();

    approvalEngine()->approve($steps[0], $manager);
    $steps[1]->refresh();

    expect($steps[1]->state)->toBe(StepState::Pending);
    expect($steps[1]->approver_id)->toBe($seniorManager->id);
});

it('auto-approves N+1_of_previous step when previous approver has no manager', function () {
    [$company, [$requestor, $manager]] = createApprovalManagerChain(1);
    $template = createAwaitingPreviousTemplate($company);
    $request = submitApprovalRequest($company, $requestor, $template);
    $steps = $request->steps()->orderBy('order')->get();

    approvalEngine()->approve($steps[0], $manager);
    $steps[1]->refresh();

    expect($steps[1]->state)->toBe(StepState::Approved);
    expect($steps[1]->notes)->toContain('Auto-skipped');

    $request->refresh();
    expect($request->status)->toBe(ApprovalStatus::Approved);
});

it('stops advancement when any step in group is rejected', function () {
    [$company, [$requestor, $manager]] = createApprovalManagerChain();
    $template = createSingleStepTemplate($company);

    $request = ApprovalRequest::factory()->create([
        'company_id' => $company->id,
        'requestor_id' => $requestor->id,
        'workflow_template_id' => $template->id,
        'workflow_snapshot' => [
            'template_name' => $template->name,
            'groups' => [
                [
                    'name' => 'Group 1',
                    'type' => GroupType::Sequential->value,
                    'steps' => [
                        ['label' => 'Step 1', 'order' => 0, 'resolver_type' => 'N+1', 'resolver_payload' => []],
                        ['label' => 'Step 2', 'order' => 1, 'resolver_type' => 'N+1', 'resolver_payload' => []],
                    ],
                ],
            ],
        ],
    ]);

    ApprovalRequestStep::factory()->rejected()->create([
        'approval_request_id' => $request->id,
        'group_name' => 'Group 1',
        'order' => 0,
        'approver_id' => $manager->id,
    ]);
    $pendingStep = ApprovalRequestStep::factory()->create([
        'approval_request_id' => $request->id,
        'group_name' => 'Group 1',
        'order' => 1,
        'approver_id' => $manager->id,
    ]);

    approvalEngine()->approve($pendingStep, $manager);
    $request->refresh();

    expect($request->status)->toBe(ApprovalStatus::Pending);
});

it('dispatches ApprovalCompleted when final group completes', function () {
    Event::fake();
    [$company, [$requestor, $manager]] = createApprovalManagerChain();
    $template = createSingleStepTemplate($company);
    $request = submitApprovalRequest($company, $requestor, $template);
    $step = $request->steps()->first();

    approvalEngine()->approve($step, $manager);

    Event::assertDispatched(ApprovalCompleted::class, fn (ApprovalCompleted $event) => $event->approvalRequest->is($request));
});

it('completes request and auto-skips remaining steps when parallel group min_approvals threshold is met', function () {
    [$company, [$requestor, $manager]] = createApprovalManagerChain();
    $template = createParallelTemplate($company, 3, minApprovals: 1);
    $request = submitApprovalRequest($company, $requestor, $template);
    $steps = $request->steps()->orderBy('order')->get();

    expect($steps)->toHaveCount(3);
    expect($steps[0]->state)->toBe(StepState::Pending);
    expect($steps[1]->state)->toBe(StepState::Pending);
    expect($steps[2]->state)->toBe(StepState::Pending);

    // Approve only the first step
    approvalEngine()->approve($steps[0], $manager);
    $request->refresh();

    // The whole request should now be approved
    expect($request->status)->toBe(ApprovalStatus::Approved);

    // Step 0 is approved, steps 1 and 2 should be auto-skipped
    expect($steps[0]->fresh()->state)->toBe(StepState::Approved);
    expect($steps[1]->fresh()->state)->toBe(StepState::Skipped);
    expect($steps[1]->fresh()->notes)->toContain('Auto-skipped');
    expect($steps[2]->fresh()->state)->toBe(StepState::Skipped);
    expect($steps[2]->fresh()->notes)->toContain('Auto-skipped');
});

it('waits for quorum when parallel group has min_approvals greater than 1', function () {
    [$company, [$requestor, $manager]] = createApprovalManagerChain();
    $template = createParallelTemplate($company, 3, minApprovals: 2);
    $request = submitApprovalRequest($company, $requestor, $template);
    $steps = $request->steps()->orderBy('order')->get();

    // First approval: still pending
    approvalEngine()->approve($steps[0], $manager);
    $request->refresh();
    expect($request->status)->toBe(ApprovalStatus::Pending);
    expect($steps[1]->fresh()->state)->toBe(StepState::Pending);
    expect($steps[2]->fresh()->state)->toBe(StepState::Pending);

    // Second approval (threshold 2 reached): completes request and skips 3rd step
    approvalEngine()->approve($steps[1], $manager);
    $request->refresh();
    expect($request->status)->toBe(ApprovalStatus::Approved);
    expect($steps[0]->fresh()->state)->toBe(StepState::Approved);
    expect($steps[1]->fresh()->state)->toBe(StepState::Approved);
    expect($steps[2]->fresh()->state)->toBe(StepState::Skipped);
});

it('advances to next sequential group when parallel group min_approvals is met', function () {
    [$company, [$requestor, $manager]] = createApprovalManagerChain();

    $template = WorkflowTemplate::factory()->create(['company_id' => $company->id]);
    $g1 = WorkflowGroup::factory()->parallel(1)->create(['template_id' => $template->id, 'name' => 'Group 1', 'order' => 0]);
    $g2 = WorkflowGroup::factory()->sequential()->create(['template_id' => $template->id, 'name' => 'Group 2', 'order' => 1]);

    WorkflowStep::factory()->nPlus1()->create(['group_id' => $g1->id, 'order' => 0]);
    WorkflowStep::factory()->nPlus1()->create(['group_id' => $g1->id, 'order' => 1]);
    WorkflowStep::factory()->nPlus1()->create(['group_id' => $g2->id, 'order' => 0]);

    $request = submitApprovalRequest($company, $requestor, $template);

    $g1Steps = $request->steps()->where('group_name', 'Group 1')->orderBy('order')->get();
    $g2Steps = $request->steps()->where('group_name', 'Group 2')->orderBy('order')->get();

    // Initial state: Group 1 (parallel) steps are Pending, Group 2 steps are AwaitingPrevious
    expect($g1Steps[0]->state)->toBe(StepState::Pending);
    expect($g1Steps[1]->state)->toBe(StepState::Pending);
    expect($g2Steps[0]->state)->toBe(StepState::AwaitingPrevious);

    // Approving Group 1 step 0 satisfies Group 1 (min_approvals = 1)
    approvalEngine()->approve($g1Steps[0], $manager);
    $request->refresh();

    expect($request->status)->toBe(ApprovalStatus::Pending);
    expect($g1Steps[0]->fresh()->state)->toBe(StepState::Approved);
    expect($g1Steps[1]->fresh()->state)->toBe(StepState::Skipped);
    expect($g2Steps[0]->fresh()->state)->toBe(StepState::Pending);

    // Now approve Group 2 step
    approvalEngine()->approve($g2Steps[0]->fresh(), $manager);
    $request->refresh();
    expect($request->status)->toBe(ApprovalStatus::Approved);
});

it('rejects entire request immediately when an approver rejects in a parallel group with min_approvals', function () {
    [$company, [$requestor, $manager]] = createApprovalManagerChain();
    $template = createParallelTemplate($company, 3, minApprovals: 1);
    $request = submitApprovalRequest($company, $requestor, $template);
    $steps = $request->steps()->orderBy('order')->get();

    approvalEngine()->reject($steps[0], $manager, 'Disapproved finance item');
    $request->refresh();

    expect($request->status)->toBe(ApprovalStatus::Rejected);
    expect($steps[0]->fresh()->state)->toBe(StepState::Rejected);
    expect($steps[1]->fresh()->state)->toBe(StepState::Skipped);
    expect($steps[2]->fresh()->state)->toBe(StepState::Skipped);
});
