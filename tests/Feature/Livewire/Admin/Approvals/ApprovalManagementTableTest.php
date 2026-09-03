<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire\Admin\Approvals;

use App\Enums\ApprovalStatus;
use App\Enums\StepState;
use App\Models\Approval\ApprovalRequest;
use App\Models\Approval\ApprovalRequestStep;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create(['name' => 'Acme Corp', 'is_active' => true]);

    $this->adminRole = Role::firstOrCreate(
        ['name' => 'admin', 'label' => 'Administrator', 'is_active' => true],
        ['permissions' => ['*']]
    );
    $this->adminRole->update(['permissions' => ['*']]);

    $this->admin = User::factory()->create(['name' => 'Admin User', 'email' => 'admin@acme.com']);
    $this->admin->companies()->attach($this->company->id, ['status' => 'active']);
    $this->admin->companyRoles()->attach($this->adminRole->id, ['company_id' => $this->company->id]);
});

it('renders approval management table with pending steps', function () {
    $this->actingAs($this->admin);
    session(['active_company_id' => $this->company->id]);

    $request = ApprovalRequest::factory()->create([
        'company_id' => $this->company->id,
        'requestor_id' => $this->admin->id,
        'status' => ApprovalStatus::Pending,
    ]);

    ApprovalRequestStep::factory()->pending()->create([
        'approval_request_id' => $request->id,
        'approver_id' => $this->admin->id,
        'step_label' => 'Operations Review',
    ]);

    Livewire::test('admin.approvals.approval-management-table')
        ->assertSuccessful()
        ->assertSee('Operations Review')
        ->assertSee('Pending')
        ->assertSee($this->admin->name);
});

it('renders filter drawer with client-side alpine bindings', function () {
    $this->actingAs($this->admin);
    session(['active_company_id' => $this->company->id]);

    Livewire::test('admin.approvals.approval-management-table')
        ->assertSuccessful()
        ->assertSeeHtml('x-show="filterPanelOpen"')
        ->assertSeeHtml('filterPanelOpen = !filterPanelOpen')
        ->assertSee('Step State')
        ->assertSee('Request Status')
        ->assertSee('All States');
});

it('searches steps by step label, requestor, or approver name', function () {
    $this->actingAs($this->admin);
    session(['active_company_id' => $this->company->id]);

    $approver1 = User::factory()->create(['name' => 'Alice Walker']);
    $this->company->users()->attach($approver1->id, ['status' => 'active']);

    $approver2 = User::factory()->create(['name' => 'Bob Builder']);
    $this->company->users()->attach($approver2->id, ['status' => 'active']);

    $request = ApprovalRequest::factory()->create([
        'company_id' => $this->company->id,
        'requestor_id' => $this->admin->id,
        'status' => ApprovalStatus::Pending,
    ]);

    ApprovalRequestStep::factory()->pending()->create([
        'approval_request_id' => $request->id,
        'approver_id' => $approver1->id,
        'step_label' => 'Finance Audit',
    ]);

    ApprovalRequestStep::factory()->pending()->create([
        'approval_request_id' => $request->id,
        'approver_id' => $approver2->id,
        'step_label' => 'Legal Compliance',
    ]);

    Livewire::test('admin.approvals.approval-management-table')
        ->set('search', 'Finance')
        ->assertSee('Finance Audit')
        ->assertDontSee('Legal Compliance')
        ->set('search', 'Builder')
        ->assertSee('Legal Compliance')
        ->assertDontSee('Finance Audit');
});

it('filters steps by step_state and request_status', function () {
    $this->actingAs($this->admin);
    session(['active_company_id' => $this->company->id]);

    $request = ApprovalRequest::factory()->create([
        'company_id' => $this->company->id,
        'requestor_id' => $this->admin->id,
        'status' => ApprovalStatus::Pending,
    ]);

    ApprovalRequestStep::factory()->pending()->create([
        'approval_request_id' => $request->id,
        'step_label' => 'Pending Step',
    ]);

    ApprovalRequestStep::factory()->approved()->create([
        'approval_request_id' => $request->id,
        'step_label' => 'Approved Step',
    ]);

    Livewire::test('admin.approvals.approval-management-table')
        ->set('filterValues.step_state', 'approved')
        ->assertSee('Approved Step')
        ->assertDontSee('Pending Step');
});

it('can open delegate modal and delegate step to another user', function () {
    $this->actingAs($this->admin);
    session(['active_company_id' => $this->company->id]);

    $approver = User::factory()->create(['name' => 'Original Approver']);
    $this->company->users()->attach($approver->id, ['status' => 'active']);

    $newApprover = User::factory()->create(['name' => 'New Delegate Approver']);
    $this->company->users()->attach($newApprover->id, ['status' => 'active']);

    $request = ApprovalRequest::factory()->create([
        'company_id' => $this->company->id,
        'requestor_id' => $this->admin->id,
        'status' => ApprovalStatus::Pending,
    ]);

    $step = ApprovalRequestStep::factory()->pending()->create([
        'approval_request_id' => $request->id,
        'approver_id' => $approver->id,
        'step_label' => 'Manager Step',
    ]);

    Livewire::test('admin.approvals.approval-management-table')
        ->call('openDelegateModal', $step->id)
        ->assertSet('selectedStepId', $step->id)
        ->assertSet('delegateModalOpen', true)
        ->set('selectedApproverId', $newApprover->id)
        ->call('delegate')
        ->assertSet('delegateModalOpen', false)
        ->assertDispatched('notify');

    $step->refresh();
    expect($step->approver_id)->toBe($newApprover->id)
        ->and($step->delegated_from_id)->toBe($this->admin->id);
});

it('can open skip modal and skip step', function () {
    $this->actingAs($this->admin);
    session(['active_company_id' => $this->company->id]);

    $request = ApprovalRequest::factory()->create([
        'company_id' => $this->company->id,
        'requestor_id' => $this->admin->id,
        'status' => ApprovalStatus::Pending,
    ]);

    $step = ApprovalRequestStep::factory()->pending()->create([
        'approval_request_id' => $request->id,
        'approver_id' => $this->admin->id,
        'step_label' => 'Skippable Step',
    ]);

    Livewire::test('admin.approvals.approval-management-table')
        ->call('openSkipModal', $step->id)
        ->assertSet('selectedStepId', $step->id)
        ->assertSet('skipModalOpen', true)
        ->call('skip')
        ->assertSet('skipModalOpen', false)
        ->assertDispatched('notify');

    $step->refresh();
    expect($step->state)->toBe(StepState::Skipped);
});

it('enforces company boundaries for approval steps', function () {
    $this->actingAs($this->admin);
    session(['active_company_id' => $this->company->id]);

    $otherCompany = Company::factory()->create(['name' => 'Other Corp', 'is_active' => true]);

    $myRequest = ApprovalRequest::factory()->create([
        'company_id' => $this->company->id,
        'requestor_id' => $this->admin->id,
        'status' => ApprovalStatus::Pending,
    ]);
    ApprovalRequestStep::factory()->pending()->create([
        'approval_request_id' => $myRequest->id,
        'step_label' => 'My Company Step',
    ]);

    $otherRequest = ApprovalRequest::factory()->create([
        'company_id' => $otherCompany->id,
        'status' => ApprovalStatus::Pending,
    ]);
    ApprovalRequestStep::factory()->pending()->create([
        'approval_request_id' => $otherRequest->id,
        'step_label' => 'Foreign Company Step',
    ]);

    Livewire::test('admin.approvals.approval-management-table')
        ->assertSee('My Company Step')
        ->assertDontSee('Foreign Company Step');
});
