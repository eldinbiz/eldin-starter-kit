<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\GroupType;
use App\Enums\ResolverType;
use App\Enums\TemplateScope;
use App\Models\Approval\WorkflowGroup;
use App\Models\Approval\WorkflowStep;
use App\Models\Approval\WorkflowTemplate;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

function createTestApprovalAdmin(): array
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

function actAsTestApprovalAdmin(TestCase $test): array
{
    [$user, $company] = createTestApprovalAdmin();

    $test->actingAs($user);
    session(['active_company_id' => $company->id]);

    return [$user, $company];
}

it('shows create view in canvas mode by default when no mode parameter is provided', function () {
    [$user, $company] = actAsTestApprovalAdmin($this);

    $response = $this->get(route('admin.approval-workflows.create'));

    $response->assertSuccessful();
    $response->assertViewIs('admin.approval-workflows.canvas');
    $response->assertSee('Canvas Mode');
    $response->assertSee('palette-blocks');
    $response->assertSee('canvas-viewport');
    $response->assertSee('Direct Manager');
    $response->assertSee('Stage Group');
    $response->assertViewHas('initialData');
    $response->assertViewHas('users');
    $response->assertViewHas('company');
});

it('shows edit view in canvas mode by default pre-populating existing template groups and steps', function () {
    [$user, $company] = actAsTestApprovalAdmin($this);

    $template = WorkflowTemplate::factory()->create([
        'company_id' => $company->id,
        'name' => 'Multi-Tier PO Approval',
        'description' => 'Requires management and finance approvals',
        'scope' => TemplateScope::ThisCompany,
        'is_active' => true,
    ]);

    $group1 = WorkflowGroup::factory()->create([
        'template_id' => $template->id,
        'name' => 'Managerial Review',
        'order' => 0,
        'type' => GroupType::Sequential,
    ]);

    WorkflowStep::factory()->create([
        'group_id' => $group1->id,
        'label' => 'Direct Line Manager',
        'order' => 0,
        'resolver_type' => ResolverType::NPlus1,
        'resolver_payload' => null,
    ]);

    $group2 = WorkflowGroup::factory()->create([
        'template_id' => $template->id,
        'name' => 'Finance Committee',
        'order' => 1,
        'type' => GroupType::Parallel,
        'min_approvals' => 1,
    ]);

    $financeUser = User::factory()->create();
    $company->users()->attach($financeUser->id, ['status' => 'active']);

    WorkflowStep::factory()->create([
        'group_id' => $group2->id,
        'label' => 'CFO Approval',
        'order' => 0,
        'resolver_type' => ResolverType::SpecificUser,
        'resolver_payload' => ['user_id' => $financeUser->id],
    ]);

    $response = $this->get(route('admin.approval-workflows.edit', $template));

    $response->assertSuccessful();
    $response->assertViewIs('admin.approval-workflows.canvas');
    $response->assertSee('Multi-Tier PO Approval');
    $response->assertSee('Managerial Review');
    $response->assertSee('Finance Committee');

    $initialData = $response->viewData('initialData');
    expect($initialData['name'])->toBe('Multi-Tier PO Approval')
        ->and($initialData['groups'])->toHaveCount(2)
        ->and($initialData['groups'][0]['name'])->toBe('Managerial Review')
        ->and($initialData['groups'][0]['steps'][0]['resolver_type'])->toBe('N+1')
        ->and($initialData['groups'][1]['name'])->toBe('Finance Committee')
        ->and($initialData['groups'][1]['type'])->toBe('parallel')
        ->and($initialData['groups'][1]['min_approvals'])->toBe(1)
        ->and($initialData['groups'][1]['steps'][0]['resolver_payload']['user_id'])->toBe($financeUser->id);
});

it('renders standard form view with link to canvas mode when mode=form query parameter is provided', function () {
    actAsTestApprovalAdmin($this);

    $response = $this->get(route('admin.approval-workflows.create', ['mode' => 'form']));

    $response->assertSuccessful();
    $response->assertViewIs('admin.approval-workflows.create');
    $response->assertSee(route('admin.approval-workflows.create', ['mode' => 'canvas']));
    $response->assertSee('Canvas Mode');
});

it('renders mode switch button in canvas view linking back to form view', function () {
    actAsTestApprovalAdmin($this);

    $response = $this->get(route('admin.approval-workflows.create'));

    $response->assertSuccessful();
    $response->assertSee(route('admin.approval-workflows.create', ['mode' => 'form']));
    $response->assertSee('Form View');
});

it('returns 403 for unauthorized users in canvas mode', function () {
    $company = Company::factory()->create(['is_active' => true]);
    Role::factory()->user()->create();

    $regularUser = User::factory()->create();
    $regularUser->companies()->attach($company->id, ['status' => 'active']);
    $regularUser->companyRoles()->attach(
        Role::where('name', 'user')->value('id'),
        ['company_id' => $company->id]
    );

    $this->actingAs($regularUser);
    session(['active_company_id' => $company->id]);

    $this->get(route('admin.approval-workflows.create', ['mode' => 'canvas']))->assertForbidden();
});

it('can successfully store a workflow template generated from canvas form submission', function () {
    [$user, $company] = actAsTestApprovalAdmin($this);

    $approverUser = User::factory()->create();
    $company->users()->attach($approverUser->id, ['status' => 'active']);

    $payload = [
        'name' => 'Canvas Created Template',
        'description' => 'Built with the WYSIWYG canvas builder',
        'approvable_type' => '',
        'scope' => 'this_company',
        'is_active' => '1',
        'groups' => [
            [
                'name' => 'Stage 1: Management',
                'type' => 'sequential',
                'steps' => [
                    [
                        'label' => 'Direct Line Manager Approval',
                        'resolver_type' => 'N+1',
                    ],
                ],
            ],
            [
                'name' => 'Stage 2: Specific Approver',
                'type' => 'parallel',
                'min_approvals' => 1,
                'steps' => [
                    [
                        'label' => 'Designated Officer Sign-off',
                        'resolver_type' => 'specific_user',
                        'resolver_payload' => [
                            'user_id' => $approverUser->id,
                        ],
                    ],
                ],
            ],
        ],
    ];

    $response = $this->post(route('admin.approval-workflows.store'), $payload);

    $response->assertRedirect(route('admin.approval-workflows.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('approval_workflow_templates', [
        'company_id' => $company->id,
        'name' => 'Canvas Created Template',
    ]);

    $created = WorkflowTemplate::with('groups.steps')->where('name', 'Canvas Created Template')->first();
    expect($created)->not->toBeNull()
        ->and($created->groups)->toHaveCount(2)
        ->and($created->groups[0]->steps)->toHaveCount(1)
        ->and($created->groups[1]->steps)->toHaveCount(1)
        ->and($created->groups[1]->steps[0]->resolver_payload['user_id'])->toBe($approverUser->id);
});

it('renders user directory palette with isUserInWorkflow binding and toast notification element', function () {
    [$user, $company] = actAsTestApprovalAdmin($this);

    $colleague = User::factory()->create(['name' => 'Alice Auditor']);
    $company->users()->attach($colleague->id, ['status' => 'active']);

    $response = $this->get(route('admin.approval-workflows.create'));

    $response->assertSuccessful();
    $response->assertSee('palette-users');
    $response->assertSee('palette-user-item');
    $response->assertSee('isUserInWorkflow');
    $response->assertSee('toast.show');
    $response->assertSee('Added');
    $response->assertSee('step._uid');
});

