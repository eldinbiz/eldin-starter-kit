<?php

declare(strict_types=1);

namespace App\Actions\Roles;

use App\Models\Role;
use InvalidArgumentException;

final class DeleteRoleAction
{
    /**
     * Soft-delete a disabled role, ensuring it is disabled and not the default 'user' role.
     *
     * @throws InvalidArgumentException
     */
    public function execute(Role $role): void
    {
        if ($role->name === 'user') {
            throw new InvalidArgumentException('The "user" role cannot be deleted.');
        }

        if ($role->is_active) {
            throw new InvalidArgumentException('Role "'.$role->name.'" must be disabled before it can be deleted.');
        }

        $role->delete();
    }
}
