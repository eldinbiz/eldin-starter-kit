<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RoleController extends Controller
{
    /**
     * Display a listing of the roles.
     */
    public function index(Request $request): View
    {
        $showTrashed = $request->boolean('trashed');

        $roles = Role::query()
            ->when($showTrashed, fn ($q) => $q->onlyTrashed())
            ->withCount(['companyUsers as users_count'])
            ->get();

        $permissionLabels = $this->getPermissionLabels();

        return view('admin.roles.index', compact('roles', 'permissionLabels', 'showTrashed'));
    }

    /**
     * Show the form for creating a new role.
     */
    public function create(): View
    {
        $permissions = config('permissions.list', []);
        $permissionGroups = config('permissions.groups', []);

        return view('admin.roles.create', compact('permissions', 'permissionGroups'));
    }

    /**
     * Store a newly created role in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string'],
        ]);

        $permissions = $this->mergeAlwaysEnabledPermissions($validated['permissions'] ?? []);

        Role::create([
            'name' => $validated['name'],
            'permissions' => $permissions,
        ]);

        return redirect()->route('admin.roles.index')->with('success', 'Role created successfully.');
    }

    /**
     * Show the form for editing the specified role.
     */
    public function edit(Role $role): View
    {
        $permissions = config('permissions.list', []);
        $permissionGroups = config('permissions.groups', []);
        $assignedPermissions = $role->permissions ?? [];

        return view('admin.roles.edit', compact('role', 'permissions', 'permissionGroups', 'assignedPermissions'));
    }

    /**
     * Update the specified role in storage.
     */
    public function update(Request $request, Role $role): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles')->ignore($role->id)],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string'],
        ]);

        $permissions = $this->mergeAlwaysEnabledPermissions($validated['permissions'] ?? []);

        $role->update([
            'name' => $validated['name'],
            'permissions' => $permissions,
        ]);

        return redirect()->route('admin.roles.index')->with('success', 'Role updated successfully.');
    }

    /**
     * Disable the specified role (set is_active = false).
     * This is the first step before a role can be soft-deleted.
     */
    public function destroy(Role $role): RedirectResponse
    {
        if ($role->name === 'user') {
            return redirect()->route('admin.roles.index')->with(
                'error',
                'The "user" role cannot be disabled.',
            );
        }

        $role->update(['is_active' => false]);

        return redirect()->route('admin.roles.index')->with('success', 'Role "'.$role->name.'" has been disabled.');
    }

    /**
     * Enable a previously disabled role (set is_active = true).
     */
    public function restore(Role $role): RedirectResponse
    {
        if ($role->trashed()) {
            // Restore from soft-delete
            $role->restore();
            $role->update(['is_active' => true]);

            return redirect()->route('admin.roles.index')->with('success', 'Role "'.$role->name.'" has been restored.');
        }

        $role->update(['is_active' => true]);

        return redirect()->route('admin.roles.index')->with('success', 'Role "'.$role->name.'" has been enabled.');
    }

    /**
     * Soft-delete a disabled role. The role must already be disabled first.
     */
    public function softDelete(Role $role): RedirectResponse
    {
        if ($role->name === 'user') {
            return redirect()->route('admin.roles.index')->with(
                'error',
                'The "user" role cannot be deleted.',
            );
        }

        if ($role->is_active) {
            return redirect()->route('admin.roles.index')->with(
                'error',
                'Role "'.$role->name.'" must be disabled before it can be deleted.',
            );
        }

        $role->delete();

        return redirect()->route('admin.roles.index')->with('success', 'Role "'.$role->name.'" has been deleted.');
    }

    /**
     * Merge any always-enabled permissions into the given permissions array.
     *
     * @param  array<string>  $permissions
     * @return array<string>
     */
    private function mergeAlwaysEnabledPermissions(array $permissions): array
    {
        foreach (config('permissions.list', []) as $key => $definition) {
            if (($definition['always_enabled'] ?? false) && ! in_array($key, $permissions, true)) {
                $permissions[] = $key;
            }
        }

        return $permissions;
    }

    /**
     * Build a lookup of permission keys to labels for display.
     *
     * @return array<string, string>
     */
    private function getPermissionLabels(): array
    {
        $labels = [];

        foreach (config('permissions.list', []) as $key => $definition) {
            $labels[$key] = $definition['label'] ?? $key;
        }

        return $labels;
    }
}
