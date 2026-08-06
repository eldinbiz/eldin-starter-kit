<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\NotificationRequested;
use App\Services\NotificationEngine;

class SendNotificationListener
{
    public function __construct(
        private readonly NotificationEngine $notificationEngine
    ) {}

    public function handle(NotificationRequested $event): void
    {
        $this->notificationEngine->send($event->recipients, $event->payload);
    }
}
