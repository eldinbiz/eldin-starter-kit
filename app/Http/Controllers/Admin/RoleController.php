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
    public function index(): View
    {
        $roles = Role::withCount('users')->get();
        $permissionLabels = $this->getPermissionLabels();

        return view('admin.roles.index', compact('roles', 'permissionLabels'));
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

        Role::create([
            'name' => $validated['name'],
            'permissions' => $validated['permissions'] ?? [],
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

        $role->update([
            'name' => $validated['name'],
            'permissions' => $validated['permissions'] ?? [],
        ]);

        return redirect()->route('admin.roles.index')->with('success', 'Role updated successfully.');
    }

    /**
     * Remove the specified role from storage.
     */
    public function destroy(Role $role): RedirectResponse
    {
        // Prevent deletion of roles with assigned users
        if ($role->users()->count() > 0) {
            return redirect()->route('admin.roles.index')->with(
                'error',
                'Cannot delete role "' . $role->name . '" because it has ' . $role->users()->count() . ' user(s) assigned.'
            );
        }

        $role->delete();

        return redirect()->route('admin.roles.index')->with('success', 'Role deleted successfully.');
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
