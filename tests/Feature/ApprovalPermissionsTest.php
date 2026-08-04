<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->company = Company::factory()->create();

    $this->userRole = Role::factory()->create([
        'name' => 'user',
        'permissions' => [
            'view-approval-inbox',
            'view-approval-history',
            'view-approval-requests',
        ],
    ]);

    $this->adminRole = Role::factory()->create([
        'name' => 'admin',
        'permissions' => [
            'view-approval-inbox',
            'view-approval-history',
            'view-approval-requests',
            'manage-approvals',
            'manage-approval-workflows',
        ],
    ]);

    $this->restrictedRole = Role::factory()->create([
        'name' => 'restricted-user',
        'permissions' => [
            'view-dashboard',
        ],
    ]);
});

function attachUserToCompanyWithRole(User $user, Company $company, Role $role): void
{
    $user->companies()->attach($company->id, ['status' => 'active']);
    $user->companyRoles()->attach($role->id, ['company_id' => $company->id]);
}

it('allows user with view-approval-inbox permission to access inbox route', function (): void {
    $user = User::factory()->create();
    attachUserToCompanyWithRole($user, $this->company, $this->userRole);

    $this->actingAs($user)
        ->get(route('approvals.inbox'))
        ->assertStatus(200);
});

it('denies user without view-approval-inbox permission from accessing inbox route', function (): void {
    $user = User::factory()->create();
    attachUserToCompanyWithRole($user, $this->company, $this->restrictedRole);

    $this->actingAs($user)
        ->get(route('approvals.inbox'))
        ->assertStatus(403);
});

it('allows user with view-approval-history permission to access history route', function (): void {
    $user = User::factory()->create();
    attachUserToCompanyWithRole($user, $this->company, $this->userRole);

    $this->actingAs($user)
        ->get(route('approvals.history'))
        ->assertStatus(200);
});

it('denies user without view-approval-history permission from accessing history route', function (): void {
    $user = User::factory()->create();
    attachUserToCompanyWithRole($user, $this->company, $this->restrictedRole);

    $this->actingAs($user)
        ->get(route('approvals.history'))
        ->assertStatus(403);
});

it('allows user with view-approval-requests permission to access requests route', function (): void {
    $user = User::factory()->create();
    attachUserToCompanyWithRole($user, $this->company, $this->userRole);

    $this->actingAs($user)
        ->get(route('approvals.requests'))
        ->assertStatus(200);
});

it('denies user without view-approval-requests permission from accessing requests route', function (): void {
    $user = User::factory()->create();
    attachUserToCompanyWithRole($user, $this->company, $this->restrictedRole);

    $this->actingAs($user)
        ->get(route('approvals.requests'))
        ->assertStatus(403);
});

it('allows user with manage-approvals permission to access approval management route', function (): void {
    $user = User::factory()->create();
    attachUserToCompanyWithRole($user, $this->company, $this->adminRole);

    $this->actingAs($user)
        ->get(route('admin.approval-management.index'))
        ->assertStatus(200);
});

it('denies user without manage-approvals permission from accessing approval management route', function (): void {
    $user = User::factory()->create();
    attachUserToCompanyWithRole($user, $this->company, $this->userRole);

    $this->actingAs($user)
        ->get(route('admin.approval-management.index'))
        ->assertStatus(403);
});

it('allows user with manage-approval-workflows permission to access approval workflows route', function (): void {
    $user = User::factory()->create();
    attachUserToCompanyWithRole($user, $this->company, $this->adminRole);

    $this->actingAs($user)
        ->get(route('admin.approval-workflows.index'))
        ->assertStatus(200);
});

it('denies user without manage-approval-workflows permission from accessing approval workflows route', function (): void {
    $user = User::factory()->create();
    attachUserToCompanyWithRole($user, $this->company, $this->userRole);

    $this->actingAs($user)
        ->get(route('admin.approval-workflows.index'))
        ->assertStatus(403);
});

it('renders descriptive approval permission labels in role creation page', function (): void {
    $user = User::factory()->create();
    attachUserToCompanyWithRole($user, $this->company, $this->adminRole);

    $response = $this->actingAs($user)->get(route('admin.roles.create'));

    $response->assertStatus(200);
    $response->assertSee('Approval Management');
    $response->assertSee('Inbox: Menu access for all approval requests assigned to you');
    $response->assertSee('History: Menu access to view past approval decisions and actions');
    $response->assertSee('My Requests: Menu access to track approval requests you submitted');
    $response->assertSee('Manage Approvals: Administrative access to monitor, reassign, skip, or override any approval request');
    $response->assertSee('Workflow Templates: Administrative access to create, edit, and configure approval workflow templates');
});
