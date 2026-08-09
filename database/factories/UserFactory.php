<?php

namespace Database\Factories;

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
     * Give the user the platform-admin role.
     */
    public function platformAdmin(): static
    {
        return $this->afterCreating(function (User $user) {
            $company = \App\Models\Company::factory()->create();
            $user->companies()->attach($company->id);
            $role = \App\Models\Role::firstOrCreate(['name' => 'platform-admin', 'is_system_role' => true, 'company_id' => null]);
            $user->companyRoles()->attach($role->id, ['company_id' => $company->id]);
        });
    }
}
