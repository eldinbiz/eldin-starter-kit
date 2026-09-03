<?php

use App\Actions\Users\DeleteUserAction;
use App\Livewire\DataTables\WithDataTable;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

new class extends Component
{
    use WithDataTable;

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
        if (! empty($this->filterValues['company_id'])) {
            $companyId = $this->filterValues['company_id'];
            $query->whereHas('companies', function (Builder $sub) use ($companyId): void {
                $sub->where('companies.id', $companyId);
            });
        }

        if (! empty($this->filterValues['roles']) && is_array($this->filterValues['roles'])) {
            $roles = $this->filterValues['roles'];
            $query->whereHas('companyRoles', function (Builder $sub) use ($roles): void {
                $sub->whereIn('name', $roles);
            });
        }

        if (! empty($this->filterValues['date_from'])) {
            $query->whereDate('users.created_at', '>=', $this->filterValues['date_from']);
        }

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

    public function cancelDelete(): void
    {
        $this->userToDeleteId = null;
        $this->userToDeleteName = null;
        $this->deleteConfirmTypedName = '';
    }

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

    #[Computed]
    public function currentUserId(): ?int
    {
        return Auth::id();
    }

    #[Computed]
    public function userCompanyRoleMap(): Collection
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
            'sysadmin', 'platform-admin' => 'bg-purple-50 text-purple-700 dark:bg-purple-500/15 dark:text-purple-400',
            'company-admin', 'admin' => 'bg-[#f2f7ff] text-[#465fff] dark:bg-[#465fff]/10 dark:text-[#7592ff]',
            'manager' => 'bg-orange-50 text-orange-600 dark:bg-orange-500/15 dark:text-orange-500',
            default => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
        };
    }
};
?>

