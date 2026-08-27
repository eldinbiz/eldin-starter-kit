<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire\Admin\AuditTrail;

use App\Livewire\Admin\AuditTrail\AuditTrailViewer;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create(['name' => 'Acme Corp', 'is_active' => true]);

    $this->adminRole = Role::firstOrCreate(['name' => 'admin', 'label' => 'Administrator', 'is_active' => true], ['permissions' => ['*']]);
    $this->adminRole->update(['permissions' => ['*']]);

    $this->user = User::factory()->create(['name' => 'Admin User', 'email' => 'admin@acme.com']);
    $this->user->companies()->attach($this->company->id, ['status' => 'active']);
    $this->user->companyRoles()->attach($this->adminRole->id, ['company_id' => $this->company->id]);

    // Ensure audit_log table exists or create if needed
    if (! Schema::hasTable('audit_log')) {
        Schema::create('audit_log', function ($table) {
            $table->id();
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->string('event')->default('created');
            $table->string('actor_type')->nullable();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_label')->nullable();
            $table->text('changes')->nullable();
            $table->string('correlation_id')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();
        });
    }
});

it('renders audit trail table and filters by event', function () {
    $this->actingAs($this->user);
    session(['active_company_id' => $this->company->id]);

    DB::table('audit_log')->insert([
        [
            'auditable_type' => 'App\Models\User',
            'auditable_id' => 1,
            'event' => 'created',
            'actor_type' => 'App\Models\User',
            'actor_label' => 'Super Admin',
            'labels' => '[]',
            'changes' => json_encode(['name' => 'New User']),
            'correlation_id' => 'corr-12345',
            'occurred_at' => now(),
            'created_at' => now(),
        ],
        [
            'auditable_type' => 'App\Models\Company',
            'auditable_id' => 2,
            'event' => 'deleted',
            'actor_type' => 'App\Models\User',
            'actor_label' => 'Super Admin',
            'labels' => '[]',
            'changes' => json_encode(['status' => 'inactive']),
            'correlation_id' => 'corr-67890',
            'occurred_at' => now()->subHour(),
            'created_at' => now(),
        ],
    ]);

    Livewire::test(AuditTrailViewer::class)
        ->assertSuccessful()
        ->assertSee('Super Admin')
        ->assertSee('User #1')
        ->assertSee('Company #2')
        ->set('filterValues.event', 'created')
        ->assertSee('User #1')
        ->assertDontSee('Company #2');
});

it('opens diff modal with recorded changes', function () {
    $this->actingAs($this->user);
    session(['active_company_id' => $this->company->id]);

    $id = DB::table('audit_log')->insertGetId([
        'auditable_type' => 'App\Models\User',
        'auditable_id' => 42,
        'event' => 'updated',
        'actor_type' => 'App\Models\User',
        'actor_label' => 'Admin User',
        'labels' => '[]',
        'changes' => json_encode(['old' => ['name' => 'Old'], 'new' => ['name' => 'New']]),
        'correlation_id' => 'corr-inspect-1',
        'occurred_at' => now(),
        'created_at' => now(),
    ]);

    Livewire::test(AuditTrailViewer::class)
        ->call('openDiffModal', $id)
        ->assertSet('diffModalOpen', true)
        ->assertSet('selectedLogId', $id)
        ->assertSee('Audit Diff: User #42')
        ->assertSee('corr-inspect-1');
});

it('filters by correlation ID from modal', function () {
    $this->actingAs($this->user);
    session(['active_company_id' => $this->company->id]);

    Livewire::test(AuditTrailViewer::class)
        ->call('filterByCorrelation', 'corr-target-99')
        ->assertSet('filterValues.correlation_id', 'corr-target-99')
        ->assertSet('diffModalOpen', false);
});
