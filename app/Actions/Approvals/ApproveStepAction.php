<?php

declare(strict_types=1);

namespace App\Actions\Approvals;

use App\Models\Approval\ApprovalRequestStep;
use App\Models\User;
use App\Services\ApprovalEngine;

final class ApproveStepAction
{
    public function __construct(
        private readonly ApprovalEngine $engine
    ) {}

    /**
     * Approve an approval step.
     */
    public function execute(
        ApprovalRequestStep $step,
        User $actingUser,
        ?string $notes = null
    ): void {
        if ($step->approver_id !== $actingUser->id) {
            abort(403);
        }

        $this->engine->approve($step, $actingUser, $notes);
    }
}
