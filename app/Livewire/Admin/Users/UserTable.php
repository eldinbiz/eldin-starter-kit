<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Users;

use App\Actions\Users\DeleteUserAction;
use App\Livewire\DataTables\DataTableComponent;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;

final class UserTable extends DataTableComponent
{
    #[Url(as: 'sort', history: true, except: 'name')]
    public string $sortField = 'name';

    #[Url(as: 'dir', history: true, except: 'asc')]
    public string $sortDirection = 'asc';

    public ?int $userToDeleteId = null;

    public ?string $userToDeleteName = null;

    public string $deleteConfirmTypedName = '';

    public ?string $flashSuccess = null;

    public ?string $flashError = null;

    public function mount(): void
    {
        $this->ensureRolesFilterIsArray();
    }

    public function resetFilters(): void
    {
        parent::resetFilters();
        $this->ensureRolesFilterIsArray();
    }

    public function removeFilter(string $key): void
    {
        parent::removeFilter($key);
        if ($key === 'roles') {
            $this->ensureRolesFilterIsArray();
        }
    }

    public function updatedFilterValues(): void
    {
        $this->ensureRolesFilterIsArray();
        parent::updatedFilterValues();
    }

    private function ensureRolesFilterIsArray(): void
    {
        if (! isset($this->filterValues['roles']) || ! is_array($this->filterValues['roles'])) {
            $this->filterValues['roles'] = is_string($this->filterValues['roles'] ?? null)
                ? [$this->filterValues['roles']]
                : [];
        }
    }

    /**
     * Define the base Eloquent query builder.
     */
    public function query(): Builder
    {
        /** @var User|null $authUser */
        $authUser = Auth::user();
        $isPlatformAdmin = $authUser?->hasPlatformAccess() ?? false;
        $activeCompany = $authUser?->getActiveCompany();

        if ($isPlatformAdmin) {
            return User::human()->with(['companyRoles', 'companies']);
        }

        if ($activeCompany) {
            return User::human()
                ->whereHas('companies', function (Builder $q) use ($activeCompany): void {
                    $q->where('companies.id', $activeCompany->id);
                })
                ->with(['companyRoles', 'companies']);
        }

        return User::human()->with(['companyRoles', 'companies']);
    }

    protected function applySearch(Builder $query): void
    {
        if ($this->search === '') {
            return;
        }

        $term = $this->search;
        $query->where(function (Builder $sub) use ($term): void {
            $sub->where('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%");
        });
    }

    protected function applyFilters(Builder $query): void
    {
        // Filter by specific Company
        if (! empty($this->filterValues['company_id'])) {
            $companyId = $this->filterValues['company_id'];
            $query->whereHas('companies', function (Builder $sub) use ($companyId): void {
                $sub->where('companies.id', $companyId);
            });
        }

        // Filter by Roles
        if (! empty($this->filterValues['roles']) && is_array($this->filterValues['roles'])) {
            $roles = $this->filterValues['roles'];
            $query->whereHas('companyRoles', function (Builder $sub) use ($roles): void {
                $sub->whereIn('name', $roles);
            });
        }

        // Filter by Date From
        if (! empty($this->filterValues['date_from'])) {
            $query->whereDate('users.created_at', '>=', $this->filterValues['date_from']);
        }

        // Filter by Date To
        if (! empty($this->filterValues['date_to'])) {
            $query->whereDate('users.created_at', '<=', $this->filterValues['date_to']);
        }
    }

    protected function applySorting(Builder $query): void
    {
        $allowed = ['name', 'email', 'created_at', 'id'];

        if (in_array($this->sortField, $allowed, true)) {
            $query->orderBy($this->sortField, $this->sortDirection);
        } else {
            $query->orderBy('name', 'asc');
        }
    }

    /**
     * Trigger modal confirmation for deleting a user.
     */
    public function promptDelete(int $userId): void
    {
        $user = User::find($userId);

        if (! $user) {
            return;
        }

        $this->userToDeleteId = $user->id;
        $this->userToDeleteName = $user->name;
        $this->deleteConfirmTypedName = '';
    }

    /**
     * Cancel active delete prompt.
     */
    public function cancelDelete(): void
    {
        $this->userToDeleteId = null;
        $this->userToDeleteName = null;
        $this->deleteConfirmTypedName = '';
    }

    /**
     * Execute deletion of the prompted user using 3-layer Action.
     */
    public function deleteUser(DeleteUserAction $action): void
    {
        if (! $this->userToDeleteId) {
            return;
        }

        $user = User::find($this->userToDeleteId);

        if (! $user) {
            $this->cancelDelete();

            return;
        }

        try {
            $action->execute($user, (int) Auth::id());
            $this->flashSuccess = 'User "'.$this->userToDeleteName.'" deleted successfully.';
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
    public function availableCompanies(): Collection
    {
        /** @var User|null $authUser */
        $authUser = Auth::user();
        if ($authUser?->hasPlatformAccess()) {
            return Company::where('is_active', true)->orderBy('name')->get();
        }

        $active = $authUser?->getActiveCompany();

        return $active ? collect([$active]) : collect();
    }

    #[Computed]
    public function availableRoles(): Collection
    {
        return Role::where('is_active', true)->orderBy('name')->get();
    }

    #[Computed]
    public function canImpersonate(): bool
    {
        /** @var User|null $authUser */
        $authUser = Auth::user();

        return $authUser?->canImpersonate() ?? false;
    }

    #[Computed]
    public function isPlatformAdmin(): bool
    {
        /** @var User|null $authUser */
        $authUser = Auth::user();

        return $authUser?->hasPlatformAccess() ?? false;
    }

    /**
     * Map grouped company-roles for current page rows.
     */
    public function getUserCompanyRoleMap(): Collection
    {
        return $this->rows->getCollection()->mapWithKeys(function (User $user): array {
            $grouped = $user->companyRoles->groupBy('pivot.company_id');

            $mapped = $grouped->map(function ($roles) use ($user): array {
                $company = $user->companies->firstWhere('id', $roles->first()->pivot->company_id);

                return [
                    'company_name' => $company?->name,
                    'roles' => $roles,
                ];
            });

            return [$user->id => $mapped];
        });
    }

    public function roleColorClasses(string $roleName): string
    {
        return match ($roleName) {
            'sysadmin' => 'bg-purple-50 text-purple-700 dark:bg-purple-500/15 dark:text-purple-400',
            'company-admin', 'admin' => 'bg-[#f2f7ff] text-[#465fff] dark:bg-[#465fff]/10 dark:text-[#7592ff]',
            'manager' => 'bg-orange-50 text-orange-600 dark:bg-orange-500/15 dark:text-orange-500',
            default => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
        };
    }

    public function render(): View
    {
        return view('livewire.admin.users.user-table', [
            'userCompanyRoleMap' => $this->getUserCompanyRoleMap(),
            'currentUserId' => Auth::id(),
        ]);
    }
}
