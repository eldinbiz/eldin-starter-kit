<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class AssignCompanyUserAction
{
    /**
     * Assign a user to a company with company-scoped roles and metadata.
     *
     * @param array{
     *     company_id: int|string,
     *     roles: array<int, int|string>,
     *     job_title?: string|null,
     *     manager_id?: int|string|null
     * } $data
     *
     * @throws InvalidArgumentException
     */
    public function execute(User $user, array $data): void
    {
        $companyId = (int) $data['company_id'];

        if ($user->companies()->where('company_id', $companyId)->exists()) {
            throw new InvalidArgumentException('This user is already assigned to that company.');
        }

        DB::transaction(function () use ($user, $companyId, $data): void {
            $user->companies()->attach($companyId, [
                'job_title' => $data['job_title'] ?? null,
                'manager_id' => ! empty($data['manager_id']) ? (int) $data['manager_id'] : null,
                'status' => 'active',
            ]);

            $roleIds = array_map('intval', (array) $data['roles']);
            $user->companyRoles()->attach($roleIds, ['company_id' => $companyId]);
        });
    }
}
