<?php

declare(strict_types=1);

use App\Models\Approval\WorkflowGroup;
use App\Models\Approval\WorkflowStep;
use App\Models\Approval\WorkflowTemplate;
use App\Models\Company;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Services\ApprovalEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('it can list purchase requests for active company', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create();
    $user->companies()->attach($company->id);

    PurchaseRequest::factory()->count(3)->create([
        'company_id' => $company->id,
        'requester_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->withSession(['active_company_id' => $company->id])
        ->get(route('purchase-requests.index'))
        ->assertOk()
        ->assertSee('Purchase Requests');
});

test('it allows user to create a purchase request in draft status', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create();
    $user->companies()->attach($company->id);

    $this->actingAs($user)
        ->withSession(['active_company_id' => $company->id])
        ->post(route('purchase-requests.store'), [
            'title' => 'Server Equipment Purchase',
            'department' => 'IT',
            'total_amount' => 12500.00,
            'description' => 'New rack servers for data center expansion',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('purchase_requests', [
        'company_id' => $company->id,
        'requester_id' => $user->id,
        'title' => 'Server Equipment Purchase',
        'department' => 'IT',
        'status' => 'draft',
    ]);
});

test('it submits purchase request for approval and creates approval request steps', function () {
    $company = Company::factory()->create();
    $manager = User::factory()->create();
    $requestor = User::factory()->create();

    $company->users()->attach($requestor->id, ['manager_id' => $manager->id, 'status' => 'active']);
    $company->users()->attach($manager->id, ['manager_id' => null, 'status' => 'active']);

    $template = WorkflowTemplate::factory()->create([
        'company_id' => $company->id,
        'approvable_type' => PurchaseRequest::class,
        'is_active' => true,
    ]);
    $group = WorkflowGroup::factory()->create(['template_id' => $template->id, 'order' => 0]);
    WorkflowStep::factory()->nPlus1()->create(['group_id' => $group->id, 'order' => 0]);

    $pr = PurchaseRequest::factory()->create([
        'company_id' => $company->id,
        'requester_id' => $requestor->id,
        'status' => 'draft',
    ]);

    $this->actingAs($requestor)
        ->withSession(['active_company_id' => $company->id])
        ->post(route('purchase-requests.submit-for-approval', $pr->id), [
            'template_id' => $template->id,
        ])
        ->assertRedirect(route('purchase-requests.show', $pr->id))
        ->assertSessionHas('success');

    expect($pr->fresh()->status)->toBe('pending_approval');
    expect($pr->approvalRequests()->count())->toBe(1);

    $approvalRequest = $pr->approvalRequests()->first();
    expect($approvalRequest->steps()->count())->toBe(1);
    expect($approvalRequest->steps()->first()->approver_id)->toBe($manager->id);

    // Verify manager received in-app notification
    expect($manager->unreadNotifications()->count())->toBe(1);
    expect($manager->unreadNotifications()->first()->data['title'])->toBe('Approval Requested');
});

test('it updates purchase request status to approved when approval engine completes', function () {
    $company = Company::factory()->create();
    $manager = User::factory()->create();
    $requestor = User::factory()->create();

    $company->users()->attach($requestor->id, ['manager_id' => $manager->id, 'status' => 'active']);
    $company->users()->attach($manager->id, ['manager_id' => null, 'status' => 'active']);

    $template = WorkflowTemplate::factory()->create([
        'company_id' => $company->id,
        'approvable_type' => PurchaseRequest::class,
    ]);
    $group = WorkflowGroup::factory()->create(['template_id' => $template->id, 'order' => 0]);
    WorkflowStep::factory()->nPlus1()->create(['group_id' => $group->id, 'order' => 0]);

    $pr = PurchaseRequest::factory()->create([
        'company_id' => $company->id,
        'requester_id' => $requestor->id,
        'status' => 'draft',
    ]);

    $engine = app(ApprovalEngine::class);
    $approvalRequest = $engine->submit($pr, $template);
    $pr->update(['status' => 'pending_approval']);

    $step = $approvalRequest->steps()->first();
    $engine->approve($step, $manager, 'Approved for purchase');

    expect($pr->fresh()->status)->toBe('approved');
});

test('it updates purchase request status to rejected when an approval step is rejected', function () {
    $company = Company::factory()->create();
    $manager = User::factory()->create();
    $requestor = User::factory()->create();

    $company->users()->attach($requestor->id, ['manager_id' => $manager->id, 'status' => 'active']);
    $company->users()->attach($manager->id, ['manager_id' => null, 'status' => 'active']);

    $template = WorkflowTemplate::factory()->create([
        'company_id' => $company->id,
        'approvable_type' => PurchaseRequest::class,
    ]);
    $group = WorkflowGroup::factory()->create(['template_id' => $template->id, 'order' => 0]);
    WorkflowStep::factory()->nPlus1()->create(['group_id' => $group->id, 'order' => 0]);

    $pr = PurchaseRequest::factory()->create([
        'company_id' => $company->id,
        'requester_id' => $requestor->id,
        'status' => 'draft',
    ]);

    $engine = app(ApprovalEngine::class);
    $approvalRequest = $engine->submit($pr, $template);
    $pr->update(['status' => 'pending_approval']);

    $step = $approvalRequest->steps()->first();
    $engine->reject($step, $manager, 'Over budget');

    expect($pr->fresh()->status)->toBe('rejected');
});
