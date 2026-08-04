<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helper Functions
// ---------------------------------------------------------------------------

function createOrgAdminUser(): array
{
    $company = Company::factory()->create(['is_active' => true]);
    $adminRole = Role::where('name', 'admin')->first() ?? Role::factory()->admin()->create();
    Role::where('name', 'user')->first() ?? Role::factory()->user()->create();

    $user = User::factory()->create();
    $user->companies()->attach($company->id, ['status' => 'active']);
    $user->companyRoles()->attach($adminRole->id, ['company_id' => $company->id]);

    return [$user, $company];
}

function actAsOrgAdmin(TestCase $test): array
{
    [$user, $company] = createOrgAdminUser();

    $test->actingAs($user);
    session(['active_company_id' => $company->id]);

    return [$user, $company];
}

function actAsOrgRegularUser(TestCase $test): User
{
    $company = Company::factory()->create(['is_active' => true]);
    $userRole = Role::where('name', 'user')->first() ?? Role::factory()->user()->create();

    $user = User::factory()->create();
    $user->companies()->attach($company->id, ['status' => 'active']);
    $user->companyRoles()->attach($userRole->id, ['company_id' => $company->id]);

    $test->actingAs($user);
    session(['active_company_id' => $company->id]);

    return $user;
}

// ---------------------------------------------------------------------------
// Authentication & Authorization
// ---------------------------------------------------------------------------

it('redirects guests from org chart routes', function (): void {
    $this->get(route('admin.org-chart.index'))->assertRedirect(route('login'));
    $this->get(route('admin.org-chart.tree'))->assertRedirect(route('login'));
    $this->patch(route('admin.org-chart.reassign'), ['user_id' => 1])->assertRedirect(route('login'));
    $this->get(route('admin.org-chart.trace', 1))->assertRedirect(route('login'));
});

it('blocks non-admin users from org chart routes', function (): void {
    actAsOrgRegularUser($this);

    $this->get(route('admin.org-chart.index'))->assertForbidden();
    $this->get(route('admin.org-chart.tree'))->assertForbidden();
    $this->patch(route('admin.org-chart.reassign'), ['user_id' => 1])->assertForbidden();
    $this->get(route('admin.org-chart.trace', 1))->assertForbidden();
});

// ---------------------------------------------------------------------------
// Org Chart Page View
// ---------------------------------------------------------------------------

it('allows admin to view the org chart page', function (): void {
    actAsOrgAdmin($this);

    $this->get(route('admin.org-chart.index'))
        ->assertSuccessful()
        ->assertSee('Organization Chart');
});

// ---------------------------------------------------------------------------
// Tree API Endpoint
// ---------------------------------------------------------------------------

it('blocks user without attached companies with 403 on tree endpoint', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('admin.org-chart.tree'))->assertForbidden();
});

it('returns full org chart tree as JSON for active company', function (): void {
    [$admin, $company] = actAsOrgAdmin($this);

    $manager = User::factory()->create(['name' => 'Alice Manager']);
    $employee = User::factory()->create(['name' => 'Bob Engineer']);

    $company->users()->attach($manager->id, ['status' => 'active', 'job_title' => 'Manager']);
    $company->users()->attach($employee->id, [
        'status' => 'active',
        'job_title' => 'Engineer',
        'manager_id' => $manager->id,
    ]);

    $response = $this->get(route('admin.org-chart.tree'))
        ->assertSuccessful()
        ->assertJsonStructure([
            'company' => ['id', 'name'],
            'users' => [
                '*' => ['id', 'name', 'email', 'initials', 'job_title', 'status', 'manager_id'],
            ],
        ]);

    $users = $response->json('users');
    $userNames = array_column($users, 'name');
    expect($userNames)->toContain('Alice Manager', 'Bob Engineer');
});

it('cleans up dangling manager references pointing outside the company', function (): void {
    [$admin, $company] = actAsOrgAdmin($this);

    $externalUser = User::factory()->create(['name' => 'External Manager']);
    $employee = User::factory()->create(['name' => 'Orphan Employee']);

    // Attach employee to company with a manager_id pointing to a user NOT in this company
    $company->users()->attach($employee->id, [
        'status' => 'active',
        'job_title' => 'Developer',
        'manager_id' => $externalUser->id,
    ]);

    $response = $this->get(route('admin.org-chart.tree'))->assertSuccessful();

    $users = $response->json('users');
    $orphanNode = current(array_filter($users, fn ($u) => $u['id'] === $employee->id));

    expect($orphanNode['manager_id'])->toBeNull();
});

