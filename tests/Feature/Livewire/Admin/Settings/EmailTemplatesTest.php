<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire\Admin\Settings;

use App\Models\Company;
use App\Models\EmailTemplate;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->company = Company::factory()->create(['name' => 'HQ Corporation', 'is_active' => true]);

    $this->adminRole = Role::create([
        'name' => 'admin',
        'permissions' => ['manage-system-settings'],
    ]);

    $this->adminUser = User::factory()->create(['email' => 'admin@example.com']);
    $this->adminUser->companies()->attach($this->company->id, ['status' => 'active']);
    $this->adminUser->companyRoles()->attach($this->adminRole->id, ['company_id' => $this->company->id]);

    session(['active_company_id' => $this->company->id]);

    $this->globalTemplate = EmailTemplate::create([
        'name' => 'Purchase Request Submitted',
        'event' => 'pr_submitted',
        'subject' => 'New PR: {{ title }}',
        'body_html' => '<p>Hello {{ user_name }}, your request {{ title }} is submitted.</p>',
        'is_active' => true,
    ]);
});

it('renders template list and filters by scope and search', function (): void {
    $this->actingAs($this->adminUser);

    EmailTemplate::create([
        'name' => 'HQ Custom Alert',
        'event' => 'hq_alert',
        'subject' => 'Alert for HQ',
        'body_html' => '<p>HQ Alert</p>',
        'company_id' => $this->company->id,
        'is_active' => true,
    ]);

    Livewire::test('admin.settings.email-templates.template-list')
        ->assertSuccessful()
        ->assertSee('Purchase Request Submitted')
        ->set('selectedCompanyId', $this->company->id)
        ->assertSee('HQ Custom Alert')
        ->set('search', 'Purchase')
        ->assertSee('Purchase Request Submitted');
});

it('prompts delete and deletes a template', function (): void {
    $this->actingAs($this->adminUser);

    Livewire::test('admin.settings.email-templates.template-list')
        ->call('promptDelete', $this->globalTemplate->id)
        ->assertSet('templateToDeleteId', $this->globalTemplate->id)
        ->call('deleteTemplate')
        ->assertSet('templateToDeleteId', null);

    expect(EmailTemplate::find($this->globalTemplate->id))->toBeNull();
});

it('creates and edits a template via TemplateEditor component', function (): void {
    $this->actingAs($this->adminUser);

    Livewire::test('admin.settings.email-templates.template-editor')
        ->set('name', 'Invoice Generated')
        ->set('event', 'invoice_generated')
        ->set('subject', 'Invoice #{{ invoice_num }} Ready')
        ->set('body_html', '<p>Your invoice is attached.</p>')
        ->call('addVariable')
        ->call('save')
        ->assertRedirect(route('admin.settings.email-templates.index'));

    expect(EmailTemplate::where('event', 'invoice_generated')->exists())->toBeTrue();
});

it('generates live preview and manages variables in TemplateEditor', function (): void {
    $this->actingAs($this->adminUser);

    Livewire::test('admin.settings.email-templates.template-editor', ['templateId' => $this->globalTemplate->id])
        ->assertSet('name', 'Purchase Request Submitted')
        ->set('newVariable', 'custom_field')
        ->call('addVariable')
        ->assertCount('variables', 11)
        ->call('removeVariable', 'custom_field')
        ->assertCount('variables', 10)
        ->call('openPreview')
        ->assertSet('previewModalOpen', true)
        ->assertSee('New PR: Purchase Request PR-2026-0042')
        ->call('closePreview')
        ->assertSet('previewModalOpen', false);
});
