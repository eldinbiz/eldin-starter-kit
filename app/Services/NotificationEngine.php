<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Notifications\AppNotification;
use App\ValueObjects\NotificationPayload;
use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use Illuminate\Support\Facades\Notification;

class NotificationEngine
{
    /**
     * Dispatch a notification payload to one or more recipients.
     *
     * @param User|Enumerable<int, User>|array<int, User|int>|int $recipients
     */
    public function send(User|Enumerable|array|int $recipients, NotificationPayload $payload): void
    {
        $resolvedRecipients = $this->resolveRecipients($recipients);

        if ($resolvedRecipients->isEmpty()) {
            return;
        }

        Notification::send($resolvedRecipients, new AppNotification($payload));
    }

    /**
     * @param User|Enumerable<int, User>|array<int, User|int>|int $recipients
     * @return Collection<int, User>
     */
    private function resolveRecipients(User|Enumerable|array|int $recipients): Collection
    {
        if ($recipients instanceof User) {
            return collect([$recipients]);
        }

        if ($recipients instanceof Enumerable) {
            return $recipients->collect();
        }

        if (is_int($recipients)) {
            $user = User::find($recipients);
            return $user ? collect([$user]) : collect();
        }

        if (is_array($recipients)) {
            $userIds = array_filter($recipients, 'is_int');
            $userModels = array_filter($recipients, fn ($r) => $r instanceof User);

            $fetched = ! empty($userIds) ? User::whereIn('id', $userIds)->get() : collect();

            return collect($userModels)->merge($fetched)->unique('id')->values();
        }

        return collect();
    }
}
