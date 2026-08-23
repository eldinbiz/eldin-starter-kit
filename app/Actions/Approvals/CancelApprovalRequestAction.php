<?php

declare(strict_types=1);

namespace App\Actions\Approvals;

use App\Models\Approval\ApprovalRequest;
use App\Models\User;
use App\Services\ApprovalEngine;

final class CancelApprovalRequestAction
{
    public function __construct(
        private readonly ApprovalEngine $engine
    ) {}

    /**
     * Cancel an active approval request.
     */
    public function execute(ApprovalRequest $request, User $actingUser): void
    {
        if ($request->requestor_id !== $actingUser->id) {
            abort(403);
        }

        $this->engine->cancel($request, $actingUser);
    }
}
