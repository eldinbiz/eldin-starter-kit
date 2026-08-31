<?php

declare(strict_types=1);

namespace App\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use Lab404\Impersonate\Guard\SessionGuard as BaseSessionGuard;

final class ImpersonationSessionGuard extends BaseSessionGuard
{
    /**
     * Log a user into the application without firing the Login event,
     * preserving previous session records to avoid race conditions with browser cookie sync.
     */
    public function quietLogin(Authenticatable $user): void
    {
        $this->session->put($this->getName(), $user->getAuthIdentifier());

        // Migrate session ID without immediately destroying the previous database row
        $this->session->migrate(false);

        $this->setUser($user);
        $this->loggedOut = false;
    }

    /**
     * Logout the user without updating remember_token and without firing the Logout event.
     */
    public function quietLogout(): void
    {
        $this->clearUserDataFromStorage();

        $this->user = null;
        $this->loggedOut = false;
    }
}
