<?php

declare(strict_types=1);

namespace App\Events\Approval;

use App\Models\Approval\ApprovalRequestStep;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ApprovalDelegated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly ApprovalRequestStep $step,
        public readonly User $delegatedBy,
        public readonly User $newApprover,
    ) {}
}
