<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Models\User;
use InvalidArgumentException;

final class DeleteUserAction
{
    /**
     * Delete a user, ensuring the authenticated user cannot delete their own account.
     *
     * @throws InvalidArgumentException
     */
    public function execute(User $user, int $currentUserId): void
    {
        if ($user->id === $currentUserId) {
            throw new InvalidArgumentException('You cannot delete your own account.');
        }

        $user->delete();
    }
}
