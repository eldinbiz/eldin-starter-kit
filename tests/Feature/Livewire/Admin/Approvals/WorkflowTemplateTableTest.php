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

    $this->adminRole = Role::firstOrCreate(['name' => 'admin'], ['is_active' => true, 'permissions' => ['*']]);
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
    expect($template->trashed())->toBeFalse()
        ->and($template->is_active)->toBeTrue();
});

it('renders filter drawer with client-side alpine bindings and trash filter', function () {
    $this->actingAs($this->admin);
    session(['active_company_id' => $this->company->id]);

    Livewire::test('admin.approvals.workflow-template-table')
        ->assertSuccessful()
        ->assertSeeHtml('x-show="filterPanelOpen"')
        ->assertSeeHtml('filterPanelOpen = !filterPanelOpen')
        ->assertSee('All Approvable Modules')
        ->assertSee('All Statuses')
        ->assertSee('All Scopes')
        ->assertSee('Trash Filter')
        ->assertSee('Show deleted templates only')
        ->assertSeeHtml('data-testid="trashed-filter-checkbox"');
});

it('renders active filter chip for trash filter and removes it on dismiss', function () {
    $this->actingAs($this->admin);
    session(['active_company_id' => $this->company->id]);

    $activeTemplate = WorkflowTemplate::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Active Workflow Item',
        'is_active' => true,
    ]);

    $trashedTemplate = WorkflowTemplate::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Deleted Workflow Item',
        'is_active' => false,
        'deleted_at' => now(),
    ]);

    Livewire::test('admin.approvals.workflow-template-table')
        ->assertSee('Active Workflow Item')
        ->assertDontSee('Deleted Workflow Item')
        ->set('filterValues.trashed', true)
        ->assertSee('Trash Filter')
        ->assertSee('Show deleted only')
        ->assertSee('Deleted Workflow Item')
        ->assertDontSee('Active Workflow Item')
        ->call('removeFilter', 'trashed')
        ->assertSee('Active Workflow Item')
        ->assertDontSee('Deleted Workflow Item');
});

it('bypasses is_active status filter when viewing trashed templates', function () {
    $this->actingAs($this->admin);
    session(['active_company_id' => $this->company->id]);

    $trashedDisabled = WorkflowTemplate::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Disabled Trashed Workflow',
        'is_active' => false,
        'deleted_at' => now(),
    ]);

    Livewire::test('admin.approvals.workflow-template-table')
        ->set('filterValues.is_active', '1')
        ->set('filterValues.trashed', true)
        ->assertSee('Disabled Trashed Workflow');
});

it('dispatches an error notification and prevents trashing active workflow templates', function () {
    $this->actingAs($this->admin);
    session(['active_company_id' => $this->company->id]);

    $template = WorkflowTemplate::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Active Workflow Never Trashed Directly',
        'is_active' => true,
    ]);

    Livewire::test('admin.approvals.workflow-template-table')
        ->call('trash', $template->id)
        ->assertDispatched('notify', message: 'Only disabled templates can be moved to trash. Please disable this template first.', type: 'error');

    $template->refresh();
    expect($template->trashed())->toBeFalse()
        ->and($template->is_active)->toBeTrue();
});

it('renders modal template markup with conditional notice and single close button for active templates', function () {
    $this->actingAs($this->admin);
    session(['active_company_id' => $this->company->id]);

    WorkflowTemplate::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Active Notice Workflow',
        'is_active' => true,
    ]);

    Livewire::test('admin.approvals.workflow-template-table')
        ->assertSuccessful()
        ->assertSeeHtml('x-show="deleteModalOpen"')
        ->assertSeeHtml('targetIsActive: false')
        ->assertSeeHtml("actionType === 'trash' && targetIsActive ? 'Template is Currently Active'")
        ->assertSeeHtml('Only disabled templates can be moved to trash')
        ->assertSeeHtml('x-show="!(actionType === \'trash\' && targetIsActive)"')
        ->assertSeeHtml("x-text=\"actionType === 'trash' && targetIsActive ? 'Close' : 'Cancel'\"");
});

