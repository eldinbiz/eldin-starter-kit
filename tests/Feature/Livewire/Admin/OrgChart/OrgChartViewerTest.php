<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire\Admin\OrgChart;

use App\Actions\OrgChart\TraceReportingLineAction;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create(['name' => 'Acme Global', 'is_active' => true]);

    $this->adminRole = Role::firstOrCreate(['name' => 'sysadmin'], ['permissions' => Role::allPermissions(), 'is_active' => true, 'is_system' => true]);
    $this->admin = User::factory()->create(['name' => 'CEO Admin', 'email' => 'ceo@acme.com']);
    $this->admin->companies()->attach($this->company->id, ['status' => 'active']);
    $this->admin->companyRoles()->attach($this->adminRole->id, ['company_id' => $this->company->id]);

    $this->manager = User::factory()->create(['name' => 'Engineering Director', 'email' => 'dir@acme.com']);
    $this->manager->companies()->attach($this->company->id, ['status' => 'active', 'manager_id' => $this->admin->id]);

    $this->engineer = User::factory()->create(['name' => 'Senior Dev', 'email' => 'dev@acme.com']);
    $this->engineer->companies()->attach($this->company->id, ['status' => 'active', 'manager_id' => $this->manager->id]);
});

it('renders org chart viewer component with active company chart data', function () {
    $this->actingAs($this->admin);
    session(['active_company_id' => $this->company->id]);

    $component = Livewire::test('admin.org-chart.org-chart-viewer')
        ->assertSuccessful()
        ->assertSee('Acme Global');

    $chartData = $component->get('chartData');
    expect($chartData)->not->toBeEmpty();
});

it('reassigns employee manager and updates reporting line', function () {
    $this->actingAs($this->admin);
    session(['active_company_id' => $this->company->id]);

    Livewire::test('admin.org-chart.org-chart-viewer')
        ->call('updateManager', $this->engineer->id, $this->admin->id, 'Lead Dev', 'active')
        ->assertSee('Reporting line successfully updated');

    $membership = $this->engineer->companies()->where('companies.id', $this->company->id)->first()->pivot;
    expect($membership->manager_id)->toBe($this->admin->id)
        ->and($membership->job_title)->toBe('Lead Dev');
});

it('traces reporting line from engineer up to CEO', function () {
    $this->actingAs($this->admin);
    session(['active_company_id' => $this->company->id]);

    $traceAction = app(TraceReportingLineAction::class);
    $component = Livewire::test('admin.org-chart.org-chart-viewer');
    $chain = $component->instance()->traceReportingChain($this->engineer->id, $traceAction);

    expect($chain)->not->toBeEmpty();
    $names = collect($chain)->pluck('name');
    expect($names)->toContain('Senior Dev', 'Engineering Director', 'CEO Admin');
});
