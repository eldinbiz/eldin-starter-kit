<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final class UpdateUserAction
{
    /**
     * Update an existing user's attributes and sync company-scoped roles.
     *
     * @param array{
     *     name: string,
     *     email: string,
     *     password?: string|null,
     *     auth_method?: string|null,
     *     roles: array<int, int|string>
     * } $data
     */
    public function execute(User $user, Company $company, array $data): User
    {
        return DB::transaction(function () use ($user, $company, $data): User {
            $userData = [
                'name' => (string) $data['name'],
                'email' => (string) $data['email'],
                'auth_method' => $data['auth_method'] ?? ($user->auth_method ?? 'local'),
            ];

            if (! empty($data['password'])) {
                $userData['password'] = Hash::make((string) $data['password']);
            }

            $user->update($userData);

            // Enforce default 'user' role is always assigned
            $userRoleId = Role::where('name', 'user')->value('id');
            $roleIds = array_map('intval', (array) ($data['roles'] ?? []));
            if ($userRoleId && ! in_array((int) $userRoleId, $roleIds, true)) {
                $roleIds[] = (int) $userRoleId;
            }

            // Sync company-scoped roles for the given company
            $pivotData = array_fill(0, count($roleIds), ['company_id' => $company->id]);
            $user->companyRoles()
                ->wherePivot('company_id', $company->id)
                ->sync(array_combine($roleIds, $pivotData));

            return $user;
        });
    }
}
