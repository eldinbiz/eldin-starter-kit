<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\Company;
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
     * Get the user's role within a specific company.
     */
    public function getCompanyRole(Company $company): string
    {
        $membership = $this->companies()->where('companies.id', $company->id)->first();

        return $membership?->pivot->role ?? 'employee';
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
