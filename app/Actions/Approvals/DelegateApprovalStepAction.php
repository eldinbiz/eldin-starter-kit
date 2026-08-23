<?php

declare(strict_types=1);

namespace App\Actions\Approvals;

use App\Models\Approval\ApprovalRequestStep;
use App\Models\User;
use App\Services\ApprovalEngine;

final class DelegateApprovalStepAction
{
    public function __construct(
        private readonly ApprovalEngine $engine
    ) {}

    /**
     * Delegate a pending step to a new approver.
     */
    public function execute(
        ApprovalRequestStep $step,
        int|User $newApprover,
        User $actingUser
    ): void {
        $targetUser = $newApprover instanceof User
            ? $newApprover
            : User::findOrFail($newApprover);

        $this->engine->delegate($step, $targetUser, $actingUser);
    }
}
