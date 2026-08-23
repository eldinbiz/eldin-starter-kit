<?php

declare(strict_types=1);

namespace App\Actions\Approvals;

use App\Models\Approval\WorkflowTemplate;
use App\Models\Company;
use Illuminate\Support\Facades\DB;

final class CreateWorkflowTemplateAction
{
    /**
     * Create a new workflow template with its nested groups and steps.
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
    public function execute(Company $company, array $data): WorkflowTemplate
    {
        return DB::transaction(function () use ($company, $data): WorkflowTemplate {
            $template = WorkflowTemplate::create([
                'company_id' => $company->id,
                'name' => (string) $data['name'],
                'description' => isset($data['description']) ? (string) $data['description'] : null,
                'approvable_type' => ! empty($data['approvable_type']) ? (string) $data['approvable_type'] : null,
                'is_active' => isset($data['is_active']) ? (bool) $data['is_active'] : true,
                'scope' => (string) ($data['scope'] ?? 'this_company'),
            ]);

            foreach ($data['groups'] ?? [] as $groupIndex => $groupData) {
                $minApprovals = ($groupData['type'] === 'parallel' && ! empty($groupData['min_approvals']))
                    ? (int) $groupData['min_approvals']
                    : null;

                $group = $template->groups()->create([
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

            return $template;
        });
    }
}
