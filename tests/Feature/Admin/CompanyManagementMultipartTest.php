<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

uses(RefreshDatabase::class);

/** @var array<int, string> $multipartTempFiles */
$multipartTempFiles = [];

afterEach(function () use (&$multipartTempFiles): void {
    foreach ($multipartTempFiles as $path) {
        if (is_file($path)) {
            @unlink($path);
        }
    }

    $multipartTempFiles = [];
});

/**
 * Create a system administrator session with an active company for company HTTP tests.
 *
 * @return array{0: User, 1: Company}
 */
function authenticateCompanyMultipartAdmin(): array
{
    $company = Company::factory()->create(['is_active' => true]);
    $adminRole = Role::factory()->admin()->create();
    Role::factory()->user()->create();

    $user = User::factory()->create();
    $user->companies()->attach($company->id, ['status' => 'active']);
    $user->companyRoles()->attach($adminRole->id, ['company_id' => $company->id]);

    test()->actingAs($user);
    session(['active_company_id' => $company->id]);

    return [$user, $company];
}

it('creates a company via multipart POST when unselected logo arrives as an empty browser upload placeholder', function () use (&$multipartTempFiles): void {
    authenticateCompanyMultipartAdmin();

    $emptyPath = tempnam(sys_get_temp_dir(), 'company-create-empty-');
    expect($emptyPath)->not->toBeFalse();
    $multipartTempFiles[] = $emptyPath;

    $emptyLogo = new UploadedFile($emptyPath, '', 'application/octet-stream', null, true);
    $typeKey = (string) array_key_first(config('company.types', ['operating' => 'Operating Company']));

    $this->get(route('admin.companies.create'))->assertSuccessful();

    $this->post(route('admin.companies.store'), [
        '_token' => session()->token(),
        'name' => 'Global Holdings',
        'slug' => 'global-holdings',
        'type' => $typeKey,
        'parent_id' => '',
        'logo' => $emptyLogo,
        'is_active' => '1',
    ], [
        'Content-Type' => 'multipart/form-data; boundary=----PestCompanyBoundary',
    ])
        ->assertRedirect(route('admin.companies.index'))
        ->assertSessionHasNoErrors();

    $created = Company::where('slug', 'global-holdings')->first();

    expect($created)->not->toBeNull()
        ->and($created->name)->toBe('Global Holdings')
        ->and($created->logo)->toBeNull();
});

it('updates a company via _method=PUT multipart POST when unselected logo arrives as an empty placeholder and preserves existing logo', function () use (&$multipartTempFiles): void {
    authenticateCompanyMultipartAdmin();

    $company = Company::factory()->create([
        'name' => 'Acme Corporation',
        'slug' => 'acme-corporation',
        'logo' => 'logos/acme-corporation.png',
    ]);

    $emptyPath = tempnam(sys_get_temp_dir(), 'company-update-empty-');
    expect($emptyPath)->not->toBeFalse();
    $multipartTempFiles[] = $emptyPath;

    $emptyLogo = new UploadedFile($emptyPath, '', 'application/octet-stream', null, true);
    $typeKey = (string) array_key_first(config('company.types', ['operating' => 'Operating Company']));

    $this->get(route('admin.companies.edit', $company))->assertSuccessful();

    $this->post(route('admin.companies.update', $company), [
        '_token' => session()->token(),
        '_method' => 'PUT',
        'name' => 'Acme Corporation International',
        'slug' => 'acme-corporation',
        'type' => $typeKey,
        'parent_id' => '',
        'logo' => $emptyLogo,
        'is_active' => '1',
    ], [
        'Content-Type' => 'multipart/form-data; boundary=----PestCompanyBoundary',
    ])
        ->assertRedirect(route('admin.companies.index'))
        ->assertSessionHasNoErrors();

    $company->refresh();

    expect($company->name)->toBe('Acme Corporation International')
        ->and($company->logo)->toBe('logos/acme-corporation.png');
});
