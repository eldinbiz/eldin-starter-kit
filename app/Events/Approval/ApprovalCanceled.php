<?php

declare(strict_types=1);

namespace App\Events\Approval;

use App\Models\Approval\ApprovalRequest;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ApprovalCanceled
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly ApprovalRequest $approvalRequest,
        public readonly User $canceledBy,
    ) {}
}
