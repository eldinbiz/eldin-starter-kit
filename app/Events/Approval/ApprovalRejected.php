<?php

declare(strict_types=1);

namespace App\Events\Approval;

use App\Models\Approval\ApprovalRequestStep;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ApprovalRejected
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly ApprovalRequestStep $step,
    ) {}
}
