<?php

declare(strict_types=1);

namespace App\Actions\Approvals;

use App\Models\Approval\WorkflowTemplate;
use Illuminate\Support\Facades\DB;

final class UpdateWorkflowTemplateAction
{
    /**
     * Update an existing workflow template and recreate its groups and steps.
     *
     * @param array{
     *     name: string,
     *     description?: string|null,
     *     approvable_type?: string|null,
     *     is_active?: bool|int|string|null,
     *     scope?: string|null,
     *     groups: array<int, array{
     *         name: string,
     *         type: string,
     *         min_approvals?: int|string|null,
     *         steps?: array<int, array{
     *             label?: string|null,
     *             resolver_type: string,
     *             resolver_payload?: array<string, mixed>|null
     *         }>|null
     *     }>
     * } $data
     */
    public function execute(WorkflowTemplate $workflow, array $data): WorkflowTemplate
    {
        return DB::transaction(function () use ($workflow, $data): WorkflowTemplate {
            $workflow->update([
                'name' => (string) $data['name'],
                'description' => isset($data['description']) ? (string) $data['description'] : null,
                'approvable_type' => ! empty($data['approvable_type']) ? (string) $data['approvable_type'] : null,
                'is_active' => isset($data['is_active']) ? (bool) $data['is_active'] : true,
                'scope' => (string) ($data['scope'] ?? 'this_company'),
            ]);

            // Remove old groups (and cascade delete their steps)
            $workflow->groups()->delete();

            foreach ($data['groups'] ?? [] as $groupIndex => $groupData) {
                $minApprovals = ($groupData['type'] === 'parallel' && ! empty($groupData['min_approvals']))
                    ? (int) $groupData['min_approvals']
                    : null;

                $group = $workflow->groups()->create([
                    'name' => (string) $groupData['name'],
                    'order' => $groupIndex,
                    'type' => (string) $groupData['type'],
                    'min_approvals' => $minApprovals,
                ]);

                foreach ($groupData['steps'] ?? [] as $stepIndex => $stepData) {
                    $group->steps()->create([
                        'label' => isset($stepData['label']) ? (string) $stepData['label'] : null,
                        'order' => $stepIndex,
                        'resolver_type' => (string) $stepData['resolver_type'],
                        'resolver_payload' => $stepData['resolver_payload'] ?? null,
                    ]);
                }
            }

            return $workflow;
        });
    }
}
