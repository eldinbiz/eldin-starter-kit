<?php

declare(strict_types=1);

namespace App\Enums;

enum StepState: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Skipped = 'skipped';
    case AwaitingPrevious = 'awaiting_previous';
}
