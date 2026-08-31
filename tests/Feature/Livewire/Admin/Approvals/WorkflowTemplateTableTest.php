<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire\Admin\Approvals;

use App\Enums\TemplateScope;
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

    $this->admin = User::factory()->create(['name' => 'Admin User', 'email' => 'admin@acme.com']);
    $this->admin->companies()->attach($this->company->id, ['status' => 'active']);
    $this->admin->companyRoles()->attach($this->adminRole->id, ['company_id' => $this->company->id]);
});

it('renders workflow template table with active templates', function () {
    $this->actingAs($this->admin);
    session(['active_company_id' => $this->company->id]);

    $template = WorkflowTemplate::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Purchase Order Standard Approval',
        'scope' => TemplateScope::ThisCompany,
        'is_active' => true,
    ]);

    Livewire::test('admin.approvals.workflow-template-table')
        ->assertSuccessful()
        ->assertSee('Purchase Order Standard Approval')
        ->assertSee('Active');
});

it('filters templates by search query', function () {
    $this->actingAs($this->admin);
    session(['active_company_id' => $this->company->id]);

    WorkflowTemplate::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Travel Expense Approval',
        'is_active' => true,
    ]);

    WorkflowTemplate::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Equipment Requisition',
        'is_active' => true,
    ]);

    Livewire::test('admin.approvals.workflow-template-table')
        ->set('search', 'Travel')
        ->assertSee('Travel Expense Approval')
        ->assertDontSee('Equipment Requisition');
});

it('filters templates by scope', function () {
    $this->actingAs($this->admin);
    session(['active_company_id' => $this->company->id]);

    WorkflowTemplate::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Global Policy Review',
        'scope' => TemplateScope::Global,
        'is_active' => true,
    ]);

    WorkflowTemplate::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Local Branch Workflow',
        'scope' => TemplateScope::ThisCompany,
        'is_active' => true,
    ]);

    Livewire::test('admin.approvals.workflow-template-table')
        ->set('filterValues.scope', 'global')
        ->assertSee('Global Policy Review')
        ->assertDontSee('Local Branch Workflow');
});

it('toggles template active status', function () {
    $this->actingAs($this->admin);
    session(['active_company_id' => $this->company->id]);

    $template = WorkflowTemplate::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Toggleable Workflow',
        'is_active' => true,
    ]);

    Livewire::test('admin.approvals.workflow-template-table')
        ->call('toggleStatus', $template->id);

    $template->refresh();
    expect($template->is_active)->toBeFalse();

    Livewire::test('admin.approvals.workflow-template-table')
        ->call('toggleStatus', $template->id);

    $template->refresh();
    expect($template->is_active)->toBeTrue();
});

it('trashes and restores workflow templates', function () {
    $this->actingAs($this->admin);
    session(['active_company_id' => $this->company->id]);

    $template = WorkflowTemplate::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Archivable Workflow',
        'is_active' => false,
    ]);

    Livewire::test('admin.approvals.workflow-template-table')
        ->call('trash', $template->id);

    $template->refresh();
    expect($template->trashed())->toBeTrue();

    Livewire::test('admin.approvals.workflow-template-table')
        ->set('filterValues.trashed', true)
        ->assertSee('Archivable Workflow')
        ->call('restore', $template->id);

    $template->refresh();
    expect($template->trashed())->toBeFalse();
});
