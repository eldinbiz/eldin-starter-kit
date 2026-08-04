<?php

declare(strict_types=1);

namespace Database\Factories\Approval;

use App\Enums\GroupType;
use App\Models\Approval\WorkflowGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkflowGroup>
 */
class WorkflowGroupFactory extends Factory
{
    protected $model = WorkflowGroup::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'template_id' => WorkflowTemplateFactory::new(),
            'name' => fake()->unique()->words(2, true),
            'order' => 0,
            'type' => GroupType::Sequential,
        ];
    }

    /**
     * Make the group sequential.
     */
    public function sequential(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => GroupType::Sequential,
        ]);
    }

    /**
     * Make the group parallel.
     */
    public function parallel(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => GroupType::Parallel,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function hasSteps(int $count = 1, array $attributes = []): static
    {
        return $this->has(WorkflowStepFactory::new()->count($count)->state($attributes), 'steps');
    }
}
