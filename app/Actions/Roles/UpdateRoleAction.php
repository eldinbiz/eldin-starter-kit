<?php

declare(strict_types=1);

namespace App\Actions\Roles;

use App\Models\Role;

final class UpdateRoleAction
{
    /**
     * Update an existing role's name and permissions, merging configured always-enabled permissions.
     *
     * @param array{
     *     name: string,
     *     permissions?: array<int, string>|null
     * } $data
     */
    public function execute(Role $role, array $data): Role
    {
        $permissions = $this->mergeAlwaysEnabledPermissions($data['permissions'] ?? []);

        $role->update([
            'name' => (string) $data['name'],
            'permissions' => $permissions,
        ]);

        return $role;
    }

    /**
     * Merge any always-enabled permissions into the given permissions array.
     *
     * @param  array<int, string>  $permissions
     * @return array<int, string>
     */
    private function mergeAlwaysEnabledPermissions(array $permissions): array
    {
        foreach (config('permissions.list', []) as $key => $definition) {
            if (($definition['always_enabled'] ?? false) && ! in_array($key, $permissions, true)) {
                $permissions[] = (string) $key;
            }
        }

        return $permissions;
    }
}
