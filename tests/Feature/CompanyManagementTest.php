<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helper Functions
// ---------------------------------------------------------------------------

function createCompanyAdminUser(): array
{
    $company = Company::factory()->create(['is_active' => true]);
    $adminRole = Role::factory()->admin()->create();
    Role::factory()->user()->create();

    $user = User::factory()->create();
    $user->companies()->attach($company->id, ['status' => 'active']);
    $user->companyRoles()->attach($adminRole->id, ['company_id' => $company->id]);

    return [$user, $company];
}

function actAsCompanyAdmin(TestCase $test): array
{
    [$user, $company] = createCompanyAdminUser();

    $test->actingAs($user);
    session(['active_company_id' => $company->id]);

    return [$user, $company];
}

function actAsCompanyRegularUser(TestCase $test): User
{
    $company = Company::factory()->create(['is_active' => true]);
    $userRole = Role::factory()->user()->create();

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

it('redirects guests from company management routes', function (): void {
    $company = Company::factory()->create();

    $this->get(route('admin.companies.index'))->assertRedirect(route('login'));
    $this->get(route('admin.companies.tree'))->assertRedirect(route('login'));
    $this->patch(route('admin.companies.reparent'), ['company_id' => $company->id])->assertRedirect(route('login'));
    $this->get(route('admin.companies.create'))->assertRedirect(route('login'));
    $this->post(route('admin.companies.store'), ['name' => 'New Co'])->assertRedirect(route('login'));
    $this->get(route('admin.companies.edit', $company))->assertRedirect(route('login'));
    $this->put(route('admin.companies.update', $company), ['name' => 'Updated Co'])->assertRedirect(route('login'));
    $this->patch(route('admin.companies.toggle', $company))->assertRedirect(route('login'));
    $this->post(route('admin.companies.assign-user', $company), ['user_id' => 1])->assertRedirect(route('login'));
    $this->delete(route('admin.companies.remove-user', [$company, 1]))->assertRedirect(route('login'));
});

it('blocks non-admin users from company management routes', function (): void {
    actAsCompanyRegularUser($this);
    $company = Company::factory()->create();

    $this->get(route('admin.companies.index'))->assertForbidden();
    $this->get(route('admin.companies.tree'))->assertForbidden();
    $this->patch(route('admin.companies.reparent'), ['company_id' => $company->id])->assertForbidden();
    $this->get(route('admin.companies.create'))->assertForbidden();
    $this->post(route('admin.companies.store'), ['name' => 'New Co'])->assertForbidden();
    $this->get(route('admin.companies.edit', $company))->assertForbidden();
    $this->put(route('admin.companies.update', $company), ['name' => 'Updated Co'])->assertForbidden();
    $this->patch(route('admin.companies.toggle', $company))->assertForbidden();
    $this->post(route('admin.companies.assign-user', $company), ['user_id' => 1])->assertForbidden();
    $this->delete(route('admin.companies.remove-user', [$company, 1]))->assertForbidden();
});

it('allows company-admin to view company hierarchy tree but blocks mutation operations', function (): void {
    $company = Company::factory()->create(['is_active' => true]);
    $companyAdminRole = Role::factory()->companyAdmin()->create();

    $user = User::factory()->create();
    $user->companies()->attach($company->id, ['status' => 'active']);
    $user->companyRoles()->attach($companyAdminRole->id, ['company_id' => $company->id]);

    $this->actingAs($user);
    session(['active_company_id' => $company->id]);

    // Allowed: view-companies
    $this->get(route('admin.companies.index'))->assertSuccessful();
    $this->get(route('admin.companies.tree'))->assertSuccessful();

    // Blocked: manage-companies mutations
    $this->patch(route('admin.companies.reparent'), ['company_id' => $company->id])->assertForbidden();
    $this->get(route('admin.companies.create'))->assertForbidden();
    $this->post(route('admin.companies.store'), ['name' => 'New Co'])->assertForbidden();
    $this->get(route('admin.companies.edit', $company))->assertForbidden();
    $this->put(route('admin.companies.update', $company), ['name' => 'Updated Co'])->assertForbidden();
    $this->patch(route('admin.companies.toggle', $company))->assertForbidden();
    $this->post(route('admin.companies.assign-user', $company), ['user_id' => 1])->assertForbidden();
    $this->delete(route('admin.companies.remove-user', [$company, 1]))->assertForbidden();
});

// ---------------------------------------------------------------------------
// Hierarchy & Tree
// ---------------------------------------------------------------------------

it('allows admin to view the company hierarchy tree page', function (): void {
    actAsCompanyAdmin($this);

    Company::factory()->create(['name' => 'Acme Holding']);

    $this->get(route('admin.companies.index'))
        ->assertSuccessful()
        ->assertSee('Company Hierarchy');
});

it('returns flattened JSON company tree for Alpine tree component', function (): void {
    actAsCompanyAdmin($this);

    $parent = Company::factory()->create(['name' => 'Parent Corp']);
    $child = Company::factory()->create(['name' => 'Subsidiary Inc', 'parent_id' => $parent->id]);

    $response = $this->get(route('admin.companies.tree'))
        ->assertSuccessful()
        ->assertJsonStructure([
            'companies' => [
                '*' => ['id', 'name', 'slug', 'type', 'type_label', 'parent_id', 'is_active', 'users_count', 'logo_url'],
            ],
        ]);

    $json = $response->json('companies');
    $names = array_column($json, 'name');
    expect($names)->toContain('Parent Corp', 'Subsidiary Inc');
});

it('allows admin to reparent a company via API', function (): void {
    actAsCompanyAdmin($this);

    $parent = Company::factory()->create();
    $company = Company::factory()->create(['parent_id' => null]);

    $this->patch(route('admin.companies.reparent'), [
        'company_id' => $company->id,
        'new_parent_id' => $parent->id,
    ])->assertSuccessful()
        ->assertJson(['success' => true]);

    $company->refresh();
    expect($company->parent_id)->toBe($parent->id);
});

it('prevents self-referencing when reparenting a company', function (): void {
    actAsCompanyAdmin($this);

    $company = Company::factory()->create();

    $this->patch(route('admin.companies.reparent'), [
        'company_id' => $company->id,
        'new_parent_id' => $company->id,
    ])->assertStatus(422)
        ->assertJson(['error' => 'A company cannot be its own parent.']);
});

it('prevents cycle creation when reparenting a parent under its subsidiary', function (): void {
    actAsCompanyAdmin($this);

    $parent = Company::factory()->create(['name' => 'Grandparent']);
    $child = Company::factory()->create(['name' => 'Parent', 'parent_id' => $parent->id]);
    $grandchild = Company::factory()->create(['name' => 'Child', 'parent_id' => $child->id]);

    // Try to move Grandparent under Child
    $this->patch(route('admin.companies.reparent'), [
        'company_id' => $parent->id,
        'new_parent_id' => $grandchild->id,
    ])->assertStatus(422)
        ->assertJson(['error' => 'Cannot move a company under one of its own subsidiaries.']);
});

// ---------------------------------------------------------------------------
// Toggle Active Status
// ---------------------------------------------------------------------------

it('allows admin to toggle company active status', function (): void {
    actAsCompanyAdmin($this);

    $company = Company::factory()->create(['is_active' => true]);

    $this->patch(route('admin.companies.toggle', $company))
        ->assertSuccessful()
        ->assertJson(['success' => true, 'is_active' => false]);

    $company->refresh();
    expect($company->is_active)->toBeFalse();
});

it('prevents disabling a company if child companies are active', function (): void {
    actAsCompanyAdmin($this);

    $parent = Company::factory()->create(['is_active' => true]);

    $child = Company::factory()->create(['parent_id' => $parent->id, 'is_active' => true]);

    $this->patch(route('admin.companies.toggle', $parent))
        ->assertStatus(422)
        ->assertJson(['error' => 'All child companies must be disabled before disabling this company.']);

    $parent->refresh();
    expect($parent->is_active)->toBeTrue();
});

// ---------------------------------------------------------------------------
// Create & Store
// ---------------------------------------------------------------------------

it('allows admin to see the create company form', function (): void {
    actAsCompanyAdmin($this);

    $this->get(route('admin.companies.create'))->assertSuccessful();
});

it('allows admin to store a new company with logo upload', function (): void {
    Storage::fake('public');
    actAsCompanyAdmin($this);

    $typeKey = array_key_first(config('company.types', ['operating' => 'Operating Company']));
    $logo = UploadedFile::fake()->create('logo.png', 100, 'image/png');

    $this->get(route('admin.companies.create'));

    $this->post(route('admin.companies.store'), [
        '_token' => session()->token(),
        'name' => 'Stark Industries',
        'slug' => 'stark-industries',
        'type' => $typeKey,
        'logo' => $logo,
        'is_active' => 1,
    ])->assertRedirect(route('admin.companies.index'))
        ->assertSessionHas('success', 'Company created successfully.');

    $this->assertDatabaseHas('companies', [
        'name' => 'Stark Industries',
        'slug' => 'stark-industries',
        'type' => $typeKey,
    ]);

    $company = Company::where('slug', 'stark-industries')->first();
    expect($company->logo)->not()->toBeNull();
    Storage::disk('public')->assertExists($company->logo);
});

it('validates required and unique fields when storing a company', function (): void {
    actAsCompanyAdmin($this);

    Company::factory()->create(['slug' => 'existing-slug']);

    $this->get(route('admin.companies.create'));

    // Missing fields & duplicate slug
    $this->post(route('admin.companies.store'), [
        '_token' => session()->token(),
        'name' => '',
        'slug' => 'existing-slug',
        'type' => 'invalid-type',
    ])->assertSessionHasErrors(['name', 'slug', 'type']);
});

// ---------------------------------------------------------------------------
// Edit & Update
// ---------------------------------------------------------------------------

it('allows admin to see the edit company form', function (): void {
    actAsCompanyAdmin($this);

    $company = Company::factory()->create();

    $this->get(route('admin.companies.edit', $company))->assertSuccessful();
});

it('allows admin to update company details and logo', function (): void {
    Storage::fake('public');
    actAsCompanyAdmin($this);

    $company = Company::factory()->create(['name' => 'Old Name', 'slug' => 'old-slug']);
    $typeKey = array_key_first(config('company.types', ['operating' => 'Operating Company']));
    $newLogo = UploadedFile::fake()->create('new-logo.png', 100, 'image/png');

    $this->get(route('admin.companies.edit', $company));

    $this->put(route('admin.companies.update', $company), [
        '_token' => session()->token(),
        'name' => 'New Name',
        'slug' => 'new-slug',
        'type' => $typeKey,
        'logo' => $newLogo,
        'is_active' => 1,
    ])->assertRedirect(route('admin.companies.index'))
        ->assertSessionHas('success', 'Company updated successfully.');

    $company->refresh();
    expect($company->name)->toBe('New Name')
        ->and($company->slug)->toBe('new-slug');
    Storage::disk('public')->assertExists($company->logo);
});

it('prevents setting a company as its own parent on update', function (): void {
    actAsCompanyAdmin($this);

    $company = Company::factory()->create(['name' => 'Self Co', 'slug' => 'self-co']);
    $typeKey = array_key_first(config('company.types', ['operating' => 'Operating Company']));

    $this->get(route('admin.companies.edit', $company));

    $this->put(route('admin.companies.update', $company), [
        '_token' => session()->token(),
        'name' => 'Self Co',
        'slug' => 'self-co',
        'type' => $typeKey,
        'parent_id' => $company->id,
    ])->assertSessionHasErrors(['parent_id']);
});

it('prevents setting a subsidiary as parent company on update', function (): void {
    actAsCompanyAdmin($this);

    $parent = Company::factory()->create(['slug' => 'parent-co']);
    $child = Company::factory()->create(['slug' => 'child-co', 'parent_id' => $parent->id]);
    $typeKey = array_key_first(config('company.types', ['operating' => 'Operating Company']));

    $this->get(route('admin.companies.edit', $parent));

    $this->put(route('admin.companies.update', $parent), [
        '_token' => session()->token(),
        'name' => $parent->name,
        'slug' => $parent->slug,
        'type' => $typeKey,
        'parent_id' => $child->id,
    ])->assertSessionHasErrors(['parent_id']);
});

// ---------------------------------------------------------------------------
// User Assignment & Removal
// ---------------------------------------------------------------------------

it('allows admin to assign a user to a company with job title and roles', function (): void {
    actAsCompanyAdmin($this);

    $targetCompany = Company::factory()->create();
    $newUser = User::factory()->create();
    $role = Role::where('name', 'user')->first();

    $this->get(route('admin.companies.edit', $targetCompany));

    $this->post(route('admin.companies.assign-user', $targetCompany), [
        '_token' => session()->token(),
        'user_id' => $newUser->id,
        'roles' => [$role->id],
        'job_title' => 'Software Engineer',
    ])->assertRedirect(route('admin.companies.edit', $targetCompany))
        ->assertSessionHas('success', 'User assigned to company.');

    $this->assertDatabaseHas('company_user', [
        'company_id' => $targetCompany->id,
        'user_id' => $newUser->id,
        'job_title' => 'Software Engineer',
        'status' => 'active',
    ]);

    $this->assertDatabaseHas('company_user_role', [
        'company_id' => $targetCompany->id,
        'user_id' => $newUser->id,
        'role_id' => $role->id,
    ]);
});

it('allows admin to remove a user from a company and reassign direct reportees to company level', function (): void {
    actAsCompanyAdmin($this);

    $targetCompany = Company::factory()->create();

    $manager = User::factory()->create();
    $reportee = User::factory()->create();

    $targetCompany->users()->attach($manager->id, ['status' => 'active', 'job_title' => 'Lead']);
    $targetCompany->users()->attach($reportee->id, [
        'status' => 'active',
        'job_title' => 'Developer',
        'manager_id' => $manager->id,
    ]);

    $this->get(route('admin.companies.edit', $targetCompany));

    $this->delete(route('admin.companies.remove-user', [$targetCompany, $manager->id]), [
        '_token' => session()->token(),
    ])->assertRedirect(route('admin.companies.edit', $targetCompany))
        ->assertSessionHas('success');

    // Manager should be detached from company
    $this->assertDatabaseMissing('company_user', [
        'company_id' => $targetCompany->id,
        'user_id' => $manager->id,
    ]);

    // Direct reportee's manager_id should be cleared to null
    $this->assertDatabaseHas('company_user', [
        'company_id' => $targetCompany->id,
        'user_id' => $reportee->id,
        'manager_id' => null,
    ]);
});
