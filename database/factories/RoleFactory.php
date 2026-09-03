<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word(),
            'permissions' => [],
        ];
    }

    /**
     * Create a sysadmin role.
     */
    public function sysadmin(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => 'sysadmin',
            'permissions' => Role::allPermissions(),
            'is_system' => true,
        ]);
    }

    /**
     * Create a company-admin role.
     */
    public function companyAdmin(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => 'company-admin',
            'permissions' => [
                'manage-users',
                'view-companies',
                'manage-org-chart',
                'view-org-chart',
                'manage-company-settings',
                'manage-approvals',
                'manage-approval-workflows',
            ],
        ]);
    }

    /**
     * Create an admin role.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => 'admin',
            'permissions' => Role::allPermissions(),
        ]);
    }

    /**
     * Create a manager role.
     */
    public function manager(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => 'manager',
            'permissions' => [
                'manage-users',
                'manage-org-chart',
                'view-approval-inbox',
                'view-approval-history',
                'view-approval-requests',
            ],
        ]);
    }

    /**
     * Create a user role.
     */
    public function user(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => 'user',
            'permissions' => [
                'view-org-chart',
                'view-approval-inbox',
                'view-approval-history',
                'view-approval-requests',
            ],
        ]);
    }
}
