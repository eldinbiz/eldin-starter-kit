<?php

declare(strict_types=1);

namespace Modules\PurchaseRequests\Tests\Feature;

use App\Enums\ResolverType;
use App\Models\Approval\WorkflowTemplate;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\PurchaseRequests\Models\PurchaseRequest;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->company = Company::factory()->create(['name' => 'Acme Corp', 'is_active' => true]);
    $this->user = User::factory()->create(['name' => 'John Doe', 'email' => 'john@acme.com']);
    $this->user->companies()->attach($this->company->id, ['status' => 'active']);

    $this->approver = User::factory()->create(['name' => 'Manager User', 'email' => 'manager@acme.com']);
    $this->approver->companies()->attach($this->company->id, ['status' => 'active']);

    $this->template = WorkflowTemplate::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Standard Purchase Approval',
        'is_active' => true,
    ]);

    $this->group = $this->template->groups()->create([
        'name' => 'Manager Review',
        'order' => 1,
    ]);

    $this->group->steps()->create([
        'label' => 'Direct Manager',
        'order' => 1,
        'resolver_type' => ResolverType::SpecificUser,
        'resolver_payload' => ['user_id' => $this->approver->id],
    ]);
});

it('renders purchase request wizard on step 1', function () {
    $this->actingAs($this->user);
    session(['active_company_id' => $this->company->id]);

    Livewire::test('purchase-requests.purchase-request-wizard')
        ->assertSuccessful()
        ->assertSee('Step 1: General Information')
        ->assertSet('currentStep', 1);
});

it('calculates dynamic line items total in step 2', function () {
    $this->actingAs($this->user);
    session(['active_company_id' => $this->company->id]);

    Livewire::test('purchase-requests.purchase-request-wizard')
        ->set('title', 'Server Rack Hardware')
        ->set('department', 'IT')
        ->call('nextStep')
        ->assertSet('currentStep', 2)
        ->set('lineItems', [
            [
                'description' => 'Dell PowerEdge R750 Server',
                'quantity' => 2,
                'unit_price' => 4500.00,
            ],
            [
                'description' => 'Cisco Catalyst 48-Port Switch',
                'quantity' => 1,
                'unit_price' => 1800.00,
            ],
        ])
        ->assertSee('$10,800.00'); // 2*4500 + 1800 = 10,800.00
});

it('saves a purchase request as draft through the wizard', function () {
    $this->actingAs($this->user);
    session(['active_company_id' => $this->company->id]);

    Livewire::test('purchase-requests.purchase-request-wizard')
        ->set('title', 'Draft Cloud Infrastructure Request')
        ->set('department', 'Operations')
        ->set('lineItems', [
            [
                'description' => 'AWS Enterprise Support Plan',
                'quantity' => 1,
                'unit_price' => 1500.00,
            ],
        ])
        ->call('saveDraft')
        ->assertHasNoErrors();

    $pr = PurchaseRequest::where('title', 'Draft Cloud Infrastructure Request')->first();
    expect($pr)->not->toBeNull()
        ->and($pr->status)->toBe('draft')
        ->and((float) $pr->total_amount)->toBe(1500.00);
});

it('submits purchase request directly for approval from the wizard', function () {
    $this->actingAs($this->user);
    session(['active_company_id' => $this->company->id]);

    Livewire::test('purchase-requests.purchase-request-wizard')
        ->set('title', 'Production SSD Upgrades')
        ->set('department', 'IT')
        ->set('lineItems', [
            [
                'description' => 'Enterprise NVMe 3.84TB Drives',
                'quantity' => 4,
                'unit_price' => 600.00,
            ],
        ])
        ->set('urgency', 'high')
        ->set('vendorName', 'Samsung B2B')
        ->set('businessJustification', 'Required for DB latency reduction')
        ->set('templateId', $this->template->id)
        ->call('submit')
        ->assertHasNoErrors();

    $pr = PurchaseRequest::where('title', 'Production SSD Upgrades')->first();
    expect($pr)->not->toBeNull()
        ->and($pr->status)->toBe('pending_approval')
        ->and($pr->isPendingApproval())->toBeTrue();
});

it('renders purchase request table and filters by status', function () {
    $this->actingAs($this->user);
    session(['active_company_id' => $this->company->id]);

    PurchaseRequest::factory()->create([
        'company_id' => $this->company->id,
        'requester_id' => $this->user->id,
        'title' => 'Approved Workstation',
        'status' => 'approved',
    ]);

    PurchaseRequest::factory()->create([
        'company_id' => $this->company->id,
        'requester_id' => $this->user->id,
        'title' => 'Draft Office Supplies',
        'status' => 'draft',
    ]);

    Livewire::test('purchase-requests.purchase-request-table')
        ->assertSuccessful()
        ->assertSee('Approved Workstation')
        ->assertSee('Draft Office Supplies')
        ->set('filterValues.status', 'draft')
        ->assertSee('Draft Office Supplies')
        ->assertDontSee('Approved Workstation');
});
