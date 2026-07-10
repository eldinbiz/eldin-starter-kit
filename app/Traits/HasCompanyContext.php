<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\Company;
use App\Models\Role;
use Illuminate\Database\Eloquent\Collection;

trait HasCompanyContext
{
    /**
     * Get the currently active company from the session,
     * falling back to the user's first company.
     */
    public function getActiveCompany(): ?Company
    {
        $activeCompanyId = session('active_company_id');

        if ($activeCompanyId) {
            return $this->companies()->where('companies.id', $activeCompanyId)->first();
        }

        return $this->companies()->first();
    }

    /**
     * Get all roles the user has within a specific company.
     *
     * @return Collection<int, Role>
     */
    public function getCompanyRoles(Company $company): Collection
    {
        return $this->companyRoles()
            ->wherePivot('company_id', $company->id)
            ->get();
    }

    /**
     * Get the user's manager within a specific company.
     */
    public function getManager(Company $company): ?self
    {
        $membership = $this->companies()->where('companies.id', $company->id)->first();

        if ($membership && $membership->pivot->manager_id) {
            /** @var self|null $manager */
            $manager = self::find($membership->pivot->manager_id);

            return $manager;
        }

        return null;
    }

    /**
     * Get users who report to this user within a specific company.
     *
     * @return Collection<int, self>
     */
    public function getSubordinates(Company $company): Collection
    {
        return $company->users()
            ->wherePivot('manager_id', $this->id)
            ->get();
    }
}
