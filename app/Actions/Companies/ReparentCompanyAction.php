<?php

declare(strict_types=1);

namespace App\Actions\Companies;

use App\Models\Company;
use InvalidArgumentException;

final class ReparentCompanyAction
{
    /**
     * Reparent a company with self-reference and cycle prevention checks.
     *
     * @throws InvalidArgumentException
     */
    public function execute(int $companyId, ?int $newParentId): Company
    {
        $company = Company::findOrFail($companyId);

        // Prevent self-referencing
        if ($companyId === $newParentId) {
            throw new InvalidArgumentException('A company cannot be its own parent.');
        }

        // Cycle detection: prevent moving a company under one of its own descendants
        if ($newParentId !== null) {
            $newParent = Company::findOrFail($newParentId);

            if ($newParent->isDescendantOf($company)) {
                throw new InvalidArgumentException('Cannot move a company under one of its own subsidiaries.');
            }
        }

        $company->update([
            'parent_id' => $newParentId,
        ]);

        return $company;
    }
}
