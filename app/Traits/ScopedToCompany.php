<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait ScopedToCompany
{
    /**
     * Scope a query to records belonging to the currently active company.
     */
    public function scopeForActiveCompany(Builder $query): Builder
    {
        $activeCompanyId = session('active_company_id');

        if ($activeCompanyId) {
            return $query->where('company_id', $activeCompanyId);
        }

        return $query;
    }
}
