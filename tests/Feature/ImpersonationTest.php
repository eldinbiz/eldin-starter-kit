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

test('session status route accurately reports authenticated state when session has impersonation payload', function () {
    $sessionId = 'test_impersonation_session_id_12345';
    $cookieName = config('session.cookie');

    // Create session record in database where user_id might be null but payload has impersonation data
    $payloadData = [
        '_token' => 'dummy_token',
        'impersonated_by' => 1,
        'login_web_59ba36addc2b2f9401580f014c7f58ea4e30989d' => 2,
    ];

    \Illuminate\Support\Facades\DB::table(config('session.table'))->insert([
        'id' => $sessionId,
        'user_id' => null, // simulates database session where user_id column is null
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Pest/Test',
        'payload' => base64_encode(json_encode($payloadData)),
        'last_activity' => now()->timestamp,
    ]);

    $this->withCredentials()
        ->withCookie($cookieName, $sessionId)
        ->getJson(route('session.status'))
        ->assertOk()
        ->assertJson(['authenticated' => true]);
});

test('session status route returns unauthenticated when session is missing or expired', function () {
    $cookieName = config('session.cookie');

    // Non-existent session
    $this->withCredentials()
        ->withCookie($cookieName, 'non_existent_session')
        ->getJson(route('session.status'))
        ->assertOk()
        ->assertJson(['authenticated' => false]);

    // Expired session
    $expiredSessionId = 'expired_session_99999';
    \Illuminate\Support\Facades\DB::table(config('session.table'))->insert([
        'id' => $expiredSessionId,
        'user_id' => 1,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Pest/Test',
        'payload' => base64_encode(json_encode(['_token' => 'dummy'])),
        'last_activity' => now()->subHours(5)->timestamp,
    ]);

    $this->withCredentials()
        ->withCookie($cookieName, $expiredSessionId)
        ->getJson(route('session.status'))
        ->assertOk()
        ->assertJson(['authenticated' => false]);
});

test('impersonated user can navigate across multiple menu routes without session loss', function () {
    $company = Company::factory()->create();

    $adminRole = Role::create([
        'name' => 'admin',
        'permissions' => ['user-impersonation', 'manage-users'],
        'is_active' => true,
    ]);

    $staffRole = Role::create([
        'name' => 'staff',
        'permissions' => ['view-approval-requests'],
        'is_active' => true,
    ]);

    $admin = User::factory()->create();
    $targetUser = User::factory()->create();

    $admin->companies()->attach($company->id);
    $admin->companyRoles()->attach($adminRole->id, ['company_id' => $company->id]);

    $targetUser->companies()->attach($company->id);
    $targetUser->companyRoles()->attach($staffRole->id, ['company_id' => $company->id]);

    // 1. Admin initiates impersonation
    $this->actingAs($admin)
        ->withSession(['active_company_id' => $company->id, 'active_role.'.$company->id => 'admin'])
        ->get(route('impersonate', $targetUser->id))
        ->assertRedirect('/');

    expect(auth()->id())->toBe($targetUser->id);

    // 2. Navigate to Dashboard
    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('You are currently impersonating');

    // 3. Navigate to Purchase Requests menu
    $this->get(route('purchase-requests.index'))
        ->assertOk();

    // 4. Navigate to Approvals Requests menu
    $this->get(route('approvals.requests'))
        ->assertOk();

    // 5. Leave impersonation
    $this->get(route('impersonate.leave'))
        ->assertRedirect();

    expect(auth()->id())->toBe($admin->id);

    // 6. Admin can access admin routes again
    $this->get(route('admin.users.index'))
        ->assertOk();
});

