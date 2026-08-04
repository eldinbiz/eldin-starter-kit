<?php

declare(strict_types=1);

use App\Events\Approval\ApprovalCanceled;
use App\Events\Approval\ApprovalCompleted;
use App\Events\Approval\ApprovalDelegated;
use App\Events\Approval\ApprovalRejected;
use App\Events\Approval\ApprovalRequested;
use App\Events\Approval\ApprovalStepCompleted;
use App\Listeners\Approval\UpdateApprovableStatus;
use App\Models\Approval\ApprovalRequest;
use App\Models\Approval\ApprovalRequestStep;
use App\Models\Approval\WorkflowGroup;
use App\Models\Approval\WorkflowStep;
use App\Models\Approval\WorkflowTemplate;
use App\Models\Company;
use App\Models\User;
use App\Services\ApprovalEngine;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\Support\FakeApprovable;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function createApprovalEventBase(): array
{
    $company = Company::factory()->create(['is_active' => true]);

    $requestor = User::factory()->create();
    $manager = User::factory()->create();

    $company->users()->attach($requestor->id, ['manager_id' => $manager->id, 'status' => 'active']);
    $company->users()->attach($manager->id, ['manager_id' => null, 'status' => 'active']);

    return [$company, $requestor, $manager];
}

function createApprovalEventTemplate(Company $company): WorkflowTemplate
{
    $template = WorkflowTemplate::factory()->create(['company_id' => $company->id]);
    $group = WorkflowGroup::factory()->create(['template_id' => $template->id, 'order' => 0]);
    WorkflowStep::factory()->nPlus1()->create(['group_id' => $group->id, 'order' => 0]);

    return $template;
}

/**
 * Create a persisted request whose approvable is a FakeApprovable.
 *
 * @return array{0: FakeApprovable, 1: ApprovalRequest}
 */
function createApprovalEventRequest(User $requestor, Company $company): array
{
    $approvable = FakeApprovable::createFor($requestor, $company);
    $request = ApprovalRequest::factory()->create([
        'company_id' => $company->id,
        'requestor_id' => $requestor->id,
        'approvable_type' => FakeApprovable::class,
        'approvable_id' => $approvable->id,
        'workflow_snapshot' => ['template_name' => 'Test Workflow', 'groups' => []],
    ]);

    return [$approvable, $request];
}

function submitApprovalEventRequest(Company $company, User $requestor, WorkflowTemplate $template): ApprovalRequest
{
    $approvable = FakeApprovable::createFor($requestor, $company);

    return app(ApprovalEngine::class)->submit($approvable, $template);
}

beforeEach(function (): void {
    FakeApprovable::ensureTable();
});

// ---------------------------------------------------------------------------
// Event dispatch
// ---------------------------------------------------------------------------

it('dispatches ApprovalRequested when submitting a request', function () {
    Event::fake();
    [$company, $requestor] = createApprovalEventBase();
    $template = createApprovalEventTemplate($company);

    $request = submitApprovalEventRequest($company, $requestor, $template);

    Event::assertDispatched(ApprovalRequested::class, fn (ApprovalRequested $event) => $event->approvalRequest->is($request));
});

it('dispatches ApprovalStepCompleted when approving a step', function () {
    Event::fake();
    [$company, $requestor, $manager] = createApprovalEventBase();
    $template = createApprovalEventTemplate($company);
    $request = submitApprovalEventRequest($company, $requestor, $template);
    $step = $request->steps()->first();

    app(ApprovalEngine::class)->approve($step, $manager);

    Event::assertDispatched(ApprovalStepCompleted::class, fn (ApprovalStepCompleted $event) => $event->step->is($step));
});

it('dispatches ApprovalCompleted when final group finishes', function () {
    Event::fake();
    [$company, $requestor, $manager] = createApprovalEventBase();
    $template = createApprovalEventTemplate($company);
    $request = submitApprovalEventRequest($company, $requestor, $template);
    $step = $request->steps()->first();

    app(ApprovalEngine::class)->approve($step, $manager);

    Event::assertDispatched(ApprovalCompleted::class, fn (ApprovalCompleted $event) => $event->approvalRequest->is($request));
});

it('dispatches ApprovalRejected when rejecting — passes the rejecting step', function () {
    Event::fake();
    [$company, $requestor, $manager] = createApprovalEventBase();
    $template = createApprovalEventTemplate($company);
    $request = submitApprovalEventRequest($company, $requestor, $template);
    $step = $request->steps()->first();

    app(ApprovalEngine::class)->reject($step, $manager, 'Rejected');

    Event::assertDispatched(ApprovalRejected::class, fn (ApprovalRejected $event) => $event->step->is($step));
});

