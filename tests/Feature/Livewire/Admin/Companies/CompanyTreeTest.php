<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire\Admin\Companies;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->holding = Company::factory()->create(['name' => 'Mega Corp Holding', 'type' => 'holding', 'is_active' => true]);
    $this->subsidiary = Company::factory()->create(['name' => 'Tech Subsidiary', 'type' => 'operating', 'parent_id' => $this->holding->id, 'is_active' => true]);

    $this->adminRole = Role::firstOrCreate(['name' => 'platform-admin', 'label' => 'Platform Administrator', 'permissions' => ['*'], 'is_active' => true]);
    $this->admin = User::factory()->create(['name' => 'Super Admin', 'email' => 'admin@megacorp.com']);
    $this->admin->companies()->attach($this->holding->id, ['status' => 'active']);
    $this->admin->companyRoles()->attach($this->adminRole->id, ['company_id' => $this->holding->id]);
});

it('renders company tree component with hierarchy data', function () {
    $this->actingAs($this->admin);
    session(['active_company_id' => $this->holding->id]);

    $component = Livewire::test('admin.companies.company-tree')
        ->assertSuccessful();

    $treeData = $component->get('treeData');
    expect($treeData)->toHaveCount(2)
        ->and(collect($treeData)->pluck('name'))->toContain('Mega Corp Holding', 'Tech Subsidiary');
});

it('toggles subsidiary company active status', function () {
    $this->actingAs($this->admin);
    session(['active_company_id' => $this->holding->id]);

    Livewire::test('admin.companies.company-tree')
        ->call('toggleCompanyActive', $this->subsidiary->id)
        ->assertSee('disabled');

    $this->subsidiary->refresh();
    expect($this->subsidiary->is_active)->toBeFalse();
});

it('prevents disabling parent company if active children exist', function () {
    $this->actingAs($this->admin);
    session(['active_company_id' => $this->holding->id]);

    Livewire::test('admin.companies.company-tree')
        ->call('toggleCompanyActive', $this->holding->id)
        ->assertSee('All child companies must be disabled before disabling this company');

    $this->holding->refresh();
    expect($this->holding->is_active)->toBeTrue();
});

it('reparents a company to a new parent', function () {
    $this->actingAs($this->admin);
    session(['active_company_id' => $this->holding->id]);

    $newParent = Company::factory()->create(['name' => 'International Holding', 'type' => 'holding', 'is_active' => true]);

    Livewire::test('admin.companies.company-tree')
        ->call('reparentCompany', $this->subsidiary->id, $newParent->id)
        ->assertSee('Company parent structure successfully updated');

    $this->subsidiary->refresh();
    expect($this->subsidiary->parent_id)->toBe($newParent->id);
});