<div x-data="{ filterPanelOpen: $wire.entangle('filterPanelOpen') }">
    {{-- Flash Notifications --}}
    @if ($flashSuccess)
        <div class="mb-4 flex items-center justify-between rounded-lg bg-success-50 p-4 text-sm text-success-700 dark:bg-success-500/10 dark:text-success-400">
            <div class="flex items-center gap-2">
                <svg class="h-5 w-5 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <span>{{ $flashSuccess }}</span>
            </div>
            <button type="button" wire:click="dismissFlash" class="text-success-600 hover:text-success-800 dark:text-success-400 dark:hover:text-success-200 cursor-pointer">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>
    @endif

    @if ($flashError)
        <div class="mb-4 flex items-center justify-between rounded-lg bg-error-50 p-4 text-sm text-error-700 dark:bg-error-500/10 dark:text-error-400">
            <div class="flex items-center gap-2">
                <svg class="h-5 w-5 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
                <span>{{ $flashError }}</span>
            </div>
            <button type="button" wire:click="dismissFlash" class="text-error-600 hover:text-error-800 dark:text-error-400 dark:hover:text-error-200 cursor-pointer">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>
    @endif

    {{-- Top Controls (Search, Filters toggle, Per-page, Create Action) --}}
    @include('livewire.datatables.header', [
        'placeholder' => 'Search users by name or email...',
        'hasFilters' => true,
    ])

    {{-- Collapsible Filter Panel --}}
    <div
        x-show="filterPanelOpen"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-2"
        class="mb-4 rounded-xl border border-gray-200 bg-gray-50/75 p-4 dark:border-gray-800 dark:bg-gray-800/40"
    >
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            {{-- Company Filter (for Platform Admin or multi-company context) --}}
            @if ($this->isPlatformAdmin || $this->availableCompanies->count() > 1)
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        Company
                    </label>
                    <select
                        wire:model.live="filterValues.company_id"
                        class="h-9.5 w-full rounded-lg border border-gray-300 bg-white px-3 text-xs text-gray-700 focus:border-brand-500 focus:outline-hidden focus:ring-1 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                    >
                        <option value="">All Companies</option>
                        @foreach ($this->availableCompanies as $company)
                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            {{-- Roles Multi-Select / Filter --}}
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    Roles
                </label>
                <div class="flex flex-wrap gap-2 pt-0.5">
                    @foreach ($this->availableRoles as $role)
                        <label class="inline-flex items-center gap-1.5 text-xs text-gray-700 dark:text-gray-300 cursor-pointer">
                            <input
                                type="checkbox"
                                value="{{ $role->name }}"
                                wire:model.live="filterValues.roles"
                                class="rounded border-gray-300 text-brand-600 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800"
                            />
                            <span>{{ match($role->name) {
                                'sysadmin' => 'SysAdmin',
                                'company-admin' => 'Company Admin',
                                'platform-admin' => 'Admin',
                                default => ucfirst(str_replace('-', ' ', $role->name))
                            } }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- Date From Filter --}}
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    Joined From
                </label>
                <input
                    type="date"
                    wire:model.live="filterValues.date_from"
                    class="h-9.5 w-full rounded-lg border border-gray-300 bg-white px-3 text-xs text-gray-700 focus:border-brand-500 focus:outline-hidden focus:ring-1 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                />
            </div>

            {{-- Date To Filter --}}
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    Joined To
                </label>
                <input
                    type="date"
                    wire:model.live="filterValues.date_to"
                    class="h-9.5 w-full rounded-lg border border-gray-300 bg-white px-3 text-xs text-gray-700 focus:border-brand-500 focus:outline-hidden focus:ring-1 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                />
            </div>
        </div>

        <div class="mt-4 flex items-center justify-end gap-2 border-t border-gray-200/60 pt-3 dark:border-gray-700/60">
            @if ($this->activeFilterCount() > 0)
                <button
                    type="button"
                    wire:click="resetFilters"
                    class="rounded-lg px-3 py-1.5 text-xs font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 cursor-pointer"
                >
                    Reset Filters
                </button>
            @endif
            <button
                type="button"
                @click="filterPanelOpen = false"
                class="rounded-lg bg-gray-200 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 cursor-pointer"
            >
                Close
            </button>
        </div>
    </div>

    {{-- Active Filter Chips --}}
    @include('livewire.datatables.active-chips', [
        'filterLabels' => [
            'company_id' => 'Company',
            'roles' => 'Role',
            'date_from' => 'Joined From',
            'date_to' => 'Joined To',
        ],
    ])

    {{-- Livewire Loading Skeleton --}}
    <div wire:loading.delay.shortest wire:target="search, sortBy, gotoPage, nextPage, previousPage, perPage, filterValues, resetFilters, removeFilter" class="w-full">
        @include('livewire.datatables.loading', ['columns' => 4, 'rows' => 5])
    </div>

    {{-- Table Content (Target for wire:loading removal) --}}
    <div wire:loading.remove.delay.shortest wire:target="search, sortBy, gotoPage, nextPage, previousPage, perPage, filterValues, resetFilters, removeFilter">
        @if ($this->rows->isNotEmpty())
            {{-- Desktop Table View --}}
            <div class="hidden sm:block overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200 dark:bg-gray-800/60 dark:border-gray-800">
                        <tr>
                            @include('livewire.datatables.th', ['field' => 'name', 'label' => 'User', 'sortable' => true])
                            @include('livewire.datatables.th', ['field' => '', 'label' => 'Company & Role', 'sortable' => false])
                            @include('livewire.datatables.th', ['field' => 'created_at', 'label' => 'Joined Date', 'sortable' => true])
                            @include('livewire.datatables.th', ['field' => '', 'label' => 'Actions', 'sortable' => false, 'align' => 'right'])
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800 bg-white dark:bg-transparent">
                        @foreach ($this->rows as $user)
                            <tr class="hover:bg-gray-50/75 dark:hover:bg-white/[0.02] transition-colors">
                                {{-- User Column --}}
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <span class="h-9 w-9 rounded-full bg-brand-50 flex items-center justify-center text-brand-600 font-bold text-xs dark:bg-brand-500/10 dark:text-brand-400 shrink-0">
                                            {{ strtoupper(substr($user->name, 0, 2)) }}
                                        </span>
                                        <div class="min-w-0">
                                            <p class="font-medium text-gray-800 text-theme-sm dark:text-white/90 truncate">
                                                {{ $user->name }}
                                            </p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                                {{ $user->email }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                {{-- Company & Role Column --}}
                                <td class="py-3.5 px-4">
                                    @if (isset($this->userCompanyRoleMap[$user->id]) && $this->userCompanyRoleMap[$user->id]->isNotEmpty())
                                        @foreach ($this->userCompanyRoleMap[$user->id] as $companyData)
                                            <div class="{{ ! $loop->first ? 'mt-2' : '' }}">
                                                <div class="flex flex-wrap items-center gap-1.5">
                                                    @foreach ($companyData['roles'] as $role)
                                                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium {{ $this->roleColorClasses($role->name) }}">
                                                            {{ match($role->name) {
                                                                'sysadmin' => 'SysAdmin',
                                                                'company-admin' => 'Company Admin',
                                                                'platform-admin' => 'Admin',
                                                                default => ucfirst(str_replace('-', ' ', $role->name))
                                                            } }}
                                                        </span>
                                                    @endforeach
                                                    @if ($companyData['company_name'])
                                                        <span class="inline-flex items-center gap-1 rounded-full bg-purple-50 px-2 py-0.5 text-xs font-medium text-purple-600 dark:bg-purple-500/15 dark:text-purple-400">
                                                            {{ $companyData['company_name'] }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    @else
                                        <span class="text-gray-400 text-theme-sm dark:text-gray-500">&mdash;</span>
                                    @endif
                                </td>

                                {{-- Joined Date --}}
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <p class="text-gray-500 text-theme-sm dark:text-gray-400">
                                        {{ $user->created_at ? $user->created_at->format('M d, Y') : '&mdash;' }}
                                    </p>
                                </td>

                                {{-- Actions --}}
                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        {{-- Impersonate --}}
                                        @if ($this->canImpersonate && $user->id !== $this->currentUserId)
                                            <a
                                                href="{{ route('impersonate', $user->id) }}"
                                                title="Impersonate {{ $user->name }}"
                                                class="text-gray-500 hover:text-amber-500 dark:text-gray-400 dark:hover:text-amber-400 transition"
                                            >
                                                <svg class="fill-current" width="18" height="18" viewBox="0 0 24 24">
                                                    <path d="M12 2a5 5 0 0 0-5 5v3H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2h-1V7a5 5 0 0 0-5-5zm-3 5a3 3 0 0 1 6 0v3H9V7zm3 7a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3z"/>
                                                </svg>
                                            </a>
                                        @endif

                                        {{-- Edit User Link with wire:navigate --}}
                                        <a
                                            href="{{ route('admin.users.edit', $user->id) }}"
                                            wire:navigate
                                            title="Edit user"
                                            class="text-gray-500 hover:text-brand-500 dark:text-gray-400 dark:hover:text-white transition"
                                        >
                                            <svg class="fill-current" width="18" height="18" viewBox="0 0 24 24" fill="none">
                                                <path d="M5 19h1.42l9.4-9.4-1.42-1.42L5 17.58V19zm-1 1v-3.08L15.42 5.5a1 1 0 0 1 1.42 0l1.66 1.66a1 1 0 0 1 0 1.42L6.08 20H4zm13-13.58l1.42-1.42-1.42-1.42-1.42 1.42 1.42 1.42z"/>
                                            </svg>
                                        </a>

                                        {{-- Delete Prompt Trigger --}}
                                        @if ($user->id !== $this->currentUserId)
                                            <button
                                                type="button"
                                                wire:click="promptDelete({{ $user->id }})"
                                                title="Delete user"
                                                class="text-gray-500 hover:text-error-500 dark:text-gray-400 dark:hover:text-error-400 cursor-pointer transition"
                                            >
                                                <svg class="fill-current" width="18" height="18" viewBox="0 0 24 24" fill="none">
                                                    <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
                                                </svg>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Mobile Cards Grid View --}}
            <div class="grid grid-cols-1 gap-4 sm:hidden">
                @foreach ($this->rows as $user)
                    <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
                        {{-- User Info --}}
                        <div class="flex items-center gap-3 mb-3">
                            <span class="h-10 w-10 rounded-full bg-brand-50 flex items-center justify-center text-brand-600 font-bold text-xs dark:bg-brand-500/10 dark:text-brand-400 shrink-0">
                                {{ strtoupper(substr($user->name, 0, 2)) }}
                            </span>
                            <div class="min-w-0">
                                <p class="font-medium text-gray-800 text-theme-sm dark:text-white/90 truncate">{{ $user->name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $user->email }}</p>
                            </div>
                        </div>

                        {{-- Roles & Companies --}}
                        <div class="flex flex-wrap items-center gap-1.5 mb-3">
                            @if (isset($this->userCompanyRoleMap[$user->id]) && $this->userCompanyRoleMap[$user->id]->isNotEmpty())
                                @foreach ($this->userCompanyRoleMap[$user->id] as $companyData)
                                    @foreach ($companyData['roles'] as $role)
                                        <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium {{ $this->roleColorClasses($role->name) }}">
                                            {{ match($role->name) {
                                                'sysadmin' => 'SysAdmin',
                                                'company-admin' => 'Company Admin',
                                                'platform-admin' => 'Admin',
                                                default => ucfirst(str_replace('-', ' ', $role->name))
                                            } }}
                                        </span>
                                    @endforeach
                                    @if ($companyData['company_name'])
                                        <span class="inline-flex items-center gap-1 rounded-full bg-purple-50 px-2 py-0.5 text-xs font-medium text-purple-600 dark:bg-purple-500/15 dark:text-purple-400">
                                            {{ $companyData['company_name'] }}
                                        </span>
                                    @endif
                                @endforeach
                            @else
                                <span class="text-xs text-gray-400 dark:text-gray-500">&mdash;</span>
                            @endif
                        </div>

                        {{-- Footer: Joined Date + Actions --}}
                        <div class="flex items-center justify-between pt-3 border-t border-gray-100 dark:border-gray-800">
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                Joined {{ $user->created_at ? $user->created_at->format('M d, Y') : '&mdash;' }}
                            </span>
                            <div class="flex items-center gap-2">
                                @if ($this->canImpersonate && $user->id !== $this->currentUserId)
                                    <a
                                        href="{{ route('impersonate', $user->id) }}"
                                        title="Impersonate {{ $user->name }}"
                                        class="text-gray-500 hover:text-amber-500 dark:text-gray-400 dark:hover:text-amber-400 transition"
                                    >
                                        <svg class="fill-current" width="18" height="18" viewBox="0 0 24 24">
                                            <path d="M12 2a5 5 0 0 0-5 5v3H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2h-1V7a5 5 0 0 0-5-5zm-3 5a3 3 0 0 1 6 0v3H9V7zm3 7a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3z"/>
                                        </svg>
                                    </a>
                                @endif

                                <a
                                    href="{{ route('admin.users.edit', $user->id) }}"
                                    wire:navigate
                                    class="text-gray-500 hover:text-brand-500 dark:text-gray-400 dark:hover:text-white transition"
                                >
                                    <svg class="fill-current" width="18" height="18" viewBox="0 0 24 24" fill="none">
                                        <path d="M5 19h1.42l9.4-9.4-1.42-1.42L5 17.58V19zm-1 1v-3.08L15.42 5.5a1 1 0 0 1 1.42 0l1.66 1.66a1 1 0 0 1 0 1.42L6.08 20H4zm13-13.58l1.42-1.42-1.42-1.42-1.42 1.42 1.42 1.42z"/>
                                    </svg>
                                </a>

                                @if ($user->id !== $this->currentUserId)
                                    <button
                                        type="button"
                                        wire:click="promptDelete({{ $user->id }})"
                                        class="text-gray-500 hover:text-error-500 dark:text-gray-400 dark:hover:text-error-400 cursor-pointer transition"
                                    >
                                        <svg class="fill-current" width="18" height="18" viewBox="0 0 24 24" fill="none">
                                            <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
                                        </svg>
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Pagination Footer --}}
            @include('livewire.datatables.pagination', ['paginator' => $this->rows])
        @else
            {{-- Empty State --}}
            @include('livewire.datatables.empty', [
                'title' => 'No users found',
                'message' => 'No user records matched your search query or filter selection.',
            ])
        @endif
    </div>

    {{-- Delete User Confirmation Modal --}}
    @if ($userToDeleteId)
        <div
            class="fixed inset-0 z-99999 flex items-center justify-center bg-black/50 backdrop-blur-sm"
            x-data
            x-trap.noscroll="true"
        >
            <div
                @click.outside="$wire.cancelDelete()"
                class="mx-4 w-full max-w-md rounded-2xl border border-gray-200 bg-white p-6 shadow-xl dark:border-gray-700 dark:bg-gray-900"
            >
                {{-- Warning Icon --}}
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-error-50 dark:bg-error-500/10 mb-4">
                    <svg class="h-7 w-7 text-error-600 dark:text-error-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                </div>

                {{-- Header text --}}
                <h3 class="text-center text-lg font-semibold text-gray-800 dark:text-white/90 mb-2">
                    Delete User?
                </h3>

                {{-- Description --}}
                <p class="text-center text-sm text-gray-500 dark:text-gray-400 mb-6">
                    Are you sure you want to permanently delete user <strong>{{ $userToDeleteName }}</strong>? This action cannot be undone.
                </p>

                {{-- Action Buttons --}}
                <div class="flex items-center justify-end gap-3">
                    <button
                        type="button"
                        wire:click="cancelDelete"
                        class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700/50 cursor-pointer transition"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        wire:click="deleteUser"
                        class="inline-flex items-center justify-center rounded-lg bg-error-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-error-700 shadow-theme-xs transition cursor-pointer"
                    >
                        Delete User
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
