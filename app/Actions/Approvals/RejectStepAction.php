<?php

declare(strict_types=1);

namespace App\Actions\Approvals;

use App\Models\Approval\ApprovalRequestStep;
use App\Models\User;
use App\Services\ApprovalEngine;

final class RejectStepAction
{
    public function __construct(
        private readonly ApprovalEngine $engine
    ) {}

    /**
     * Reject an approval step.
     */
    public function execute(
        ApprovalRequestStep $step,
        User $actingUser,
        string $reason
    ): void {
        if ($step->approver_id !== $actingUser->id) {
            abort(403);
        }

        $this->engine->reject($step, $actingUser, $reason);
    }
}
