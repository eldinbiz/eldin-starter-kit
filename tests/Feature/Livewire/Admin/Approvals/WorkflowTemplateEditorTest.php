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

it('renders workflow template editor for creating a new template', function () {
    $this->actingAs($this->admin);
    session(['active_company_id' => $this->company->id]);

    Livewire::test('admin.approvals.workflow-template-editor')
        ->assertSuccessful()
        ->assertSee('New Workflow Template')
        ->assertSee('Group 1')
        ->assertSee('Direct Manager Approval');
});

it('can add and remove step groups', function () {
    $this->actingAs($this->admin);
    session(['active_company_id' => $this->company->id]);

    Livewire::test('admin.approvals.workflow-template-editor')
        ->assertCount('groups', 1)
        ->call('addGroup')
        ->assertCount('groups', 2)
        ->call('removeGroup', 1)
        ->assertCount('groups', 1);
});

it('can add and remove steps within a group', function () {
    $this->actingAs($this->admin);
    session(['active_company_id' => $this->company->id]);

    Livewire::test('admin.approvals.workflow-template-editor')
        ->call('addStep', 0)
        ->assertCount('groups.0.steps', 2)
        ->call('removeStep', 0, 1)
        ->assertCount('groups.0.steps', 1);
});

it('creates a new multi-group workflow template successfully', function () {
    $this->actingAs($this->admin);
    session(['active_company_id' => $this->company->id]);

    $specificApprover = User::factory()->create(['name' => 'Finance Director']);
    $specificApprover->companies()->attach($this->company->id, ['status' => 'active']);

    Livewire::test('admin.approvals.workflow-template-editor')
        ->set('name', 'Capital Expenditure Workflow')
        ->set('description', 'High value capex multi-tier review')
        ->set('scope', TemplateScope::Global->value)
        ->set('groups', [
            [
                'name' => 'Management Tier',
                'type' => 'sequential',
                'min_approvals' => null,
                'steps' => [
                    [
                        'label' => 'Direct Manager',
                        'resolver_type' => 'N+1',
                        'resolver_payload' => ['user_id' => null],
                    ],
                ],
            ],
            [
                'name' => 'Finance & Compliance',
                'type' => 'parallel',
                'min_approvals' => 1,
                'steps' => [
                    [
                        'label' => 'Finance Director',
                        'resolver_type' => 'specific_user',
                        'resolver_payload' => ['user_id' => $specificApprover->id],
                    ],
                ],
            ],
        ])
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.approval-workflows.index'));

    $this->assertDatabaseHas('approval_workflow_templates', [
        'name' => 'Capital Expenditure Workflow',
        'scope' => 'global',
    ]);

    $created = WorkflowTemplate::where('name', 'Capital Expenditure Workflow')->first();
    expect($created)->not->toBeNull();
    expect($created->groups)->toHaveCount(2);
});
