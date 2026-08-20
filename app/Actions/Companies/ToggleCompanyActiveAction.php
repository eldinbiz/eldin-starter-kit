<?php

declare(strict_types=1);

namespace App\Actions\Companies;

use App\Models\Company;
use InvalidArgumentException;

final class ToggleCompanyActiveAction
{
    /**
     * Toggle the active status of a company, ensuring child companies are disabled first.
     *
     * @throws InvalidArgumentException
     */
    public function execute(Company $company): Company
    {
        if ($company->is_active) {
            // Disabling: check all children are already disabled
            if (! $company->allChildrenDisabled()) {
                throw new InvalidArgumentException('All child companies must be disabled before disabling this company.');
            }
        }

        $company->update([
            'is_active' => ! $company->is_active,
        ]);

        return $company;
    }
}
