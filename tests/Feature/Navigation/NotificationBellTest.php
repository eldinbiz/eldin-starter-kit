<?php

declare(strict_types=1);

namespace Tests\Feature\Navigation;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create(['name' => 'Acme Corp']);
    $this->role = Role::firstOrCreate(['name' => 'manager', 'label' => 'Manager']);

    $this->user = User::factory()->create();
    $this->user->companies()->attach($this->company->id);
    $this->user->companyRoles()->attach($this->role->id, ['company_id' => $this->company->id]);
});

it('renders the notification bell component with zero notifications initially', function () {
    $this->actingAs($this->user);

    Livewire::test('navigation.notification-bell')
        ->assertSuccessful()
        ->assertSet('open', false)
        ->assertDontSee('unread');
});

it('displays unread notification badges and popover details', function () {
    $this->actingAs($this->user);

    // Create a database notification for the user
    DatabaseNotification::create([
        'id' => (string) Str::uuid(),
        'type' => 'App\Notifications\SystemAlert',
        'notifiable_type' => User::class,
        'notifiable_id' => $this->user->id,
        'data' => [
            'title' => 'Purchase Request Approved',
            'message' => 'Your request PR-1001 was approved.',
            'company_id' => $this->company->id,
            'company_name' => $this->company->name,
        ],
        'read_at' => null,
    ]);

    Livewire::test('navigation.notification-bell')
        ->call('toggleDropdown')
        ->assertSet('open', true)
        ->assertSee('Purchase Request Approved')
        ->assertSee('Your request PR-1001 was approved.')
        ->assertSee('1 unread');
});

it('marks a single notification as read and auto-switches company context if applicable', function () {
    $this->actingAs($this->user);

    $notification = DatabaseNotification::create([
        'id' => (string) Str::uuid(),
        'type' => 'App\Notifications\SystemAlert',
        'notifiable_type' => User::class,
        'notifiable_id' => $this->user->id,
        'data' => [
            'title' => 'Invoice Paid',
            'message' => 'Invoice #999 was processed.',
            'company_id' => $this->company->id,
            'company_name' => $this->company->name,
            'url' => '/dashboard',
        ],
        'read_at' => null,
    ]);

    Livewire::test('navigation.notification-bell')
        ->call('markAsRead', $notification->id)
        ->assertRedirect('/dashboard');

    expect($notification->fresh()->read_at)->not->toBeNull();
    expect(session('active_company_id'))->toBe($this->company->id);
});

it('marks all notifications as read', function () {
    $this->actingAs($this->user);

    DatabaseNotification::create([
        'id' => (string) Str::uuid(),
        'type' => 'App\Notifications\Alert1',
        'notifiable_type' => User::class,
        'notifiable_id' => $this->user->id,
        'data' => ['title' => 'First'],
        'read_at' => null,
    ]);

    DatabaseNotification::create([
        'id' => (string) Str::uuid(),
        'type' => 'App\Notifications\Alert2',
        'notifiable_type' => User::class,
        'notifiable_id' => $this->user->id,
        'data' => ['title' => 'Second'],
        'read_at' => null,
    ]);

    expect($this->user->unreadNotifications()->count())->toBe(2);

    Livewire::test('navigation.notification-bell')
        ->call('markAllAsRead');

    expect($this->user->fresh()->unreadNotifications()->count())->toBe(0);
});
