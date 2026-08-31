<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire\Dashboard;

use App\Enums\ApprovalStatus;
use App\Models\Approval\ApprovalRequest;
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

    $this->user = User::factory()->create(['name' => 'John Doe', 'email' => 'john@acme.com']);
    $this->user->companies()->attach($this->company->id, ['status' => 'active']);
    $this->user->companyRoles()->attach($this->adminRole->id, ['company_id' => $this->company->id]);
});

it('renders dashboard with company metrics counters', function () {
    $this->actingAs($this->user);
    session(['active_company_id' => $this->company->id]);

    Livewire::test('dashboard.metrics-dashboard')
        ->assertSuccessful()
        ->assertSee('Dashboard Overview')
        ->assertSee('Acme Corp')
        ->assertSee('Company Users')
        ->assertSee('Pending Approvals');
});

it('updates period, recalculates chart data, and dispatches chart-data-updated event', function () {
    $this->actingAs($this->user);
    session(['active_company_id' => $this->company->id]);

    Livewire::test('dashboard.metrics-dashboard')
        ->set('period', '7d')
        ->assertSet('period', '7d')
        ->assertDispatched('chart-data-updated')
        ->call('setPeriod', '90d')
        ->assertSet('period', '90d')
        ->assertDispatched('chart-data-updated');
});

it('correctly calculates pending approvals count', function () {
    $this->actingAs($this->user);
    session(['active_company_id' => $this->company->id]);

    ApprovalRequest::create([
        'company_id' => $this->company->id,
        'requestor_id' => $this->user->id,
        'approvable_type' => 'App\Models\Company',
        'approvable_id' => $this->company->id,
        'workflow_snapshot' => ['groups' => []],
        'status' => ApprovalStatus::Pending,
        'submitted_at' => now(),
    ]);

    $component = Livewire::test('dashboard.metrics-dashboard');
    expect($component->get('metrics')['pending_approvals'])->toBe(1);
});
