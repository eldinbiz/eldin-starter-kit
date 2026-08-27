<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire\Admin\ApiDocs;

use App\Livewire\Admin\ApiDocs\ApiDocsViewer;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create(['name' => 'Acme Corp', 'is_active' => true]);

    $this->adminRole = Role::firstOrCreate(['name' => 'admin', 'label' => 'Administrator', 'is_active' => true], ['permissions' => ['*']]);
    $this->adminRole->update(['permissions' => ['*']]);

    $this->user = User::factory()->create(['name' => 'Admin User', 'email' => 'admin@acme.com']);
    $this->user->companies()->attach($this->company->id, ['status' => 'active']);
    $this->user->companyRoles()->attach($this->adminRole->id, ['company_id' => $this->company->id]);
});

it('renders api docs explorer with endpoint list and snippets', function () {
    $this->actingAs($this->user);
    session(['active_company_id' => $this->company->id]);

    Livewire::test(ApiDocsViewer::class)
        ->assertSuccessful()
        ->assertSee('REST API Explorer')
        ->assertSee('/api/v1/me')
        ->assertSee('Get Authenticated User Profile')
        ->assertSee('curl -X GET');
});

it('filters endpoints by module tag', function () {
    $this->actingAs($this->user);
    session(['active_company_id' => $this->company->id]);

    Livewire::test(ApiDocsViewer::class)
        ->call('setTag', 'users')
        ->assertSet('selectedTag', 'users')
        ->assertSee('/api/v1/users')
        ->assertDontSee('/api/v1/approvals');
});

it('switches snippet language to php and javascript', function () {
    $this->actingAs($this->user);
    session(['active_company_id' => $this->company->id]);

    Livewire::test(ApiDocsViewer::class)
        ->call('setSnippetLanguage', 'js')
        ->assertSet('snippetLanguage', 'js')
        ->assertSee('fetch(')
        ->call('setSnippetLanguage', 'php')
        ->assertSet('snippetLanguage', 'php')
        ->assertSee('GuzzleHttp\Client');
});

it('executes api request against subrequest handler', function () {
    $this->actingAs($this->user);
    session(['active_company_id' => $this->company->id]);

    Livewire::test(ApiDocsViewer::class)
        ->call('selectEndpoint', 'auth_me')
        ->call('executeRequest')
        ->assertSet('responseStatus', 200)
        ->assertSee('admin@acme.com');
});
