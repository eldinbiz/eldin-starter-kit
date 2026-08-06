<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseRequest>
 */
class PurchaseRequestFactory extends Factory
{
    protected $model = PurchaseRequest::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'requester_id' => User::factory(),
            'pr_number' => 'PR-'.fake()->unique()->numerify('######'),
            'title' => fake()->words(3, true),
            'department' => fake()->randomElement(['IT', 'Finance', 'Procurement', 'HR', 'Operations']),
            'description' => fake()->paragraph(),
            'total_amount' => fake()->randomFloat(2, 500, 50000),
            'status' => 'draft',
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'approved',
        ]);
    }

    public function pendingApproval(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'pending_approval',
        ]);
    }
}