// ---------------------------------------------------------------------------
// Manager Reassignment API
// ---------------------------------------------------------------------------

it('blocks user without attached companies with 403 on reassign endpoint', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->patch(route('admin.org-chart.reassign'), ['user_id' => 1])->assertForbidden();
});

it('allows admin to reassign an employee to a new manager', function (): void {
    [$admin, $company] = actAsOrgAdmin($this);

    $manager = User::factory()->create();
    $employee = User::factory()->create();

    $company->users()->attach($manager->id, ['status' => 'active', 'job_title' => 'Manager']);
    $company->users()->attach($employee->id, ['status' => 'active', 'job_title' => 'Developer']);

    $this->patch(route('admin.org-chart.reassign'), [
        'user_id' => $employee->id,
        'new_manager_id' => $manager->id,
        'job_title' => 'Senior Developer',
        'status' => 'active',
    ])->assertSuccessful()
      ->assertJson(['success' => true]);

    $this->assertDatabaseHas('company_user', [
        'company_id' => $company->id,
        'user_id' => $employee->id,
        'manager_id' => $manager->id,
        'job_title' => 'Senior Developer',
    ]);
});

it('prevents setting an employee as their own manager', function (): void {
    [$admin, $company] = actAsOrgAdmin($this);

    $user = User::factory()->create();
    $company->users()->attach($user->id, ['status' => 'active']);

    $this->patch(route('admin.org-chart.reassign'), [
        'user_id' => $user->id,
        'new_manager_id' => $user->id,
    ])->assertStatus(422)
      ->assertJson(['error' => 'A user cannot be their own manager.']);
});

it('auto-promotes manager to root when assigned under their own subordinate to prevent cycles', function (): void {
    [$admin, $company] = actAsOrgAdmin($this);

    $leader = User::factory()->create();
    $subordinate = User::factory()->create();

    // Leader is manager of subordinate
    $company->users()->attach($leader->id, ['status' => 'active', 'manager_id' => null]);
    $company->users()->attach($subordinate->id, ['status' => 'active', 'manager_id' => $leader->id]);

    // Reassign Leader under Subordinate (attempting a cycle)
    $this->patch(route('admin.org-chart.reassign'), [
        'user_id' => $leader->id,
        'new_manager_id' => $subordinate->id,
    ])->assertSuccessful()
      ->assertJson(['success' => true]);

    // Subordinate should have been auto-promoted to root (manager_id = null)
    $this->assertDatabaseHas('company_user', [
        'company_id' => $company->id,
        'user_id' => $subordinate->id,
        'manager_id' => null,
    ]);

    // Leader now reports to Subordinate
    $this->assertDatabaseHas('company_user', [
        'company_id' => $company->id,
        'user_id' => $leader->id,
        'manager_id' => $subordinate->id,
    ]);
});

// ---------------------------------------------------------------------------
// Trace Reporting Line API
// ---------------------------------------------------------------------------

it('blocks user without attached companies with 403 on trace endpoint', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('admin.org-chart.trace', 1))->assertForbidden();
});

it('traces reporting line from employee up to top manager', function (): void {
    [$admin, $company] = actAsOrgAdmin($this);

    $ceo = User::factory()->create(['name' => 'CEO User']);
    $vp = User::factory()->create(['name' => 'VP User']);
    $staff = User::factory()->create(['name' => 'Staff User']);

    $company->users()->attach($ceo->id, ['status' => 'active', 'job_title' => 'CEO', 'manager_id' => null]);
    $company->users()->attach($vp->id, ['status' => 'active', 'job_title' => 'VP', 'manager_id' => $ceo->id]);
    $company->users()->attach($staff->id, ['status' => 'active', 'job_title' => 'Engineer', 'manager_id' => $vp->id]);

    $response = $this->get(route('admin.org-chart.trace', $staff->id))
        ->assertSuccessful()
        ->assertJsonStructure([
            'chain' => [
                '*' => ['id', 'name', 'initials', 'job_title'],
            ],
        ]);

    $chain = $response->json('chain');
    $chainIds = array_column($chain, 'id');

    // Chain should trace: Staff -> VP -> CEO
    expect($chainIds)->toBe([$staff->id, $vp->id, $ceo->id]);
});
