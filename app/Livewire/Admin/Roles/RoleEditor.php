<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Roles;

use App\Actions\Roles\CreateRoleAction;
use App\Actions\Roles\UpdateRoleAction;
use App\Models\Role;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

final class RoleEditor extends Component
{
    public ?int $roleId = null;

    public string $name = '';

    /**
     * @var array<int, string>
     */
    public array $selectedPermissions = [];

    public string $permissionSearch = '';

    public bool $is_active = true;

    public bool $is_system = false;

    public ?string $flashSuccess = null;

    public ?string $flashError = null;

    public function mount(?int $roleId = null): void
    {
        $this->roleId = $roleId;

        if ($this->roleId !== null) {
            $role = Role::withTrashed()->findOrFail($this->roleId);
            $this->name = $role->name;
            $this->selectedPermissions = $role->permissions ?? [];
            $this->is_active = (bool) $role->is_active;
            $this->is_system = (bool) ($role->is_system ?? false);
        } else {
            $this->selectedPermissions = [];
        }

        // Always ensure always_enabled permissions are selected
        $this->mergeAlwaysEnabled();
    }

    /**
     * Ensure always_enabled permissions are present in selectedPermissions.
     */
    private function mergeAlwaysEnabled(): void
    {
        foreach (config('permissions.list', []) as $key => $definition) {
            if (($definition['always_enabled'] ?? false) && ! in_array($key, $this->selectedPermissions, true)) {
                $this->selectedPermissions[] = (string) $key;
            }
        }
    }

    /**
     * Check if a specific permission is marked always_enabled.
     */
    public function isAlwaysEnabled(string $key): bool
    {
        $definition = config("permissions.list.{$key}");

        return (bool) ($definition['always_enabled'] ?? false);
    }

    /**
     * Toggle a single permission checkbox.
     */
    public function togglePermission(string $key): void
    {
        if ($this->isAlwaysEnabled($key)) {
            return;
        }

        if (in_array($key, $this->selectedPermissions, true)) {
            $this->selectedPermissions = array_values(array_filter(
                $this->selectedPermissions,
                fn (string $p): bool => $p !== $key
            ));
        } else {
            $this->selectedPermissions[] = $key;
        }
    }

    /**
     * Toggle all selectable permissions for a given group.
     */
    public function toggleGroup(string $group): void
    {
        $groupKeys = $this->getGroupPermissionKeys($group);

        if (empty($groupKeys)) {
            return;
        }

        if ($this->isGroupFullySelected($group)) {
            // Deselect all non-always-enabled keys in this group
            $this->selectedPermissions = array_values(array_filter(
                $this->selectedPermissions,
                fn (string $key): bool => ! in_array($key, $groupKeys, true) || $this->isAlwaysEnabled($key)
            ));
        } else {
            // Select all keys in this group
            foreach ($groupKeys as $key) {
                if (! in_array($key, $this->selectedPermissions, true)) {
                    $this->selectedPermissions[] = $key;
                }
            }
        }
    }

    /**
     * Check whether all selectable permissions in a group are checked.
     */
    public function isGroupFullySelected(string $group): bool
    {
        $groupKeys = $this->getGroupPermissionKeys($group);

        if (empty($groupKeys)) {
            return false;
        }

        foreach ($groupKeys as $key) {
            if (! in_array($key, $this->selectedPermissions, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check whether a group is partially selected.
     */
    public function isGroupPartiallySelected(string $group): bool
    {
        $groupKeys = $this->getGroupPermissionKeys($group);

        if (empty($groupKeys)) {
            return false;
        }

        $selectedCount = 0;
        foreach ($groupKeys as $key) {
            if (in_array($key, $this->selectedPermissions, true)) {
                $selectedCount++;
            }
        }

        return $selectedCount > 0 && $selectedCount < count($groupKeys);
    }

    /**
     * Get all permission keys for a given group name.
     *
     * @return array<int, string>
     */
    private function getGroupPermissionKeys(string $group): array
    {
        $keys = [];
        foreach (config('permissions.list', []) as $key => $definition) {
            if (($definition['group'] ?? '') === $group) {
                $keys[] = (string) $key;
            }
        }

        return $keys;
    }

    /**
     * Save or update the role.
     */
    public function save(CreateRoleAction $createAction, UpdateRoleAction $updateAction): void
    {
        $this->authorize('manage-roles');

        $this->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')->ignore($this->roleId),
            ],
            'selectedPermissions' => ['array'],
            'selectedPermissions.*' => ['string'],
        ]);

        $data = [
            'name' => strtolower(trim($this->name)),
            'permissions' => $this->selectedPermissions,
        ];

        if ($this->roleId !== null) {
            $role = Role::withTrashed()->findOrFail($this->roleId);
            if ($role->is_system) {
                $data['name'] = $role->name;
            }
            $updateAction->execute($role, $data);
            session()->flash('success', 'Role updated successfully.');
        } else {
            $createAction->execute($data);
            session()->flash('success', 'Role created successfully.');
        }

        $this->redirect(route('admin.roles.index'), navigate: true);
    }

    #[Computed]
    public function isEditing(): bool
    {
        return $this->roleId !== null;
    }

    #[Computed]
    public function groupedPermissions(): array
    {
        $groups = config('permissions.groups', []);
        $allPermissions = config('permissions.list', []);
        $search = strtolower(trim($this->permissionSearch));

        $result = [];

        foreach ($groups as $group) {
            $groupItems = [];

            foreach ($allPermissions as $key => $definition) {
                if (($definition['group'] ?? '') !== $group) {
                    continue;
                }

                if ($search !== '') {
                    $label = strtolower((string) ($definition['label'] ?? ''));
                    $keyLower = strtolower((string) $key);

                    if (! str_contains($label, $search) && ! str_contains($keyLower, $search)) {
                        continue;
                    }
                }

                $groupItems[$key] = $definition;
            }

            if (! empty($groupItems)) {
                $result[$group] = $groupItems;
            }
        }

        return $result;
    }

    public function render(): View
    {
        return view('livewire.admin.roles.role-editor');
    }
}
