<?php

declare(strict_types=1);

namespace App\Actions\Companies;

use App\Models\Company;
use App\Models\User;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class SwitchActiveCompanyAction
{
    /**
     * Switch the user's active company context.
     *
     * @throws HttpException
     */
    public function execute(User $user, int $companyId): Company
    {
        if (! $user->belongsToCompany($companyId)) {
            abort(403, 'You do not have access to this company.');
        }

        /** @var Company $company */
        $company = Company::findOrFail($companyId);

        session(['active_company_id' => $company->id]);

        // Auto-select and synchronize the active role for the new company
        $activeRole = $user->getActiveRole($company);
        if ($activeRole) {
            session(["active_role.{$company->id}" => $activeRole->name]);
        }

        return $company;
    }
}