it('dispatches ApprovalCanceled when canceling — passes request and user', function () {
    Event::fake();
    [$company, $requestor, $manager] = createApprovalEventBase();
    $template = createApprovalEventTemplate($company);
    $request = submitApprovalEventRequest($company, $requestor, $template);

    app(ApprovalEngine::class)->cancel($request, $requestor);

    Event::assertDispatched(ApprovalCanceled::class, fn (ApprovalCanceled $event) => $event->approvalRequest->is($request) && $event->canceledBy->is($requestor));
});

it('dispatches ApprovalDelegated when delegating — passes step, from, to', function () {
    Event::fake();
    [$company, $requestor, $manager] = createApprovalEventBase();
    $template = createApprovalEventTemplate($company);
    $request = submitApprovalEventRequest($company, $requestor, $template);
    $step = $request->steps()->first();
    $newApprover = User::factory()->create();

    app(ApprovalEngine::class)->delegate($step, $newApprover, $manager);

    Event::assertDispatched(ApprovalDelegated::class, fn (ApprovalDelegated $event) => $event->step->is($step) && $event->delegatedBy->is($manager) && $event->newApprover->is($newApprover));
});

it('dispatches ApprovalDelegated when resolving previous manager', function () {
    Event::fake();
    [$company, $requestor, $manager] = createApprovalEventBase();

    $template = WorkflowTemplate::factory()->create(['company_id' => $company->id]);
    $group = WorkflowGroup::factory()->create(['template_id' => $template->id, 'order' => 0]);
    WorkflowStep::factory()->nPlus1()->create(['group_id' => $group->id, 'order' => 0]);
    WorkflowStep::factory()->nPlus1OfPrevious()->create(['group_id' => $group->id, 'order' => 1]);

    $request = submitApprovalEventRequest($company, $requestor, $template);
    $step = $request->steps()->orderBy('order')->get()[1];
    $newApprover = User::factory()->create();

    app(ApprovalEngine::class)->resolvePreviousManager($step, $requestor, $newApprover);

    Event::assertDispatched(ApprovalDelegated::class, fn (ApprovalDelegated $event) => $event->step->is($step) && $event->delegatedBy->is($requestor) && $event->newApprover->is($newApprover));
});

// ---------------------------------------------------------------------------
// UpdateApprovableStatus listener
// ---------------------------------------------------------------------------

it('calls onApprovalCompleted on the approvable model when ApprovalCompleted fires', function () {
    [$company, $requestor] = createApprovalEventBase();
    [$approvable, $request] = createApprovalEventRequest($requestor, $company);

    event(new ApprovalCompleted($request));

    $this->assertDatabaseHas('fake_approvables', ['id' => $approvable->id, 'is_completed' => 1]);
});

it('calls onApprovalRejected on the approvable model when ApprovalRejected fires', function () {
    [$company, $requestor] = createApprovalEventBase();
    [$approvable, $request] = createApprovalEventRequest($requestor, $company);

    $step = ApprovalRequestStep::factory()->create(['approval_request_id' => $request->id]);

    event(new ApprovalRejected($step));

    $this->assertDatabaseHas('fake_approvables', ['id' => $approvable->id, 'is_rejected' => 1]);
});

it('calls onApprovalCanceled on the approvable model when ApprovalCanceled fires', function () {
    [$company, $requestor] = createApprovalEventBase();
    [$approvable, $request] = createApprovalEventRequest($requestor, $company);

    event(new ApprovalCanceled($request, $requestor));

    $this->assertDatabaseHas('fake_approvables', ['id' => $approvable->id, 'is_canceled' => 1]);
});

it('does nothing when approvable morph resolves to a non-Approvable model', function () {
    $company = Company::factory()->create(['is_active' => true]);
    $requestor = User::factory()->create();
    $company->users()->attach($requestor->id, ['status' => 'active']);
    $someUser = User::factory()->create();

    $request = ApprovalRequest::factory()->create([
        'company_id' => $company->id,
        'requestor_id' => $requestor->id,
        'approvable_type' => User::class,
        'approvable_id' => $someUser->id,
        'workflow_snapshot' => ['template_name' => 'Test Workflow', 'groups' => []],
    ]);

    event(new ApprovalCompleted($request));

    $this->assertDatabaseCount('fake_approvables', 0);
});

it('implements ShouldHandleEventsAfterCommit', function () {
    $reflection = new ReflectionClass(UpdateApprovableStatus::class);

    expect($reflection->implementsInterface(ShouldHandleEventsAfterCommit::class))->toBeTrue();
});

it('fires the listener after the wrapping database transaction commits', function () {
    [$company, $requestor] = createApprovalEventBase();
    [$approvable, $request] = createApprovalEventRequest($requestor, $company);

    DB::transaction(function () use ($request, $approvable): void {
        event(new ApprovalCompleted($request));

        // Listener callback is deferred until the transaction commits.
        $this->assertDatabaseHas('fake_approvables', ['id' => $approvable->id, 'is_completed' => 0]);
    });

    $this->assertDatabaseHas('fake_approvables', ['id' => $approvable->id, 'is_completed' => 1]);
});
