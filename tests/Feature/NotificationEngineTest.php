<?php

declare(strict_types=1);

use App\Enums\ApprovalStatus;
use App\Enums\StepState;
use App\Events\Approval\ApprovalRequested;
use App\Events\NotificationRequested;
use App\Models\Approval\ApprovalRequest;
use App\Models\Approval\WorkflowTemplate;
use App\Models\Company;
use App\Models\User;
use App\Notifications\AppNotification;
use App\Services\NotificationEngine;
use App\ValueObjects\NotificationPayload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

test('NotificationRequested event triggers SendNotificationListener and stores in database', function () {
    Notification::fake();

    $user = User::factory()->create();

    NotificationRequested::dispatch(
        recipients: $user,
        payload: new NotificationPayload(
            title: 'Test Notification',
            message: 'This is a test notification message.',
            type: 'test',
            level: 'info'
        )
    );

    Notification::assertSentTo(
        $user,
        AppNotification::class,
        function (AppNotification $notification) {
            return $notification->payload->title === 'Test Notification'
                && $notification->payload->message === 'This is a test notification message.';
        }
    );
});

test('NotificationEngine directly sends notification payload to database', function () {
    $user = User::factory()->create();
    $engine = app(NotificationEngine::class);

    $engine->send(
        recipients: $user,
        payload: new NotificationPayload(
            title: 'Direct Engine Call',
            message: 'Sent directly via NotificationEngine.',
            type: 'system',
            level: 'success'
        )
    );

    expect($user->unreadNotifications()->count())->toBe(1);

    $notification = $user->unreadNotifications()->first();
    expect($notification->data['title'])->toBe('Direct Engine Call');
    expect($notification->data['message'])->toBe('Sent directly via NotificationEngine.');
});

test('ApprovalRequested domain event triggers ApprovalNotificationSubscriber and dispatches NotificationRequested', function () {
    $company = Company::factory()->create();
    $requestor = User::factory()->create();
    $approver = User::factory()->create();

    $requestor->companies()->attach($company->id);
    $approver->companies()->attach($company->id);

    $template = WorkflowTemplate::factory()->create(['company_id' => $company->id]);

    /** @var ApprovalRequest $approvalRequest */
    $approvalRequest = ApprovalRequest::create([
        'company_id' => $company->id,
        'requestor_id' => $requestor->id,
        'approvable_type' => 'TestApprovable',
        'approvable_id' => 1,
        'workflow_template_id' => $template->id,
        'workflow_snapshot' => ['groups' => []],
        'status' => ApprovalStatus::Pending,
    ]);

    $approvalRequest->steps()->create([
        'group_name' => 'Group 1',
        'step_label' => 'Step 1',
        'order' => 1,
        'resolver_type' => 'specific_user',
        'approver_id' => $approver->id,
        'state' => StepState::Pending,
    ]);

    ApprovalRequested::dispatch($approvalRequest);

    expect($approver->unreadNotifications()->count())->toBe(1);

    $notification = $approver->unreadNotifications()->first();
    expect($notification->data['title'])->toBe('Approval Requested');
    expect($notification->data['company_id'])->toBe($company->id);
});

test('GET /notifications/latest returns correct unread count and JSON payload', function () {
    $user = User::factory()->create();
    $engine = app(NotificationEngine::class);

    $engine->send(
        recipients: $user,
        payload: new NotificationPayload(
            title: 'Unread Alert',
            message: 'Check this alert.',
            type: 'alert'
        )
    );

    $this->actingAs($user)
        ->getJson(route('notifications.latest'))
        ->assertOk()
        ->assertJson([
            'unread_count' => 1,
        ])
        ->assertJsonPath('notifications.0.title', 'Unread Alert');
});

test('POST /notifications/{id}/read marks notification as read and auto-switches active company session', function () {
    $company1 = Company::factory()->create();
    $company2 = Company::factory()->create(['name' => 'Company 2']);

    $user = User::factory()->create();
    $user->companies()->attach([$company1->id, $company2->id]);

    $engine = app(NotificationEngine::class);

    $engine->send(
        recipients: $user,
        payload: new NotificationPayload(
            title: 'Switch Company Notification',
            message: 'Click to switch to company 2.',
            companyId: $company2->id,
            companyName: $company2->name,
            url: '/approvals/99'
        )
    );

    $notification = $user->unreadNotifications()->first();

    $this->actingAs($user)
        ->withSession(['active_company_id' => $company1->id])
        ->postJson(route('notifications.mark-as-read', $notification->id))
        ->assertOk()
        ->assertJson([
            'success' => true,
            'redirect_url' => '/approvals/99',
        ]);

    expect($notification->fresh()->read_at)->not()->toBeNull();
    expect(session('active_company_id'))->toBe($company2->id);
});

test('POST /notifications/read-all marks all unread notifications as read', function () {
    $user = User::factory()->create();
    $engine = app(NotificationEngine::class);

    $engine->send($user, new NotificationPayload(title: 'Note 1', message: 'Msg 1'));
    $engine->send($user, new NotificationPayload(title: 'Note 2', message: 'Msg 2'));

    expect($user->unreadNotifications()->count())->toBe(2);

    $this->actingAs($user)
        ->postJson(route('notifications.read-all'))
        ->assertOk()
        ->assertJson(['success' => true]);

    expect($user->unreadNotifications()->count())->toBe(0);
});

test('GET /notifications renders full notifications index page', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create();
    $user->companies()->attach($company->id);

    $engine = app(NotificationEngine::class);

    $engine->send($user, new NotificationPayload(title: 'Dashboard Alert', message: 'Hello World'));

    $this->actingAs($user)
        ->withSession(['active_company_id' => $company->id])
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertSee('Notifications Center')
        ->assertSee('Dashboard Alert');
});
