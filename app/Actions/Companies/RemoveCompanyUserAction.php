<?php

declare(strict_types=1);

namespace App\Actions\Companies;

use App\Models\Company;
use Illuminate\Support\Facades\DB;

final class RemoveCompanyUserAction
{
    /**
     * Remove a user from a company, reassigning direct reports to the company level.
     *
     * @return int Number of affected direct reports
     */
    public function execute(Company $company, int $userId): int
    {
        return DB::transaction(function () use ($company, $userId): int {
            // Reassign direct reports to the company level before removing the user
            $affectedCount = $company->users()
                ->wherePivot('manager_id', $userId)
                ->newPivotStatement()
                ->where('company_id', $company->id)
                ->update(['manager_id' => null]);

            $company->users()->detach($userId);

            return (int) $affectedCount;
        });
    }
}
