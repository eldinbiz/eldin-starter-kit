<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Roles;

use App\Actions\Roles\DeleteRoleAction;
use App\Actions\Roles\DisableRoleAction;
use App\Actions\Roles\RestoreRoleAction;
use App\Livewire\DataTables\DataTableComponent;
use App\Models\Role;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;

final class RoleTable extends DataTableComponent
{
    #[Url(as: 'sort', history: true, except: 'name')]
    public string $sortField = 'name';

    #[Url(as: 'dir', history: true, except: 'asc')]
    public string $sortDirection = 'asc';

    public ?int $roleToDeleteId = null;

    public ?string $roleToDeleteName = null;

    public int $roleToDeleteUserCount = 0;

    public string $deleteConfirmTypedName = '';

    public ?string $flashSuccess = null;

    public ?string $flashError = null;

    public function mount(): void
    {
        if (request()->has('trashed')) {
            $this->filterValues['trashed'] = request()->boolean('trashed');
        }
    }

    /**
     * Define base Eloquent query builder.
     */
    public function query(): Builder
    {
        $showTrashed = ! empty($this->filterValues['trashed']);

        $query = Role::query()->withCount(['companyUsers as users_count']);

        if ($showTrashed) {
            $query->onlyTrashed();
        }

        return $query;
    }

    protected function applySearch(Builder $query): void
    {
        if ($this->search === '') {
            return;
        }

        $term = $this->search;
        $query->where('name', 'like', "%{$term}%");
    }

    protected function applyFilters(Builder $query): void
    {
        // Status filter: active or disabled (only applied if not viewing trashed)
        if (empty($this->filterValues['trashed']) && ! empty($this->filterValues['status'])) {
            $status = $this->filterValues['status'];
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'disabled') {
                $query->where('is_active', false);
            }
        }
    }

    protected function applySorting(Builder $query): void
    {
        $allowed = ['name', 'users_count', 'is_active', 'created_at', 'id'];

        if (in_array($this->sortField, $allowed, true)) {
            $query->orderBy($this->sortField, $this->sortDirection);
        } else {
            $query->orderBy('name', 'asc');
        }
    }

    /**
     * Toggle status between active and disabled.
     */
    public function toggleStatus(int $roleId, DisableRoleAction $disableAction, RestoreRoleAction $restoreAction): void
    {
        $role = Role::find($roleId);

        if (! $role) {
            return;
        }

        try {
            if ($role->is_active) {
                $disableAction->execute($role);
                $this->flashSuccess = 'Role "'.$role->name.'" has been disabled.';
                $this->flashError = null;
            } else {
                $restoreAction->execute($role);
                $this->flashSuccess = 'Role "'.$role->name.'" has been enabled.';
                $this->flashError = null;
            }
        } catch (InvalidArgumentException $e) {
            $this->flashError = $e->getMessage();
            $this->flashSuccess = null;
        }

        $this->resetPage();
    }

    /**
     * Restore a soft-deleted role.
     */
    public function restoreRole(int $roleId, RestoreRoleAction $action): void
    {
        $role = Role::withTrashed()->find($roleId);

        if (! $role) {
            return;
        }

        $status = $action->execute($role);
        $this->flashSuccess = 'Role "'.$role->name.'" has been '.$status.'.';
        $this->flashError = null;
        $this->resetPage();
    }

    /**
     * Prompt modal delete confirmation for a disabled role.
     */
    public function promptDelete(int $roleId): void
    {
        $role = Role::find($roleId);

        if (! $role || $role->is_system) {
            return;
        }

        $this->roleToDeleteId = $role->id;
        $this->roleToDeleteName = $role->name;
        $this->roleToDeleteUserCount = $role->companyUsers()->count();
        $this->deleteConfirmTypedName = '';
    }

    /**
     * Cancel active delete prompt.
     */
    public function cancelDelete(): void
    {
        $this->roleToDeleteId = null;
        $this->roleToDeleteName = null;
        $this->roleToDeleteUserCount = 0;
        $this->deleteConfirmTypedName = '';
    }

    /**
     * Execute soft-deletion of the role after type-to-confirm verification.
     */
    public function deleteRole(DeleteRoleAction $action): void
    {
        if (! $this->roleToDeleteId) {
            return;
        }

        $role = Role::find($this->roleToDeleteId);

        if (! $role) {
            $this->cancelDelete();

            return;
        }

        try {
            $action->execute($role);
            $this->flashSuccess = 'Role "'.$this->roleToDeleteName.'" has been deleted.';
            $this->flashError = null;
        } catch (InvalidArgumentException $e) {
            $this->flashError = $e->getMessage();
            $this->flashSuccess = null;
        }

        $this->cancelDelete();
        $this->resetPage();
    }

    public function dismissFlash(): void
    {
        $this->flashSuccess = null;
        $this->flashError = null;
    }

    #[Computed]
    public function permissionLabels(): array
    {
        $labels = [];
        foreach (config('permissions.list', []) as $key => $definition) {
            $labels[$key] = $definition['label'] ?? $key;
        }

        return $labels;
    }

    public function render(): View
    {
        return view('livewire.admin.roles.role-table');
    }
}
