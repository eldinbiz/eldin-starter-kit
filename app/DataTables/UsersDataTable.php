<?php

declare(strict_types=1);

namespace App\DataTables;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class UsersDataTable extends DataTable
{
    protected Collection $userCompanyRoleMap;

    public function query(): Builder
    {
        $authUser = auth()->user();
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

    public function columns(): array
    {
        return [
            Column::make('name')
                ->label('User')
                ->sortable()
                ->searchable(),
            Column::make('company_roles')
                ->label('Company & Role'),
            Column::make('created_at')
                ->label('Joined Date')
                ->sortable(),
            Column::make('actions')
                ->label('Actions')
                ->align('right'),
        ];
    }

    public function filters(): array
    {
        $authUser = auth()->user();
        $isPlatformAdmin = $authUser?->hasPlatformAccess() ?? false;
        $activeCompany = $authUser?->getActiveCompany();

        return [
            Filter::make('search')
                ->type('text')
                ->label('Name or Email')
                ->placeholder('Search users...')
                ->apply(function (Builder $q, string $value): void {
                    $q->where(function (Builder $sub) use ($value): void {
                        $sub->where('name', 'like', "%{$value}%")
                            ->orWhere('email', 'like', "%{$value}%");
                    });
                }),

            Filter::make('filter_roles')
                ->type('checkbox')
                ->label('Role')
                ->options(fn (): array => Role::all()->pluck('name', 'name')->toArray())
                ->apply(function (Builder $q, array $values): void {
                    $q->whereHas('companyRoles', function (Builder $sub) use ($values): void {
                        $sub->whereIn('name', $values);
                    });
                }),

            Filter::make('filter_company_id')
                ->type('select')
                ->label('Company')
                ->options(function () use ($isPlatformAdmin, $activeCompany): array {
                    if ($isPlatformAdmin) {
                        return Company::where('is_active', true)
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray();
                    }

                    if ($activeCompany) {
                        return [$activeCompany->id => $activeCompany->name];
                    }

                    return [];
                })
                ->apply(function (Builder $q, int|string $value): void {
                    $q->whereHas('companies', function (Builder $sub) use ($value): void {
                        $sub->where('id', $value);
                    });
                })
                ->when(fn (): bool => $isPlatformAdmin || ($activeCompany === null)),

            Filter::make('filter_date_from')
                ->type('date')
                ->label('Joined From')
                ->apply(function (Builder $q, string $value): void {
                    $q->whereDate('users.created_at', '>=', $value);
                }),

            Filter::make('filter_date_to')
                ->type('date')
                ->label('Joined To')
                ->apply(function (Builder $q, string $value): void {
                    $q->whereDate('users.created_at', '<=', $value);
                }),
        ];
    }

    public function bodyView(): string
    {
        return 'admin.users._tbody';
    }

    public function mobileBodyView(): string
    {
        return 'admin.users._mobile_body';
    }

    public function rowView(): string
    {
        return 'admin.users._row';
    }

    public function mobileRowView(): string
    {
        return 'admin.users._mobile_row';
    }

    public function rowViewData(mixed $row): array
    {
        return [
            'user' => $row,
        ];
    }

    private ?array $cachedSharedViewData = null;

    public function sharedViewData(): array
    {
        if ($this->cachedSharedViewData !== null) {
            return $this->cachedSharedViewData;
        }

        return $this->cachedSharedViewData = [
            'userCompanyRoleMap' => $this->getUserCompanyRoleMap(),
            'canImpersonate' => auth()->user()?->canImpersonate() ?? false,
            'authUserId' => auth()->id(),
            'roleColorClasses' => fn (string $roleName): string => $this->roleColorClasses($roleName),
        ];
    }

    public function getUserCompanyRoleMap(): Collection
    {
        if (! isset($this->userCompanyRoleMap)) {
            $this->buildUserCompanyRoleMap();
        }

        return $this->userCompanyRoleMap;
    }

    /**
     * @return array<string, string>
     */
    public function roleColorClasses(string $roleName): string
    {
        return match ($roleName) {
            'sysadmin', 'platform-admin' => 'bg-purple-50 text-purple-700 dark:bg-purple-500/15 dark:text-purple-400',
            'company-admin', 'admin' => 'bg-[#f2f7ff] text-[#465fff] dark:bg-[#465fff]/10 dark:text-[#7592ff]',
            'manager' => 'bg-orange-50 text-orange-600 dark:bg-orange-500/15 dark:text-orange-500',
            default => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
        };
    }

    private function buildUserCompanyRoleMap(): void
    {
        $this->userCompanyRoleMap = $this->results()->getCollection()->mapWithKeys(function (User $user) {
            $grouped = $user->companyRoles->groupBy('pivot.company_id');

            $mapped = $grouped->map(function ($roles) use ($user) {
                $company = $user->companies->firstWhere('id', $roles->first()->pivot->company_id);

                return [
                    'company_name' => $company?->name,
                    'roles' => $roles,
                ];
            });

            return [$user->id => $mapped];
        });
    }
}
