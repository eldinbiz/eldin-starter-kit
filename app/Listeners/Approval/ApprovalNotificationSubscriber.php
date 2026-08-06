<?php

declare(strict_types=1);

namespace App\Listeners\Approval;

use App\Enums\StepState;
use App\Events\Approval\ApprovalCanceled;
use App\Events\Approval\ApprovalCompleted;
use App\Events\Approval\ApprovalDelegated;
use App\Events\Approval\ApprovalRejected;
use App\Events\Approval\ApprovalRequested;
use App\Events\Approval\ApprovalStepCompleted;
use App\Events\NotificationRequested;
use App\ValueObjects\NotificationPayload;
use Illuminate\Events\Dispatcher;

class ApprovalNotificationSubscriber
{
    public function handleApprovalRequested(ApprovalRequested $event): void
    {
        $request = $event->approvalRequest;
        $request->loadMissing(['steps.approver', 'company']);

        $pendingSteps = $request->steps->where('state', StepState::Pending);

        foreach ($pendingSteps as $step) {
            if ($step->approver) {
                NotificationRequested::dispatch(
                    recipients: $step->approver,
                    payload: new NotificationPayload(
                        title: 'Approval Requested',
                        message: "Approval request #{$request->id} ({$step->step_label}) requires your review.",
                        type: 'approval',
                        url: "/admin/approval-requests/{$request->id}",
                        actionText: 'Review Request',
                        companyId: $request->company_id,
                        companyName: $request->company?->name,
                        level: 'info'
                    )
                );
            }
        }
    }

    public function handleApprovalStepCompleted(ApprovalStepCompleted $event): void
    {
        $step = $event->step;
        $request = $step->loadMissing('request')->request;
        $request->loadMissing(['steps.approver', 'company', 'requestor']);

        // 1. Notify Next Step Approvers if any steps just transitioned to Pending
        $pendingSteps = $request->steps->where('state', StepState::Pending);

        foreach ($pendingSteps as $nextStep) {
            if ($nextStep->approver && $nextStep->id !== $step->id) {
                NotificationRequested::dispatch(
                    recipients: $nextStep->approver,
                    payload: new NotificationPayload(
                        title: 'Approval Requested',
                        message: "Approval request #{$request->id} ({$nextStep->step_label}) is ready for your review.",
                        type: 'approval',
                        url: "/admin/approval-requests/{$request->id}",
                        actionText: 'Review Request',
                        companyId: $request->company_id,
                        companyName: $request->company?->name,
                        level: 'info'
                    )
                );
            }
        }

        // 2. Notify Requestor of Step Approval
        if ($request->requestor) {
            NotificationRequested::dispatch(
                recipients: $request->requestor,
                payload: new NotificationPayload(
                    title: 'Step Approved',
                    message: "Step \"{$step->step_label}\" for approval request #{$request->id} has been approved.",
                    type: 'approval',
                    url: "/admin/approval-requests/{$request->id}",
                    actionText: 'View Status',
                    companyId: $request->company_id,
                    companyName: $request->company?->name,
                    level: 'info'
                )
            );
        }
    }

    public function handleApprovalCompleted(ApprovalCompleted $event): void
    {
        $request = $event->approvalRequest;
        $request->loadMissing(['company', 'requestor']);

        if ($request->requestor) {
            NotificationRequested::dispatch(
                recipients: $request->requestor,
                payload: new NotificationPayload(
                    title: 'Approval Request Approved',
                    message: "Your approval request #{$request->id} has been fully approved.",
                    type: 'approval',
                    url: "/admin/approval-requests/{$request->id}",
                    actionText: 'View Details',
                    companyId: $request->company_id,
                    companyName: $request->company?->name,
                    level: 'success'
                )
            );
        }
    }

    public function handleApprovalRejected(ApprovalRejected $event): void
    {
        $step = $event->step;
        $request = $step->loadMissing('request')->request;
        $request->loadMissing(['company', 'requestor']);

        if ($request->requestor) {
            NotificationRequested::dispatch(
                recipients: $request->requestor,
                payload: new NotificationPayload(
                    title: 'Approval Request Rejected',
                    message: "Your approval request #{$request->id} was rejected at step \"{$step->step_label}\".",
                    type: 'approval',
                    url: "/admin/approval-requests/{$request->id}",
                    actionText: 'View Details',
                    companyId: $request->company_id,
                    companyName: $request->company?->name,
                    level: 'danger'
                )
            );
        }
    }

    public function handleApprovalCanceled(ApprovalCanceled $event): void
    {
        $request = $event->approvalRequest;
        $request->loadMissing(['steps.approver', 'company']);

        $pendingSteps = $request->steps->where('state', StepState::Pending);

        foreach ($pendingSteps as $step) {
            if ($step->approver) {
                NotificationRequested::dispatch(
                    recipients: $step->approver,
                    payload: new NotificationPayload(
                        title: 'Approval Request Canceled',
                        message: "Approval request #{$request->id} was canceled by {$event->canceledBy->name}.",
                        type: 'approval',
                        url: "/admin/approval-requests/{$request->id}",
                        actionText: 'View Status',
                        companyId: $request->company_id,
                        companyName: $request->company?->name,
                        level: 'warning'
                    )
                );
            }
        }
    }

    public function handleApprovalDelegated(ApprovalDelegated $event): void
    {
        $step = $event->step;
        $request = $step->loadMissing('request')->request;
        $request->loadMissing('company');

        NotificationRequested::dispatch(
            recipients: $event->newApprover,
            payload: new NotificationPayload(
                title: 'Approval Delegated to You',
                message: "{$event->delegatedBy->name} delegated step \"{$step->step_label}\" for approval request #{$request->id} to you.",
                type: 'approval',
                url: "/admin/approval-requests/{$request->id}",
                actionText: 'Review Request',
                companyId: $request->company_id,
                companyName: $request->company?->name,
                level: 'info'
            )
        );
    }

    /**
     * Register listeners for subscriber.
     *
     * @return array<string, string>
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            ApprovalRequested::class => 'handleApprovalRequested',
            ApprovalStepCompleted::class => 'handleApprovalStepCompleted',
            ApprovalCompleted::class => 'handleApprovalCompleted',
            ApprovalRejected::class => 'handleApprovalRejected',
            ApprovalCanceled::class => 'handleApprovalCanceled',
            ApprovalDelegated::class => 'handleApprovalDelegated',
        ];
    }
}
