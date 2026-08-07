<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\Company;
use App\Models\Role;
use Illuminate\Database\Eloquent\Collection;

trait HasCompanyContext
{
    /**
     * Cached active company instance for the current request.
     */
    private ?Company $cachedActiveCompany = null;

    /**
     * Get the currently active company from the session,
     * falling back to the user's first company.
     *
     * Memoized per request to prevent duplicate database queries.
     */
    public function getActiveCompany(): ?Company
    {
        if ($this->cachedActiveCompany !== null) {
            return $this->cachedActiveCompany;
        }

        $activeCompanyId = session('active_company_id');

        if ($activeCompanyId) {
            $company = $this->companies()->where('companies.id', $activeCompanyId)->first();

            if ($company) {
                return $this->cachedActiveCompany = $company;
            }
        }

        $fallbackCompany = $this->companies()->first();

        if ($fallbackCompany) {
            session(['active_company_id' => $fallbackCompany->id]);
        }

        return $this->cachedActiveCompany = $fallbackCompany;
    }

    /**
     * Get all roles the user has within a specific company.
     *
     * @return Collection<int, Role>
     */
    public function getCompanyRoles(?Company $company = null): Collection
    {
        $company ??= $this->getActiveCompany();

        if (! $company) {
            return new Collection;
        }

        return $this->companyRoles()
            ->wherePivot('company_id', $company->id)
            ->get();
    }

    /**
     * Get the user's manager within a specific company.
     */
    public function getManager(?Company $company = null): ?self
    {
        $company ??= $this->getActiveCompany();

        if (! $company) {
            return null;
        }

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
    public function getSubordinates(?Company $company = null): Collection
    {
        $company ??= $this->getActiveCompany();

        if (! $company) {
            return new Collection;
        }

        return $company->users()
            ->wherePivot('manager_id', $this->id)
            ->get();
    }

    /**
     * Check if user belongs to a given company.
     */
    public function belongsToCompany(Company|int $company): bool
    {
        $id = $company instanceof Company ? $company->id : $company;

        return $this->companies()->where('companies.id', $id)->exists();
    }
}
