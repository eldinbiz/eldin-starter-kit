<?php

declare(strict_types=1);

namespace Database\Factories\Approval;

use App\Enums\ApprovalStatus;
use App\Models\Approval\ApprovalRequest;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApprovalRequest>
 */
class ApprovalRequestFactory extends Factory
{
    protected $model = ApprovalRequest::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'requestor_id' => User::factory(),
            'workflow_template_id' => WorkflowTemplateFactory::new(),
            'approvable_type' => null,
            'approvable_id' => null,
            'workflow_snapshot' => ['template_name' => '', 'groups' => []],
            'status' => ApprovalStatus::Pending,
            'submitted_at' => now(),
        ];
    }

    /**
     * Mark the request as approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ApprovalStatus::Approved,
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark the request as rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ApprovalStatus::Rejected,
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark the request as canceled.
     */
    public function canceled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ApprovalStatus::Canceled,
            'canceled_at' => now(),
        ]);
    }
}
