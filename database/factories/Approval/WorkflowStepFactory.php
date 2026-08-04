<?php

declare(strict_types=1);

namespace Database\Factories\Approval;

use App\Enums\ResolverType;
use App\Models\Approval\WorkflowStep;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkflowStep>
 */
class WorkflowStepFactory extends Factory
{
    protected $model = WorkflowStep::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'group_id' => WorkflowGroupFactory::new(),
            'label' => fake()->words(2, true),
            'order' => 0,
            'resolver_type' => ResolverType::NPlus1,
            'resolver_payload' => [],
        ];
    }

    /**
     * Resolve the approver as the requestor's direct manager.
     */
    public function nPlus1(): static
    {
        return $this->state(fn (array $attributes): array => [
            'resolver_type' => ResolverType::NPlus1,
            'resolver_payload' => [],
        ]);
    }

    /**
     * Resolve the approver as a specific user.
     */
    public function specificUser(int $userId): static
    {
        return $this->state(fn (array $attributes): array => [
            'resolver_type' => ResolverType::SpecificUser,
            'resolver_payload' => ['user_id' => $userId],
        ]);
    }

    /**
     * Resolve the approver as the manager of the previous step's approver.
     */
    public function nPlus1OfPrevious(): static
    {
        return $this->state(fn (array $attributes): array => [
            'resolver_type' => ResolverType::NPlus1OfPrevious,
            'resolver_payload' => [],
        ]);
    }
}
