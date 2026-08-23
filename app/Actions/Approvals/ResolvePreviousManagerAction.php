<?php

declare(strict_types=1);

namespace App\Actions\Approvals;

use App\Models\Approval\ApprovalRequestStep;
use App\Models\User;
use App\Services\ApprovalEngine;

final class ResolvePreviousManagerAction
{
    public function __construct(
        private readonly ApprovalEngine $engine
    ) {}

    /**
     * Resolve the N+1_of_previous approver at runtime.
     */
    public function execute(
        ApprovalRequestStep $step,
        User $actingUser,
        int|User $newApprover
    ): void {
        $targetUser = $newApprover instanceof User
            ? $newApprover
            : User::findOrFail($newApprover);

        $this->engine->resolvePreviousManager($step, $actingUser, $targetUser);
    }
}
