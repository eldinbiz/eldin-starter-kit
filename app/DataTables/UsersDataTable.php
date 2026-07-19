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
            return User::query()->with(['companyRoles', 'companies']);
        }

        if ($activeCompany) {
            return $activeCompany->users()->with(['companyRoles', 'companies']);
        }

        return User::query()->with(['companyRoles', 'companies']);
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
                ->apply(function (Builder $q, int $value): void {
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

    public function renderRow(mixed $row): string
    {
        /** @var User $row */
        return view('admin.users._row', [
            'user' => $row,
            'userCompanyRoleMap' => $this->getUserCompanyRoleMap(),
            'roleColorClasses' => fn (string $roleName): string => $this->roleColorClasses($roleName),
        ])->render();
    }

    public function renderMobileRow(mixed $row): string
    {
        /** @var User $row */
        return view('admin.users._mobile_row', [
            'user' => $row,
            'userCompanyRoleMap' => $this->getUserCompanyRoleMap(),
            'roleColorClasses' => fn (string $roleName): string => $this->roleColorClasses($roleName),
        ])->render();
    }

    public function handle(\Illuminate\Http\Request $request): static
    {
        parent::handle($request);

        $this->buildUserCompanyRoleMap();

        return $this;
    }

    public function getUserCompanyRoleMap(): Collection
    {
        return $this->userCompanyRoleMap ?? collect();
    }

    /**
     * @return array<string, string>
     */
    public function roleColorClasses(string $roleName): string
    {
        return match ($roleName) {
            'admin', 'platform-admin' => 'bg-[#f2f7ff] text-[#465fff] dark:bg-[#465fff]/10 dark:text-[#7592ff]',
            'manager' => 'bg-orange-50 text-orange-600 dark:bg-orange-500/15 dark:text-orange-500',
            default => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
        };
    }

    private function buildUserCompanyRoleMap(): void
    {
        $this->userCompanyRoleMap = $this->results->getCollection()->mapWithKeys(function (User $user) {
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
