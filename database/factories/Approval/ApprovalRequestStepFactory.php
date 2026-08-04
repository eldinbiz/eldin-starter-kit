<?php

declare(strict_types=1);

namespace Database\Factories\Approval;

use App\Enums\ResolverType;
use App\Enums\StepState;
use App\Models\Approval\ApprovalRequestStep;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApprovalRequestStep>
 */
class ApprovalRequestStepFactory extends Factory
{
    protected $model = ApprovalRequestStep::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'approval_request_id' => ApprovalRequestFactory::new(),
            'group_name' => 'Default Group',
            'step_label' => fake()->words(2, true),
            'order' => 0,
            'resolver_type' => ResolverType::NPlus1,
            'resolver_payload' => [],
            'approver_id' => User::factory(),
            'state' => StepState::Pending,
            'notes' => null,
            'action_at' => null,
        ];
    }

    /**
     * Mark the step as pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'state' => StepState::Pending,
        ]);
    }

    /**
     * Mark the step as approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes): array => [
            'state' => StepState::Approved,
            'action_at' => now(),
        ]);
    }

    /**
     * Mark the step as rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes): array => [
            'state' => StepState::Rejected,
            'action_at' => now(),
            'notes' => 'Rejected',
        ]);
    }

    /**
     * Mark the step as skipped.
     */
    public function skipped(): static
    {
        return $this->state(fn (array $attributes): array => [
            'state' => StepState::Skipped,
        ]);
    }

    /**
     * Mark the step as awaiting its predecessor's approval.
     */
    public function awaitingPrevious(): static
    {
        return $this->state(fn (array $attributes): array => [
            'state' => StepState::AwaitingPrevious,
            'approver_id' => null,
        ]);
    }
}
