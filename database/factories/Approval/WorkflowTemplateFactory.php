<?php

declare(strict_types=1);

namespace Database\Factories\Approval;

use App\Enums\TemplateScope;
use App\Models\Approval\WorkflowTemplate;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkflowTemplate>
 */
class WorkflowTemplateFactory extends Factory
{
    protected $model = WorkflowTemplate::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->unique()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
            'scope' => TemplateScope::ThisCompany,
            'approvable_type' => null,
        ];
    }

    /**
     * Mark the template as inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    /**
     * Make the template available to all companies.
     */
    public function scopeGlobal(): static
    {
        return $this->state(fn (array $attributes): array => [
            'scope' => TemplateScope::Global,
        ]);
    }

    /**
     * Make the template available to this company and its subsidiaries.
     */
    public function scopeSubsidiaries(): static
    {
        return $this->state(fn (array $attributes): array => [
            'scope' => TemplateScope::ThisCompanyAndSubsidiaries,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function hasGroups(int $count = 1, array $attributes = []): static
    {
        return $this->has(WorkflowGroupFactory::new()->count($count)->state($attributes), 'groups');
    }
}
