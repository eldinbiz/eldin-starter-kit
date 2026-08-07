<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\User;
use App\ValueObjects\NotificationPayload;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Enumerable;

class NotificationRequested
{
    use Dispatchable, SerializesModels;

    /**
     * @param  User|Enumerable<int, User>|array<int, User|int>|int  $recipients
     */
    public function __construct(
        public readonly User|Enumerable|array|int $recipients,
        public readonly NotificationPayload $payload
    ) {}
}
