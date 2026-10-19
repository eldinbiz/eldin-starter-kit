<?php

declare(strict_types=1);

namespace Tests\Browser;

use App\Models\Approval\WorkflowGroup;
use App\Models\Approval\WorkflowStep;
use App\Models\Approval\WorkflowTemplate;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('canvas mode loads by default and admin can toggle between canvas and form mode', function (): void {
    $company = Company::factory()->create([
        'name' => 'Acme Corporation',
        'is_active' => true,
    ]);

    $adminRole = Role::factory()->admin()->create();
    $admin = User::factory()->create([
        'name' => 'Alice Admin',
        'email' => 'alice.canvas@example.com',
        'password' => 'password',
    ]);
    $admin->companies()->attach($company->id, ['status' => 'active']);
    $admin->companyRoles()->attach($adminRole->id, ['company_id' => $company->id]);

    loginApprovalAdminUser($admin)
        ->navigate('/admin/approval-workflows/create')
        ->assertSee('Components & Approvers')
        ->assertSee('Workflow Blocks')
        ->assertSee('User Directory')
        ->assertSee('START: Requester Submission')
        ->assertSee('FINISH: Approved & Executed')
        ->assertSee('Form View')
        // Switch to Form View
        ->click('a[title="Switch to Form View"]')
        ->wait(1)
        ->assertPathIs('/admin/approval-workflows/create')
        ->assertQueryStringHas('mode', 'form')
        ->assertSee('New Workflow Template')
        ->assertSee('Canvas Mode')
        // Switch back to Canvas Mode
        ->click('a[title="Switch to Graphical WYSIWYG Canvas Mode"]')
        ->wait(1)
        ->assertPathIs('/admin/approval-workflows/create')
        ->assertSee('Components & Approvers');
})->group('browser');

test('canvas mode displays pipeline stages, inspector panel, and floating controls by default on edit', function (): void {
    $company = Company::factory()->create([
        'name' => 'Acme Corporation',
        'is_active' => true,
    ]);

    $adminRole = Role::factory()->admin()->create();
    $admin = User::factory()->create([
        'name' => 'Alice Admin',
        'email' => 'alice.canvas2@example.com',
        'password' => 'password',
    ]);
    $admin->companies()->attach($company->id, ['status' => 'active']);
    $admin->companyRoles()->attach($adminRole->id, ['company_id' => $company->id]);

    $template = WorkflowTemplate::factory()->create([
        'company_id' => $company->id,
        'name' => 'Interactive Pipeline PO',
        'is_active' => true,
    ]);

    $group = WorkflowGroup::factory()->create([
        'template_id' => $template->id,
        'name' => 'Executive Sign-off',
        'order' => 0,
        'type' => 'sequential',
    ]);

    WorkflowStep::factory()->create([
        'group_id' => $group->id,
        'label' => 'Direct Line Manager Review',
        'resolver_type' => 'N+1',
        'order' => 0,
    ]);

    loginApprovalAdminUser($admin)
        ->navigate("/admin/approval-workflows/{$template->id}/edit")
        ->wait(1)
        ->assertSee('Executive Sign-off')
        ->assertSee('Direct Line Manager Review')
        ->assertSee('Properties')
        ->assertSee('100%');
})->group('browser');
