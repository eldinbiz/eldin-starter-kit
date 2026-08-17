<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Yammi\AuditLog\Infrastructure\Facade\AuditLog;

uses(RefreshDatabase::class);

it('allows admin with view-audit-logs or platform access to access audit log gate', function (): void {
    $company = Company::factory()->create(['is_active' => true]);
    $adminRole = Role::factory()->admin()->create();

    $user = User::factory()->create();
    $user->companies()->attach($company->id, ['status' => 'active']);
    $user->companyRoles()->attach($adminRole->id, ['company_id' => $company->id]);

    $this->actingAs($user);
    session(['active_company_id' => $company->id]);

    expect(Gate::allows('viewAuditLog'))->toBeTrue();
});

it('denies regular users without audit permissions from audit log gate', function (): void {
    $company = Company::factory()->create(['is_active' => true]);
    $userRole = Role::factory()->user()->create();

    $user = User::factory()->create();
    $user->companies()->attach($company->id, ['status' => 'active']);
    $user->companyRoles()->attach($userRole->id, ['company_id' => $company->id]);

    $this->actingAs($user);
    session(['active_company_id' => $company->id]);

    expect(Gate::allows('viewAuditLog'))->toBeFalse();
});

it('fakes and records audit log entries when AuditLog package is loaded', function (): void {
    if (! class_exists(AuditLog::class)) {
        $this->markTestSkipped('Yammi AuditLog package not yet installed via composer.');
    }

    AuditLog::fake();

    $user = User::factory()->create(['name' => 'Original Name']);
    $user->update(['name' => 'Updated Name']);

    AuditLog::assertRecorded(User::class, $user->id, 'updated');
});

it('renders the embedded audit trail iframe page for authorized admins', function (): void {
    $company = Company::factory()->create(['is_active' => true]);
    $adminRole = Role::factory()->admin()->create();

    $user = User::factory()->create();
    $user->companies()->attach($company->id, ['status' => 'active']);
    $user->companyRoles()->attach($adminRole->id, ['company_id' => $company->id]);

    $this->actingAs($user);
    session(['active_company_id' => $company->id]);

    $response = $this->get(route('admin.audit-trail.index'));

    $response->assertStatus(200);
    $response->assertSee('System Audit Trail');
    $response->assertSee('<iframe', false);
    $response->assertSee('/audit-log');
});

it('records access audit log entry when user impersonation starts and stops', function (): void {
    if (! class_exists(AuditLog::class)) {
        $this->markTestSkipped('Yammi AuditLog package not yet installed via composer.');
    }

    AuditLog::fake();

    $company = Company::factory()->create(['is_active' => true]);
    $adminRole = Role::factory()->admin()->create();

    $admin = User::factory()->create();
    $admin->companies()->attach($company->id, ['status' => 'active']);
    $admin->companyRoles()->attach($adminRole->id, ['company_id' => $company->id]);

    $target = User::factory()->create();

    $admin->impersonate($target);
    AuditLog::assertRecorded(User::class, $target->id, 'accessed');

    $admin->leaveImpersonation();
    AuditLog::assertRecorded(User::class, $target->id, 'accessed');
});

it('denies audit trail access when user with sysadmin role switches active role to user', function (): void {
    $company = Company::factory()->create(['is_active' => true]);
    $sysadminRole = Role::factory()->sysadmin()->create();
    $userRole = Role::factory()->user()->create();

    $user = User::factory()->create();
    $user->companies()->attach($company->id, ['status' => 'active']);
    $user->companyRoles()->attach($sysadminRole->id, ['company_id' => $company->id]);
    $user->companyRoles()->attach($userRole->id, ['company_id' => $company->id]);

    // Active role: sysadmin -> Allowed
    session([
        'active_company_id' => $company->id,
        "active_role.{$company->id}" => 'sysadmin',
    ]);
    $this->actingAs($user)
        ->get(route('admin.audit-trail.index'))
        ->assertSuccessful();

    // Switch active role to user -> Forbidden
    session([
        'active_company_id' => $company->id,
        "active_role.{$company->id}" => 'user',
    ]);
    $this->actingAs($user)
        ->get(route('admin.audit-trail.index'))
        ->assertForbidden();
});
