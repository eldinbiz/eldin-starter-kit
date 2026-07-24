<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Approvable;
use App\Enums\ApprovalStatus;
use App\Enums\GroupType;
use App\Enums\ResolverType;
use App\Enums\StepState;
use App\Events\Approval\ApprovalCanceled;
use App\Events\Approval\ApprovalCompleted;
use App\Events\Approval\ApprovalDelegated;
use App\Events\Approval\ApprovalRejected;
use App\Events\Approval\ApprovalRequested;
use App\Events\Approval\ApprovalStepCompleted;
use App\Models\Approval\ApprovalRequest;
use App\Models\Approval\ApprovalRequestStep;
use App\Models\Approval\WorkflowTemplate;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ApprovalEngine
{
    /**
     * Submit a new approval request using the given workflow template.
     *
     * @throws RuntimeException if the approvable model cannot be matched to any user IDs.
     */
    public function submit(Approvable $approvable, WorkflowTemplate $template): ApprovalRequest
    {
        /** @var User $requestor */
        $requestor = $approvable->getApprovalRequester();
        $company = $approvable->getApprovalCompany();

        // Build a snapshot of the template so edits don't affect in-flight requests.
        $snapshot = $this->buildSnapshot($template);

        return DB::transaction(function () use ($approvable, $template, $requestor, $company, $snapshot): ApprovalRequest {
            /** @var ApprovalRequest $request */
            $request = ApprovalRequest::create([
                'company_id' => $company->id,
                'requestor_id' => $requestor->id,
                'workflow_template_id' => $template->id,
                'approvable_type' => $approvable::class,
                'approvable_id' => $approvable->id,
                'workflow_snapshot' => $snapshot,
                'status' => ApprovalStatus::Pending,
                'submitted_at' => now(),
            ]);

            // Build runtime step records
            foreach ($snapshot['groups'] as $group) {
                foreach ($group['steps'] as $stepData) {
                    $resolverType = ResolverType::from($stepData['resolver_type']);
                    $approverId = $this->resolveApproverUserId($resolverType, $stepData['resolver_payload'] ?? [], $requestor, $company);

                    ApprovalRequestStep::create([
                        'approval_request_id' => $request->id,
                        'group_name' => $group['name'],
                        'step_label' => $stepData['label'],
                        'order' => $stepData['order'],
                        'resolver_type' => $resolverType,
                        'resolver_payload' => $stepData['resolver_payload'] ?? [],
                        'approver_id' => $approverId,
                        'state' => StepState::Pending,
                    ]);
                }
            }

            ApprovalRequested::dispatch($request);

            return $request;
        });
    }

    /**
     * Approve a step. If the group finishes, advance the state machine.
     */
    public function approve(ApprovalRequestStep $step, User $approver, ?string $notes = null): void
    {
        if (! $step->isPending()) {
            throw new RuntimeException('This step is not in a pending state.');
        }

        DB::transaction(function () use ($step, $notes): void {
            $step->update([
                'state' => StepState::Approved,
                'action_at' => now(),
                'notes' => $notes,
            ]);

            ApprovalStepCompleted::dispatch($step);

            $request = $step->request()->lockForUpdate()->firstOrFail();
            $this->advanceStateMachine($request);
        });
    }

    /**
     * Reject a step — this rejects the entire approval request.
     */
    public function reject(ApprovalRequestStep $step, User $approver, string $reason): void
    {
        if (! $step->isPending()) {
            throw new RuntimeException('This step is not in a pending state.');
        }

        DB::transaction(function () use ($step, $reason): void {
            $step->update([
                'state' => StepState::Rejected,
                'action_at' => now(),
                'notes' => $reason,
            ]);

            $request = $step->request()->lockForUpdate()->firstOrFail();

            // Reject all remaining pending steps
            $request->steps()->pending()->update([
                'state' => StepState::Rejected,
            ]);

            $request->update([
                'status' => ApprovalStatus::Rejected,
                'completed_at' => now(),
            ]);

            ApprovalRejected::dispatch($step);
        });
    }

    /**
     * Requestor cancels the entire pending request.
     */
    public function cancel(ApprovalRequest $request, User $canceledBy): void
    {
        if (! $request->isCancelable()) {
            throw new RuntimeException('This request cannot be canceled.');
        }

        DB::transaction(function () use ($request, $canceledBy): void {
            $request->lockForUpdate()->update([
                'status' => ApprovalStatus::Canceled,
                'canceled_at' => now(),
            ]);

            ApprovalCanceled::dispatch($request, $canceledBy);
        });
    }

    /**
     * Reassign an approver for a pending step.
     */
    public function delegate(ApprovalRequestStep $step, User $newApprover, User $delegatedBy): void
    {
        if (! $step->isPending()) {
            throw new RuntimeException('This step is not in a pending state.');
        }

        $previousApproverId = $step->approver_id;

        $step->update([
            'approver_id' => $newApprover->id,
            'delegated_from_id' => $delegatedBy->id,
        ]);

        ApprovalDelegated::dispatch($step, $delegatedBy, $newApprover);
    }

    /**
     * Preview a workflow — resolves all approvers without creating any database records.
     * Returns the template structure with resolved approver names for each step.
     *
     * @return array{template_name: string, groups: array<int, array{name: string, type: string, steps: array<int, array{label: string, order: int, resolver_type: string, approver_name: string, approver_job_title: string|null}>}>}
     */
    public function preview(Approvable $approvable, WorkflowTemplate $template): array
    {
        $requestor = $approvable->getApprovalRequester();
        $company = $approvable->getApprovalCompany();

        $template->load('groups.steps');

        $groups = $template->groups->map(function ($group) use ($requestor, $company): array {
            return [
                'name' => $group->name,
                'type' => $group->type->value,
                'steps' => $group->steps->map(function ($step) use ($requestor, $company): array {
                    $resolverType = $step->resolver_type;

                    // Skip N+1_of_previous — can't resolve without previous step context
                    if ($resolverType === ResolverType::NPlus1OfPrevious) {
                        return [
                            'label' => $step->label,
                            'order' => $step->order,
                            'resolver_type' => $resolverType->value,
                            'approver_name' => 'Will be determined at runtime',
                            'approver_job_title' => null,
                        ];
                    }

                    try {
                        $approverId = $this->resolveApproverUserId(
                            $resolverType,
                            $step->resolver_payload ?? [],
                            $requestor,
                            $company,
                        );
                        $approver = User::find($approverId);

                        $membership = $approver?->companies->firstWhere('id', $company->id);

                        return [
                            'label' => $step->label,
                            'order' => $step->order,
                            'resolver_type' => $resolverType->value,
                            'approver_name' => $approver?->name ?? 'Unknown',
                            'approver_job_title' => $membership?->pivot?->job_title,
                        ];
                    } catch (RuntimeException $e) {
                        return [
                            'label' => $step->label,
                            'order' => $step->order,
                            'resolver_type' => $resolverType->value,
                            'approver_name' => 'Cannot resolve',
                            'approver_job_title' => null,
                        ];
                    }
                })->values()->toArray(),
            ];
        })->values()->toArray();

        return [
            'template_name' => $template->name,
            'groups' => $groups,
        ];
    }

    /**
     * Resolve an approver user ID from a resolver type at submission time.
     *
     * @param  array<string, mixed>  $payload
     */
    private function resolveApproverUserId(ResolverType $type, array $payload, User $requestor, Company $company): int
    {
        return match ($type) {
            ResolverType::NPlus1 => $this->resolveNPlus1($requestor, $company),
            ResolverType::SpecificUser => $this->resolveSpecificUser($payload, $company),
            ResolverType::NPlus1OfPrevious => throw new RuntimeException('N+1_of_previous cannot be resolved at submission — it requires previous step context.'),
        };
    }

    private function resolveNPlus1(User $user, Company $company): int
    {
        $manager = $user->getManager($company);

        if (! $manager) {
            throw new RuntimeException("Cannot resolve N+1 approver — user {$user->id} has no manager in company {$company->id}.");
        }

        return $manager->id;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function resolveSpecificUser(array $payload, Company $company): int
    {
        $userId = $payload['user_id'] ?? null;

        if (! $userId) {
            throw new RuntimeException('specific_user resolver requires a user_id in resolver_payload.');
        }

        // Verify the user belongs to this company or an ancestor
        $target = User::findOrFail((int) $userId);

        $targetCompanies = $target->companies->pluck('id');

        $validIds = $company->id;

        foreach ($company->allAncestors() as $ancestor) {
            $validIds = array_merge((array) $validIds, [$ancestor->id]);
        }

        if (! in_array($validIds, $targetCompanies->toArray(), true)) {
            // Simple check: does user belong to this company or any ancestor?
            $companyIds = collect([$company->id])->merge($company->allAncestors()->pluck('id'));
            if (! $targetCompanies->intersect($companyIds)->isNotEmpty()) {
                throw new RuntimeException("User {$target->id} does not belong to company {$company->id} or any ancestor.");
            }
        }

        return (int) $target->id;
    }

    /**
     * Check if a group is complete and advance to the next group if needed.
     * If all groups are complete, mark the request as approved.
     */
    private function advanceStateMachine(ApprovalRequest $request): void
    {
        $snapshot = $request->workflow_snapshot;

        if (! $snapshot || ! isset($snapshot['groups'])) {
            return;
        }

        // Reload steps
        $request->load('steps');
        $allSteps = $request->steps;

        foreach ($snapshot['groups'] as $groupIndex => $group) {
            $groupName = $group['name'];
            $groupType = GroupType::from($group['type']);

            $groupSteps = $allSteps->where('group_name', $groupName);

            // If any step in this group is rejected, stop — request already rejected.
            if ($groupSteps->contains('state', StepState::Rejected)) {
                return;
            }

            // Check if this group is complete
            $isGroupComplete = false;

            if ($groupType === GroupType::Sequential) {
                // Sequential: all steps must be approved
                $allApproved = $groupSteps->every(fn ($s) => $s->state === StepState::Approved);
                $isGroupComplete = $allApproved;
            } else {
                // Parallel: ALL steps must be approved (concurrent approval, but all required)
                $allApproved = $groupSteps->every(fn ($s) => $s->state === StepState::Approved);
                $isGroupComplete = $allApproved;
            }

            if (! $isGroupComplete) {
                // This is the current active group — stop
                return;
            }

            // Group is complete. If this is the last group, approve the request.
            if ($groupIndex === count($snapshot['groups']) - 1) {
                $request->update([
                    'status' => ApprovalStatus::Approved,
                    'completed_at' => now(),
                ]);

                ApprovalCompleted::dispatch($request);

                return;
            }
            // Otherwise continue to next group (the loop will handle it)
        }
    }

    /**
     * Build a snapshot array from a workflow template.
     *
     * @return array{template_name: string, groups: array<int, array{name: string, type: string, steps: array<int, array{label: string, order: int, resolver_type: string, resolver_payload: array}>}>}
     */
    private function buildSnapshot(WorkflowTemplate $template): array
    {
        $template->load('groups.steps');

        return [
            'template_name' => $template->name,
            'groups' => $template->groups->map(function ($group): array {
                return [
                    'name' => $group->name,
                    'type' => $group->type->value,
                    'steps' => $group->steps->map(function ($step): array {
                        return [
                            'label' => $step->label,
                            'order' => $step->order,
                            'resolver_type' => $step->resolver_type->value,
                            'resolver_payload' => $step->resolver_payload ?? [],
                        ];
                    })->values()->toArray(),
                ];
            })->values()->toArray(),
        ];
    }
}
