<?php

declare(strict_types=1);

namespace App\Actions\Companies;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class AssignCompanyUserAction
{
    /**
     * Assign a user to a company with company-scoped roles and metadata.
     *
     * @param array{
     *     user_id: int|string,
     *     roles: array<int, int|string>,
     *     job_title?: string|null,
     *     manager_id?: int|string|null
     * } $data
     */
    public function execute(Company $company, array $data): void
    {
        $userId = (int) $data['user_id'];

        DB::transaction(function () use ($company, $userId, $data): void {
            $company->users()->attach($userId, [
                'job_title' => $data['job_title'] ?? null,
                'manager_id' => ! empty($data['manager_id']) ? (int) $data['manager_id'] : null,
                'status' => 'active',
            ]);

            // Attach company-scoped roles
            $user = User::find($userId);
            if ($user !== null) {
                $roleIds = array_map('intval', (array) $data['roles']);
                $user->companyRoles()->attach($roleIds, ['company_id' => $company->id]);
            }
        });
    }
}
