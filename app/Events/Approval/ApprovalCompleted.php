<?php

declare(strict_types=1);

namespace App\Events\Approval;

use App\Models\Approval\ApprovalRequest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ApprovalCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly ApprovalRequest $approvalRequest,
    ) {}
}
