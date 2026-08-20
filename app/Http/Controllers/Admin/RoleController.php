<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Roles\CreateRoleAction;
use App\Actions\Roles\DeleteRoleAction;
use App\Actions\Roles\DisableRoleAction;
use App\Actions\Roles\RestoreRoleAction;
use App\Actions\Roles\UpdateRoleAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Roles\StoreRoleRequest;
use App\Http\Requests\Admin\Roles\UpdateRoleRequest;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

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
    public function store(StoreRoleRequest $request, CreateRoleAction $action): RedirectResponse
    {
        /** @var array{name: string, permissions?: array<int, string>|null} $validated */
        $validated = $request->validated();

        $action->execute($validated);

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
    public function update(UpdateRoleRequest $request, Role $role, UpdateRoleAction $action): RedirectResponse
    {
        /** @var array{name: string, permissions?: array<int, string>|null} $validated */
        $validated = $request->validated();

        $action->execute($role, $validated);

        return redirect()->route('admin.roles.index')->with('success', 'Role updated successfully.');
    }

    /**
     * Disable the specified role (set is_active = false).
     * This is the first step before a role can be soft-deleted.
     */
    public function destroy(Role $role, DisableRoleAction $action): RedirectResponse
    {
        try {
            $action->execute($role);
        } catch (InvalidArgumentException $e) {
            return redirect()->route('admin.roles.index')->with('error', $e->getMessage());
        }

        return redirect()->route('admin.roles.index')->with('success', 'Role "'.$role->name.'" has been disabled.');
    }

    /**
     * Enable a previously disabled role (set is_active = true) or restore from trash.
     */
    public function restore(Role $role, RestoreRoleAction $action): RedirectResponse
    {
        $status = $action->execute($role);

        return redirect()->route('admin.roles.index')->with('success', 'Role "'.$role->name.'" has been '.$status.'.');
    }

    /**
     * Soft-delete a disabled role. The role must already be disabled first.
     */
    public function softDelete(Role $role, DeleteRoleAction $action): RedirectResponse
    {
        try {
            $action->execute($role);
        } catch (InvalidArgumentException $e) {
            return redirect()->route('admin.roles.index')->with('error', $e->getMessage());
        }

        return redirect()->route('admin.roles.index')->with('success', 'Role "'.$role->name.'" has been deleted.');
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
