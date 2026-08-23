<?php

declare(strict_types=1);

namespace App\Actions\Approvals;

use App\Models\Approval\ApprovalRequestStep;
use App\Models\User;
use App\Services\ApprovalEngine;

final class SkipApprovalStepAction
{
    public function __construct(
        private readonly ApprovalEngine $engine
    ) {}

    /**
     * Force-skip an approval step as an administrator.
     */
    public function execute(ApprovalRequestStep $step, User $actingUser): void
    {
        $this->engine->skipStep($step, $actingUser);
    }
}
