<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Events\NotificationRequested;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use App\ValueObjects\NotificationPayload;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final class CreateUserAction
{
    /**
     * Create a new user, associate them with a company, attach roles, and dispatch a welcome notification.
     *
     * @param array{
     *     name: string,
     *     email: string,
     *     password: string,
     *     auth_method?: string|null,
     *     roles: array<int, int|string>,
     *     job_title?: string|null,
     *     manager_id?: int|string|null
     * } $data
     */
    public function execute(Company $company, array $data): User
    {
        return DB::transaction(function () use ($company, $data): User {
            $user = User::create([
                'name' => (string) $data['name'],
                'email' => (string) $data['email'],
                'password' => Hash::make((string) $data['password']),
                'auth_method' => $data['auth_method'] ?? 'local',
            ]);

            // Enforce default 'user' role is always assigned
            $userRoleId = Role::where('name', 'user')->value('id');
            $roleIds = array_map('intval', (array) ($data['roles'] ?? []));
            if ($userRoleId && ! in_array((int) $userRoleId, $roleIds, true)) {
                $roleIds[] = (int) $userRoleId;
            }

            // Attach user to the specified company with metadata
            $user->companies()->attach($company->id, [
                'job_title' => $data['job_title'] ?? null,
                'manager_id' => ! empty($data['manager_id']) ? (int) $data['manager_id'] : null,
            ]);

            // Attach company-scoped roles
            $user->companyRoles()->attach($roleIds, ['company_id' => $company->id]);

            // Dispatch welcome notification
            NotificationRequested::dispatch(
                recipients: $user,
                payload: new NotificationPayload(
                    title: 'Welcome to '.config('app.name', 'Scaffolding App'),
                    message: "Your user account has been created for {$company->name}.",
                    type: 'user_welcome',
                    url: route('login'),
                    actionText: 'Log In to Your Account',
                    companyId: $company->id,
                    companyName: $company->name,
                    level: 'success'
                )
            );

            return $user;
        });
    }
}
