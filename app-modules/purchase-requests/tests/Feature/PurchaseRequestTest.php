<?php

declare(strict_types=1);

use App\Enums\ResolverType;
use App\Models\Approval\WorkflowTemplate;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\PurchaseRequests\Models\PurchaseRequest;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->create();
    $this->user->companies()->attach($this->company->id, ['status' => 'active']);
});

test('user can view purchase requests index page', function (): void {
    $this->actingAs($this->user);
    session(['active_company_id' => $this->company->id]);

    PurchaseRequest::factory()->create([
        'company_id' => $this->company->id,
        'requester_id' => $this->user->id,
        'title' => 'MacBook Pro M3 Max',
    ]);

    $response = $this->get(route('purchase-requests.index'));

    $response->assertOk()
        ->assertSee('Purchase Requests')
        ->assertSee('MacBook Pro M3 Max');
});

test('user can create a draft purchase request', function (): void {
    $this->actingAs($this->user);
    session(['active_company_id' => $this->company->id]);

    $response = $this->post(route('purchase-requests.store'), [
        'title' => 'Ergonomic Desk Setup',
        'department' => 'IT',
        'description' => 'Standing desks and monitors for engineering team',
        'total_amount' => '3500.00',
    ]);

    $pr = PurchaseRequest::where('title', 'Ergonomic Desk Setup')->first();

    expect($pr)->not->toBeNull()
        ->and($pr->company_id)->toBe($this->company->id)
        ->and($pr->requester_id)->toBe($this->user->id)
        ->and($pr->status)->toBe('draft')
        ->and((float) $pr->total_amount)->toBe(3500.00);

    $response->assertRedirect(route('purchase-requests.show', $pr->id));
});

test('user can submit draft purchase request for approval through core engine', function (): void {
    $this->actingAs($this->user);
    session(['active_company_id' => $this->company->id]);

    $approver = User::factory()->create();
    $approver->companies()->attach($this->company->id, ['status' => 'active']);

    $template = WorkflowTemplate::factory()->create([
        'company_id' => $this->company->id,
        'is_active' => true,
    ]);

    $group = $template->groups()->create([
        'name' => 'Management Review',
        'order' => 1,
    ]);

    $group->steps()->create([
        'label' => 'Direct Approval',
        'order' => 1,
        'resolver_type' => ResolverType::SpecificUser,
        'resolver_payload' => ['user_id' => $approver->id],
    ]);

    $pr = PurchaseRequest::factory()->create([
        'company_id' => $this->company->id,
        'requester_id' => $this->user->id,
        'status' => 'draft',
    ]);

    $response = $this->post(route('purchase-requests.submit-for-approval', $pr->id), [
        'template_id' => $template->id,
    ]);

    $response->assertRedirect(route('purchase-requests.show', $pr->id));

    $pr->refresh();
    expect($pr->status)->toBe('pending_approval')
        ->and($pr->isPendingApproval())->toBeTrue();
});
