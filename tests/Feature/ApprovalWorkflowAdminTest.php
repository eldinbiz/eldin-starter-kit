<?php

declare(strict_types=1);

use App\Models\Approval\WorkflowGroup;
use App\Models\Approval\WorkflowStep;
use App\Models\Approval\WorkflowTemplate;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function createApprovalAdminUser(): array
{
    $company = Company::factory()->create(['is_active' => true]);
    Role::factory()->admin()->create();
    Role::factory()->user()->create();

    $user = User::factory()->create();
    $user->companies()->attach($company->id, ['status' => 'active']);
    $user->companyRoles()->attach(
        Role::where('name', 'admin')->value('id'),
        ['company_id' => $company->id]
    );

    return [$user, $company];
}

function actAsApprovalAdmin(TestCase $test): array
{
    [$user, $company] = createApprovalAdminUser();

    $test->actingAs($user);
    session(['active_company_id' => $company->id]);

    return [$user, $company];
}

function actAsApprovalRegularUser(TestCase $test): User
{
    $company = Company::factory()->create(['is_active' => true]);
    Role::factory()->user()->create();

    $user = User::factory()->create();
    $user->companies()->attach($company->id, ['status' => 'active']);
    $user->companyRoles()->attach(
        Role::where('name', 'user')->value('id'),
        ['company_id' => $company->id]
    );

    $test->actingAs($user);
    session(['active_company_id' => $company->id]);

    return $user;
}

function validWorkflowPayload(string $name = 'New Workflow', int $groupCount = 1, int $stepsPerGroup = 1): array
{
    $groups = [];

    for ($g = 0; $g < $groupCount; $g++) {
        $steps = [];

        for ($s = 0; $s < $stepsPerGroup; $s++) {
            $steps[] = [
                'label' => "Step {$g}-{$s}",
                'resolver_type' => 'N+1',
                'resolver_payload' => [],
            ];
        }

        $groups[] = [
            'name' => "Group {$g}",
            'type' => 'sequential',
            'steps' => $steps,
        ];
    }

    return [
        'name' => $name,
        'description' => 'A test workflow',
        'approvable_type' => '',
        'is_active' => true,
        'groups' => $groups,
    ];
}

// ---------------------------------------------------------------------------
// Index
// ---------------------------------------------------------------------------

it('shows list of workflow templates', function () {
    [$user, $company] = actAsApprovalAdmin($this);
    WorkflowTemplate::factory()->create(['company_id' => $company->id, 'name' => 'Purchase Approval']);

    $this->get(route('admin.approval-workflows.index'))
        ->assertSuccessful()
        ->assertSee('Purchase Approval');
});

it('requires authentication', function () {
    $this->get(route('admin.approval-workflows.index'))->assertRedirect(route('login'));
});

it('returns 403 for non-admin users on index and create', function () {
    actAsApprovalRegularUser($this);

    $this->get(route('admin.approval-workflows.index'))->assertForbidden();
    $this->get(route('admin.approval-workflows.create'))->assertForbidden();
});

// ---------------------------------------------------------------------------
// Create
// ---------------------------------------------------------------------------

it('shows create form with approvable type dropdown', function () {
    actAsApprovalAdmin($this);

    $this->get(route('admin.approval-workflows.create'))
        ->assertSuccessful()
        ->assertSee('Any Module');
});

it('returns 403 for non-admin users on create form', function () {
    actAsApprovalRegularUser($this);

    $this->get(route('admin.approval-workflows.create'))->assertForbidden();
});

// ---------------------------------------------------------------------------
// Store
// ---------------------------------------------------------------------------

it('creates template with groups and steps', function () {
    [$user, $company] = actAsApprovalAdmin($this);
    $targetUser = User::factory()->create();
    $company->users()->attach($targetUser->id, ['status' => 'active']);

    $this->get(route('admin.approval-workflows.create'));

    $payload = validWorkflowPayload('New Workflow');
    $payload['groups'][0]['steps'][0]['resolver_type'] = 'specific_user';
    $payload['groups'][0]['steps'][0]['resolver_payload'] = ['user_id' => $targetUser->id];

    $this->post(route('admin.approval-workflows.store'), $payload)
        ->assertRedirect(route('admin.approval-workflows.index'))
        ->assertSessionHas('success', 'Workflow template created successfully.');

    $this->assertDatabaseHas('approval_workflow_templates', ['name' => 'New Workflow', 'company_id' => $company->id]);
    $this->assertDatabaseHas('approval_workflow_groups', ['name' => 'Group 0']);
    $this->assertDatabaseHas('approval_workflow_steps', ['label' => 'Step 0-0', 'resolver_type' => 'specific_user']);
});

