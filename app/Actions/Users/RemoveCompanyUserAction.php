<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class RemoveCompanyUserAction
{
    /**
     * Remove a user from a company, reassigning direct reports and detaching company-scoped roles.
     *
     * @return int Number of direct reports reassigned
     */
    public function execute(User $user, Company $company): int
    {
        return DB::transaction(function () use ($user, $company): int {
            // Reassign direct reports before removing the user
            $affectedCount = $company->users()
                ->wherePivot('manager_id', $user->id)
                ->newPivotStatement()
                ->where('company_id', $company->id)
                ->update(['manager_id' => null]);

            // Detach company-scoped roles
            $user->companyRoles()
                ->wherePivot('company_id', $company->id)
                ->detach();

            // Detach from company
            $user->companies()->detach($company->id);

            return (int) $affectedCount;
        });
    }
}
