<?php

declare(strict_types=1);

namespace App\Actions\Roles;

use App\Models\Role;
use InvalidArgumentException;

final class DisableRoleAction
{
    /**
     * Disable a role (set is_active = false), ensuring default 'user' role is protected.
     *
     * @throws InvalidArgumentException
     */
    public function execute(Role $role): Role
    {
        if ($role->name === 'user') {
            throw new InvalidArgumentException('The "user" role cannot be disabled.');
        }

        $role->update(['is_active' => false]);

        return $role;
    }
}