it('returns validation error when name is missing', function () {
    actAsApprovalAdmin($this);

    $this->get(route('admin.approval-workflows.create'));

    $payload = validWorkflowPayload();
    $payload['name'] = '';

    $this->post(route('admin.approval-workflows.store'), $payload)
        ->assertSessionHasErrors('name');
});

it('returns validation error when no groups are provided', function () {
    actAsApprovalAdmin($this);

    $this->get(route('admin.approval-workflows.create'));

    $this->post(route('admin.approval-workflows.store'), [
        '_token' => session()->token(),
        'name' => 'No Groups',
        'groups' => [],
    ])->assertSessionHasErrors('groups');
});

it('returns validation error when group has no steps', function () {
    actAsApprovalAdmin($this);

    $this->get(route('admin.approval-workflows.create'));

    $payload = validWorkflowPayload();
    $payload['groups'][0]['steps'] = [];

    $this->post(route('admin.approval-workflows.store'), $payload)
        ->assertSessionHasErrors('groups.0.steps');
});

it('returns 403 for non-admin users on store', function () {
    actAsApprovalRegularUser($this);

    $this->get(route('admin.approval-workflows.create'));

    $this->post(route('admin.approval-workflows.store'), [
        '_token' => session()->token(),
        ...validWorkflowPayload(),
    ])->assertForbidden();
});

// ---------------------------------------------------------------------------
// Edit
// ---------------------------------------------------------------------------

it('shows edit form pre-populated with template data', function () {
    [$user, $company] = actAsApprovalAdmin($this);
    $template = WorkflowTemplate::factory()->create(['company_id' => $company->id, 'name' => 'Existing Workflow']);

    $this->get(route('admin.approval-workflows.edit', $template))
        ->assertSuccessful()
        ->assertSee('Existing Workflow');
});

it('returns 404 for non-existent template', function () {
    actAsApprovalAdmin($this);

    $this->get(route('admin.approval-workflows.edit', 999999))->assertNotFound();
});

// ---------------------------------------------------------------------------
// Update
// ---------------------------------------------------------------------------

it('updates template name, description, and scope', function () {
    [$user, $company] = actAsApprovalAdmin($this);
    $template = WorkflowTemplate::factory()->create(['company_id' => $company->id, 'name' => 'Old Name']);

    $this->get(route('admin.approval-workflows.edit', $template));

    $payload = validWorkflowPayload('Updated Name');
    $this->put(route('admin.approval-workflows.update', $template), $payload)
        ->assertRedirect(route('admin.approval-workflows.index'))
        ->assertSessionHas('success', 'Workflow template updated successfully.');

    $this->assertDatabaseHas('approval_workflow_templates', ['id' => $template->id, 'name' => 'Updated Name']);
});

it('replaces groups and steps on update', function () {
    [$user, $company] = actAsApprovalAdmin($this);
    $template = WorkflowTemplate::factory()->create(['company_id' => $company->id]);
    $oldGroup = WorkflowGroup::factory()->create(['template_id' => $template->id, 'name' => 'Old Group']);
    WorkflowStep::factory()->create(['group_id' => $oldGroup->id, 'label' => 'Old Step']);

    $this->get(route('admin.approval-workflows.edit', $template));

    $payload = validWorkflowPayload('Replaced', 2, 1);
    $this->put(route('admin.approval-workflows.update', $template), $payload)
        ->assertRedirect(route('admin.approval-workflows.index'));

    $this->assertDatabaseMissing('approval_workflow_groups', ['id' => $oldGroup->id]);
    $this->assertDatabaseHas('approval_workflow_groups', ['name' => 'Group 0']);
    $this->assertDatabaseHas('approval_workflow_groups', ['name' => 'Group 1']);
    $this->assertDatabaseHas('approval_workflow_steps', ['label' => 'Step 1-0']);
});

