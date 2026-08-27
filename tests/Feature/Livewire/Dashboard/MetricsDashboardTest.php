<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire\Dashboard;

use App\Enums\ApprovalStatus;
use App\Livewire\Dashboard\MetricsDashboard;
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

    Livewire::test(MetricsDashboard::class)
        ->assertSuccessful()
        ->assertSee('Dashboard Overview')
        ->assertSee('Acme Corp')
        ->assertSee('Company Users')
        ->assertSee('Pending Approvals');
});

it('updates period and recalculates chart data', function () {
    $this->actingAs($this->user);
    session(['active_company_id' => $this->company->id]);

    Livewire::test(MetricsDashboard::class)
        ->set('period', '7d')
        ->assertSet('period', '7d')
        ->call('setPeriod', '90d')
        ->assertSet('period', '90d');
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

    $component = Livewire::test(MetricsDashboard::class);
    expect($component->get('metrics')['pending_approvals'])->toBe(1);
});
