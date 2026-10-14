<?php

declare(strict_types=1);

namespace App\Actions\OrgChart;

use App\Models\Company;
use App\Models\User;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

final class GetOrgChartTreeAction
{
    /**
     * Optional testing hook to override tree dataset without subclassing final action.
     *
     * @var (Closure(Company): array{company: array{id: int, name: string}, users: Collection<int, array<string, mixed>>})|null
     */
    public static ?Closure $fakeResolver = null;

    /**
     * Build the org chart tree dataset for the given company, cleaning dangling references.
     *
     * @return array{
     *     company: array{id: int, name: string},
     *     users: Collection<int, array<string, mixed>>
     * }
     */
    public function execute(Company $company): array
    {
        if (self::$fakeResolver !== null) {
            return (self::$fakeResolver)($company);
        }

        $allUsers = $company->users()
            ->get()
            ->map(function (User $u): array {
                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'initials' => strtoupper(substr($u->name, 0, 1)),
                    'job_title' => $u->pivot->job_title ?? 'No Title',
                    'status' => $u->pivot->status ?? 'active',
                    'manager_id' => $u->pivot->manager_id,
                ];
            });

        // Defensive: strip manager_id references to users not in this company
        $userIds = $allUsers->pluck('id');
        $allUsers = $allUsers->map(function (array $u) use ($userIds, $company): array {
            if ($u['manager_id'] !== null && ! $userIds->contains($u['manager_id'])) {
                Log::warning('Org chart: dangling manager reference detected', [
                    'company_id' => $company->id,
                    'user_id' => $u['id'],
                    'missing_manager_id' => $u['manager_id'],
                ]);
                $u['manager_id'] = null;
            }

            return $u;
        });

        return [
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
            ],
            'users' => $allUsers,
        ];
    }
}
