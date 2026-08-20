<?php

declare(strict_types=1);

namespace App\Actions\Roles;

use App\Models\Role;

final class RestoreRoleAction
{
    /**
     * Enable or restore a role from trash and set active.
     *
     * @return string 'restored' if recovered from soft-deletion, or 'enabled' if activated from disabled status
     */
    public function execute(Role $role): string
    {
        if ($role->trashed()) {
            $role->restore();
            $role->update(['is_active' => true]);

            return 'restored';
        }

        $role->update(['is_active' => true]);

        return 'enabled';
    }
}
