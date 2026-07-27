<?php

declare(strict_types=1);

namespace App\Listeners\Approval;

use App\Contracts\Approvable;
use App\Events\Approval\ApprovalCanceled;
use App\Events\Approval\ApprovalCompleted;
use App\Events\Approval\ApprovalRejected;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class UpdateApprovableStatus implements ShouldHandleEventsAfterCommit
{
    public function handle(ApprovalCompleted|ApprovalCanceled|ApprovalRejected $event): void
    {
        $request = match (true) {
            $event instanceof ApprovalCompleted => $event->approvalRequest,
            $event instanceof ApprovalCanceled => $event->approvalRequest,
            $event instanceof ApprovalRejected => $event->step->request()->first(),
        };

        if (! $request) {
            return;
        }

        $approvable = $request->approvable;

        if (! $approvable instanceof Approvable) {
            return;
        }

        match (true) {
            $event instanceof ApprovalCompleted => $approvable->onApprovalCompleted($request),
            $event instanceof ApprovalRejected => $approvable->onApprovalRejected($request),
            $event instanceof ApprovalCanceled => $approvable->onApprovalCanceled($request),
            default => null,
        };
    }
}
