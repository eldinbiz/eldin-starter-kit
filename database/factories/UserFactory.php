<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= 'password',
            'remember_token' => Str::random(10),
            'auth_method' => 'local',
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the user is a system bot.
     */
    public function system(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_system' => true,
        ]);
    }

    /**
     * Give the user the sysadmin role.
     */
    public function sysadmin(): static
    {
        return $this->afterCreating(function (User $user) {
            $company = Company::factory()->create();
            $user->companies()->attach($company->id, ['status' => 'active']);
            $role = Role::firstOrCreate(
                ['name' => 'sysadmin'],
                ['permissions' => Role::allPermissions(), 'is_active' => true, 'is_system' => true]
            );
            $user->companyRoles()->attach($role->id, ['company_id' => $company->id]);
        });
    }

    /**
     * Give the user the company-admin role.
     */
    public function companyAdmin(?Company $company = null): static
    {
        return $this->afterCreating(function (User $user) use ($company) {
            $targetCompany = $company ?? Company::factory()->create();
            $user->companies()->syncWithoutDetaching([$targetCompany->id => ['status' => 'active']]);
            $role = Role::firstOrCreate(
                ['name' => 'company-admin'],
                [
                    'permissions' => [
                        'manage-users',
                        'view-companies',
                        'manage-org-chart',
                        'view-org-chart',
                        'manage-company-settings',
                        'manage-approvals',
                        'manage-approval-workflows',
                    ],
                    'is_active' => true,
                ]
            );
            $user->companyRoles()->attach($role->id, ['company_id' => $targetCompany->id]);
        });
    }

    /**
     * Give the user the platform-admin role (deprecated: aliases to sysadmin).
     */
    public function platformAdmin(): static
    {
        return $this->sysadmin();
    }
}
