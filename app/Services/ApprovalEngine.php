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
use Illuminate\Support\Collection;
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
            foreach ($snapshot['groups'] as $groupIndex => $group) {
                foreach ($group['steps'] as $stepData) {
                    $resolverType = ResolverType::from($stepData['resolver_type']);

                    $isNonFirstGroup = $groupIndex > 0;
                    $isSequentialStepNonZero = ($group['type'] === GroupType::Sequential->value && $stepData['order'] > 0);

                    if ($isNonFirstGroup || $resolverType === ResolverType::NPlus1OfPrevious || $isSequentialStepNonZero) {
                        $approverId = ($resolverType === ResolverType::NPlus1OfPrevious)
                            ? null
                            : $this->resolveApproverUserId($resolverType, $stepData['resolver_payload'] ?? [], $requestor, $company);

                        ApprovalRequestStep::create([
                            'approval_request_id' => $request->id,
                            'group_name' => $group['name'],
                            'step_label' => $stepData['label'],
                            'order' => $stepData['order'],
                            'resolver_type' => $resolverType,
                            'resolver_payload' => $stepData['resolver_payload'] ?? [],
                            'approver_id' => $approverId,
                            'state' => StepState::AwaitingPrevious,
                        ]);

                        continue;
                    }

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
            $request = $step->request()->lockForUpdate()->firstOrFail();

            if (! $request->isPending()) {
                throw new RuntimeException('This approval request is no longer pending.');
            }

            $step->update([
                'state' => StepState::Approved,
                'action_at' => now(),
                'notes' => $notes,
            ]);

            $this->advanceStateMachine($request);

            ApprovalStepCompleted::dispatch($step);
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

            // Mark all remaining pending AND awaiting_previous steps as skipped
            $request->steps()
                ->whereIn('state', [StepState::Pending, StepState::AwaitingPrevious])
                ->update([
                    'state' => StepState::Skipped,
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

        $request = $step->request()->firstOrFail();

        if (! $request->isPending()) {
            throw new RuntimeException('This approval request is no longer pending.');
        }

        $step->update([
            'approver_id' => $newApprover->id,
            'delegated_from_id' => $delegatedBy->id,
        ]);

        ApprovalDelegated::dispatch($step, $delegatedBy, $newApprover);
    }

    /**
     * Skip a pending or awaiting step — marks it as bypassed and advances the state machine.
     */
    public function skipStep(ApprovalRequestStep $step, User $skippedBy): void
    {
        $allowedStates = [StepState::Pending, StepState::AwaitingPrevious];

        if (! in_array($step->state, $allowedStates, true)) {
            throw new RuntimeException('This step is not in an actionable state.');
        }

        DB::transaction(function () use ($step, $skippedBy): void {
            $request = $step->request()->lockForUpdate()->firstOrFail();

            if (! $request->isPending()) {
                throw new RuntimeException('This approval request is no longer pending.');
            }

            $step->update([
                'state' => StepState::Skipped,
                'action_at' => now(),
                'notes' => "Skipped by administrator ({$skippedBy->name})",
            ]);

            $this->advanceStateMachine($request);

            ApprovalStepCompleted::dispatch($step);
        });
    }

    /**
     * Manually resolve an N+1_of_previous step that could not be auto-resolved
     * (e.g., previous approver had no manager). Assigns a specific user.
     */
    public function resolvePreviousManager(ApprovalRequestStep $step, User $resolvedBy, User $newApprover): void
    {
        if (! $step->isAwaitingPrevious()) {
            throw new RuntimeException('This step is not awaiting previous approver resolution.');
        }

        DB::transaction(function () use ($step, $resolvedBy, $newApprover): void {
            $step->update([
                'approver_id' => $newApprover->id,
                'state' => StepState::Pending,
                'delegated_from_id' => $resolvedBy->id,
            ]);

            ApprovalDelegated::dispatch($step, $resolvedBy, $newApprover);
        });
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
            throw new RuntimeException("Cannot resolve N+1 approver — {$user->name} has no manager assigned in {$company->name}.");
        }

        return $manager->id;
    }

    /**
     * Resolve the N+1_of_previous approver at runtime.
     * Looks up the actual approver of the previous step (respects delegation),
     * then finds their direct manager.
     * Returns the company that owns the request for manager lookup.
     * If no manager exists, skips the step by auto-approving it.
     *
     * @param  array<string, mixed>  $payload
     */
    private function resolveNPlus1OfPrevious(Approvable $approvable, int $previousStepId, Company $company, array $payload = []): ?int
    {
        $previousStep = ApprovalRequestStep::findOrFail($previousStepId);

        // The actual approver who acted — respects delegation (use approver_id, not delegated_from_id,
        // since approver_id holds whoever actually needs to act)
        $previousApproverId = $previousStep->approver_id;

        if (! $previousApproverId) {
            // Auto-skip — no previous approver to resolve from
            return null;
        }

        $previousApprover = User::findOrFail($previousApproverId);
        $manager = $previousApprover->getManager($company);

        if (! $manager) {
            // Auto-skip — previous approver has no manager
            return null;
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
                throw new RuntimeException("{$target->name} does not belong to {$company->name} or any ancestor company.");
            }
        }

        return (int) $target->id;
    }

    /**
     * Check if a group is complete and advance to the next group if needed.
     * If all groups are complete, mark the request as approved.
     * Also resolves any N+1_of_previous steps when their predecessor completes.
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

        // First pass: resolve any N+1_of_previous steps whose predecessor just completed
        $this->resolveAwaitingSteps($request, $snapshot);

        // Reload after potential resolution
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

            $isComplete = $groupSteps->every(fn ($s) => in_array($s->state, [StepState::Approved, StepState::Skipped], true));

            if (! $isComplete) {
                $this->activateGroupStepsIfNeeded($request, $group, $groupSteps);

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
     * Activate awaiting steps for an active group when its previous group completes.
     *
     * @param  Collection<int, ApprovalRequestStep>  $groupSteps
     */
    private function activateGroupStepsIfNeeded(ApprovalRequest $request, array $group, Collection $groupSteps): void
    {
        $awaitingSteps = $groupSteps->where('state', StepState::AwaitingPrevious);

        if ($awaitingSteps->isEmpty()) {
            return;
        }

        // If some step in this group is already Pending or Approved, do not activate everything if sequential
        $hasPendingOrApproved = $groupSteps->contains(fn ($s) => in_array($s->state, [StepState::Pending, StepState::Approved], true));

        $groupType = GroupType::from($group['type']);
        $approvable = $request->getApprovable();
        $company = $approvable?->getApprovalCompany();
        $requestor = $request->requestor;

        if ($groupType === GroupType::Sequential) {
            if (! $hasPendingOrApproved) {
                $minOrder = $awaitingSteps->min('order');
                $targetStep = $awaitingSteps->firstWhere('order', $minOrder);

                if ($targetStep) {
                    $this->activateSingleStep($targetStep, $requestor, $company);
                }
            }
        } else {
            foreach ($awaitingSteps as $step) {
                $this->activateSingleStep($step, $requestor, $company);
            }
        }

        $request->load('steps');
    }

    private function activateSingleStep(ApprovalRequestStep $step, ?User $requestor, ?Company $company): void
    {
        if ($step->approver_id === null && $company && $requestor) {
            try {
                $approverId = $this->resolveApproverUserId(
                    $step->resolver_type,
                    $step->resolver_payload ?? [],
                    $requestor,
                    $company
                );
                $step->update([
                    'approver_id' => $approverId,
                    'state' => StepState::Pending,
                ]);
            } catch (RuntimeException $e) {
                // Leave as AwaitingPrevious if resolution fails (for admin intervention)
            }
        } else {
            $step->update([
                'state' => StepState::Pending,
            ]);
        }
    }

    /**
     * Walk through the snapshot and resolve any N+1_of_previous steps
     * whose predecessor step has just been approved.
     */
    private function resolveAwaitingSteps(ApprovalRequest $request, array $snapshot): void
    {
        $allSteps = $request->steps;

        foreach ($snapshot['groups'] as $group) {
            $groupName = $group['name'];
            $groupType = GroupType::from($group['type']);

            // Only sequential groups support N+1_of_previous (there's a defined "previous")
            if ($groupType !== GroupType::Sequential) {
                continue;
            }

            $groupSteps = $allSteps
                ->where('group_name', $groupName)
                ->sortBy('order')
                ->values();

            for ($i = 1; $i < count($groupSteps); $i++) {
                $currentStep = $groupSteps[$i];
                $previousStep = $groupSteps[$i - 1];

                // Only resolve if the current step is awaiting and the previous is approved
                if (
                    $currentStep->state !== StepState::AwaitingPrevious
                    || $previousStep->state !== StepState::Approved
                ) {
                    continue;
                }

                // Attempt resolution
                $approvable = $request->getApprovable();
                $company = $approvable?->getApprovalCompany();

                if (! $company) {
                    continue;
                }

                $resolverPayload = $currentStep->resolver_payload ?? [];

                try {
                    $managerId = $this->resolveNPlus1OfPrevious(
                        $approvable,
                        $previousStep->id,
                        $company,
                        $resolverPayload,
                    );

                    if ($managerId === null) {
                        // Auto-skip: no manager — approve the step automatically
                        $currentStep->update([
                            'state' => StepState::Approved,
                            'action_at' => now(),
                            'notes' => 'Auto-skipped (no manager found for previous approver)',
                        ]);

                        ApprovalStepCompleted::dispatch($currentStep);
                    } else {
                        // Assign the resolved manager as the approver
                        $currentStep->update([
                            'approver_id' => $managerId,
                            'state' => StepState::Pending,
                        ]);

                        // Re-dispatch ApprovalRequested-like notification for this step
                        // (the notification module will pick this up)
                    }
                } catch (RuntimeException $e) {
                    // Resolution failed — leave as AwaitingPrevious for manual intervention
                    // The step stays unresolved until an admin uses resolvePreviousManager()
                }
            }
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
