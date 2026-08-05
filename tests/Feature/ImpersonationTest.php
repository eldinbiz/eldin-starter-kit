<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user without user-impersonation permission cannot impersonate another user', function () {
    $company = Company::factory()->create();

    $role = Role::create([
        'name' => 'regular-manager',
        'permissions' => ['manage-users'],
        'is_active' => true,
    ]);

    $admin = User::factory()->create();
    $targetUser = User::factory()->create();

    $admin->companies()->attach($company->id);
    $admin->companyRoles()->attach($role->id, ['company_id' => $company->id]);
    $targetUser->companies()->attach($company->id);

    $this->actingAs($admin)
        ->withSession(['active_company_id' => $company->id])
        ->get(route('impersonate', $targetUser->id))
        ->assertForbidden();

    expect(auth()->id())->toBe($admin->id);
});

test('user with explicit user-impersonation permission can impersonate another user', function () {
    $company = Company::factory()->create();

    $impersonatorRole = Role::create([
        'name' => 'super-admin',
        'permissions' => ['user-impersonation'],
        'is_active' => true,
    ]);

    $admin = User::factory()->create();
    $targetUser = User::factory()->create();

    $admin->companies()->attach($company->id);
    $admin->companyRoles()->attach($impersonatorRole->id, ['company_id' => $company->id]);
    $targetUser->companies()->attach($company->id);

    $this->actingAs($admin)
        ->withSession(['active_company_id' => $company->id])
        ->get(route('impersonate', $targetUser->id))
        ->assertRedirect();

    expect(auth()->id())->toBe($targetUser->id);
    expect(app('impersonate')->isImpersonating())->toBeTrue();
});

test('impersonating user can leave impersonation session', function () {
    $company = Company::factory()->create();

    $impersonatorRole = Role::create([
        'name' => 'super-admin',
        'permissions' => ['user-impersonation'],
        'is_active' => true,
    ]);

    $admin = User::factory()->create();
    $targetUser = User::factory()->create();

    $admin->companies()->attach($company->id);
    $admin->companyRoles()->attach($impersonatorRole->id, ['company_id' => $company->id]);
    $targetUser->companies()->attach($company->id);

    // Start impersonating
    $this->actingAs($admin)
        ->withSession(['active_company_id' => $company->id])
        ->get(route('impersonate', $targetUser->id));

    expect(auth()->id())->toBe($targetUser->id);

    // Leave impersonation
    $this->get(route('impersonate.leave'))
        ->assertRedirect();

    expect(auth()->id())->toBe($admin->id);
});

test('impersonated user retains their active role permissions and can access gated routes', function () {
    $company = Company::factory()->create();

    $adminRole = Role::create([
        'name' => 'admin',
        'permissions' => ['user-impersonation'],
        'is_active' => true,
    ]);

    $approverRole = Role::create([
        'name' => 'approver',
        'permissions' => ['view-approval-inbox', 'view-approval-history', 'view-approval-requests'],
        'is_active' => true,
    ]);

    $admin = User::factory()->create();
    $targetUser = User::factory()->create();

    $admin->companies()->attach($company->id);
    $admin->companyRoles()->attach($adminRole->id, ['company_id' => $company->id]);

    $targetUser->companies()->attach($company->id);
    $targetUser->companyRoles()->attach($approverRole->id, ['company_id' => $company->id]);

    // Admin starts impersonating target user
    $this->actingAs($admin)
        ->withSession(['active_company_id' => $company->id, 'active_role.'.$company->id => 'admin'])
        ->get(route('impersonate', $targetUser->id))
        ->assertRedirect();

    expect(auth()->id())->toBe($targetUser->id);

    // Access gated inbox route while impersonating
    $this->get(route('approvals.inbox'))
        ->assertStatus(200);
});