it('returns 403 for non-admin users on update', function () {
    actAsApprovalRegularUser($this);
    $company = Company::factory()->create(['is_active' => true]);
    $template = WorkflowTemplate::factory()->create(['company_id' => $company->id]);

    $this->get(route('admin.approval-workflows.create'));

    $this->put(route('admin.approval-workflows.update', $template), [
        '_token' => session()->token(),
        ...validWorkflowPayload(),
    ])->assertForbidden();
});

// ---------------------------------------------------------------------------
// Destroy (disable)
// ---------------------------------------------------------------------------

it('disables template via destroy', function () {
    [$user, $company] = actAsApprovalAdmin($this);
    $template = WorkflowTemplate::factory()->create(['company_id' => $company->id, 'is_active' => true]);

    $this->get(route('admin.approval-workflows.index'));

    $this->delete(route('admin.approval-workflows.destroy', $template), ['_token' => session()->token()])
        ->assertRedirect(route('admin.approval-workflows.index'))
        ->assertSessionHas('success', 'Workflow template disabled.');

    $this->assertDatabaseHas('approval_workflow_templates', ['id' => $template->id, 'is_active' => 0]);
});

it('returns 403 for non-admin users on destroy', function () {
    actAsApprovalRegularUser($this);
    $company = Company::factory()->create(['is_active' => true]);
    $template = WorkflowTemplate::factory()->create(['company_id' => $company->id]);

    $this->get(route('admin.approval-workflows.create'));

    $this->delete(route('admin.approval-workflows.destroy', $template), ['_token' => session()->token()])
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// Restore
// ---------------------------------------------------------------------------

it('restores soft-deleted template', function () {
    [$user, $company] = actAsApprovalAdmin($this);
    $template = WorkflowTemplate::factory()->create(['company_id' => $company->id, 'is_active' => false]);
    $template->delete();

    $this->get(route('admin.approval-workflows.index'));

    $this->post(route('admin.approval-workflows.restore', $template->id), ['_token' => session()->token()])
        ->assertRedirect(route('admin.approval-workflows.index'))
        ->assertSessionHas('success', 'Workflow template enabled.');

    $this->assertDatabaseHas('approval_workflow_templates', ['id' => $template->id, 'is_active' => 1, 'deleted_at' => null]);
});

// ---------------------------------------------------------------------------
// Trash (soft delete)
// ---------------------------------------------------------------------------

it('soft-deletes disabled template via trash', function () {
    [$user, $company] = actAsApprovalAdmin($this);
    $template = WorkflowTemplate::factory()->create(['company_id' => $company->id, 'is_active' => false]);

    $this->get(route('admin.approval-workflows.index'));

    $this->delete(route('admin.approval-workflows.trash', $template), ['_token' => session()->token()])
        ->assertRedirect(route('admin.approval-workflows.index'))
        ->assertSessionHas('success', 'Workflow template deleted.');

    $this->assertSoftDeleted('approval_workflow_templates', ['id' => $template->id]);
});

// ---------------------------------------------------------------------------
// Soft-delete (permanent force delete)
// ---------------------------------------------------------------------------

it('force-deletes template with all groups and steps', function () {
    [$user, $company] = actAsApprovalAdmin($this);
    $template = WorkflowTemplate::factory()->create(['company_id' => $company->id, 'is_active' => false]);
    $group = WorkflowGroup::factory()->create(['template_id' => $template->id]);
    $step = WorkflowStep::factory()->create(['group_id' => $group->id]);
    $template->delete();

    $this->get(route('admin.approval-workflows.index'));

    $this->delete(route('admin.approval-workflows.soft-delete', $template->id), ['_token' => session()->token()])
        ->assertRedirect(route('admin.approval-workflows.index', ['trashed' => 1]))
        ->assertSessionHas('success', 'Workflow template permanently deleted.');

    $this->assertDatabaseMissing('approval_workflow_templates', ['id' => $template->id]);
    $this->assertDatabaseMissing('approval_workflow_groups', ['id' => $group->id]);
    $this->assertDatabaseMissing('approval_workflow_steps', ['id' => $step->id]);
});
