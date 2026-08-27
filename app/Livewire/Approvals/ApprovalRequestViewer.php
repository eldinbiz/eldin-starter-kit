<?php

declare(strict_types=1);

namespace App\Livewire\Approvals;

use App\Actions\Approvals\ApproveStepAction;
use App\Actions\Approvals\CancelApprovalRequestAction;
use App\Actions\Approvals\DelegateApprovalStepAction;
use App\Actions\Approvals\RejectStepAction;
use App\Actions\Approvals\ResolvePreviousManagerAction;
use App\Models\Approval\ApprovalRequest;
use App\Models\Approval\ApprovalRequestStep;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ApprovalRequestViewer extends Component
{
    public int $requestId;

    // Modals
    public bool $approveModalOpen = false;
    public bool $rejectModalOpen = false;
    public bool $delegateModalOpen = false;
    public bool $resolveModalOpen = false;

    public ?int $activeStepId = null;
    public string $approvalNotes = '';
    public string $rejectionReason = '';
    public ?int $delegateUserId = null;
    public ?int $resolveApproverId = null;

    public function mount(int $requestId): void
    {
        $this->requestId = $requestId;
        $this->loadRequest();
    }

    private function loadRequest(): ApprovalRequest
    {
        /** @var User|null $user */
        $user = auth()->user();
        $company = $user?->getActiveCompany();

        $request = ApprovalRequest::with([
            'steps' => fn ($q) => $q->with(['approver', 'delegatedFrom']),
            'approvable',
            'requestor',
            'company',
        ])->findOrFail($this->requestId);

        if (! $user?->hasPlatformAccess() && (! $company || $request->company_id !== $company->id)) {
            abort(403, 'Unauthorized to view this approval request.');
        }

        return $request;
    }

    #[Computed]
    public function approvalRequest(): ApprovalRequest
    {
        return $this->loadRequest();
    }

    public function openApprove(int $stepId): void
    {
        $this->activeStepId = $stepId;
        $this->approvalNotes = '';
        $this->approveModalOpen = true;
    }

    public function openReject(int $stepId): void
    {
        $this->activeStepId = $stepId;
        $this->rejectionReason = '';
        $this->rejectModalOpen = true;
    }

    public function openDelegate(int $stepId): void
    {
        $this->activeStepId = $stepId;
        $this->delegateUserId = null;
        $this->delegateModalOpen = true;
    }

    public function openResolve(int $stepId): void
    {
        $this->activeStepId = $stepId;
        $this->resolveApproverId = null;
        $this->resolveModalOpen = true;
    }

    public function approve(ApproveStepAction $action): void
    {
        if (! $this->activeStepId) {
            return;
        }

        /** @var User $user */
        $user = auth()->user();
        $step = ApprovalRequestStep::findOrFail($this->activeStepId);

        if ($step->approver_id !== $user->id || ! $step->isPending()) {
            $this->dispatch('notify', message: 'Step cannot be approved.', type: 'error');
            $this->approveModalOpen = false;
            return;
        }

        $action->execute($step, $user, $this->approvalNotes !== '' ? $this->approvalNotes : null);

        $this->approveModalOpen = false;
        $this->activeStepId = null;
        $this->dispatch('notify', message: 'Step approved successfully.', type: 'success');
    }

    public function reject(RejectStepAction $action): void
    {
        $this->validate([
            'rejectionReason' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        if (! $this->activeStepId) {
            return;
        }

        /** @var User $user */
        $user = auth()->user();
        $step = ApprovalRequestStep::findOrFail($this->activeStepId);

        if ($step->approver_id !== $user->id || ! $step->isPending()) {
            $this->dispatch('notify', message: 'Step cannot be rejected.', type: 'error');
            $this->rejectModalOpen = false;
            return;
        }

        $action->execute($step, $user, $this->rejectionReason);

        $this->rejectModalOpen = false;
        $this->activeStepId = null;
        $this->dispatch('notify', message: 'Request rejected.', type: 'info');
    }

    public function delegate(DelegateApprovalStepAction $action): void
    {
        $this->validate([
            'delegateUserId' => ['required', 'integer', 'exists:users,id'],
        ]);

        if (! $this->activeStepId) {
            return;
        }

        /** @var User $user */
        $user = auth()->user();
        $step = ApprovalRequestStep::findOrFail($this->activeStepId);

        if ($step->approver_id !== $user->id || ! $step->isPending()) {
            $this->dispatch('notify', message: 'Step cannot be delegated.', type: 'error');
            $this->delegateModalOpen = false;
            return;
        }

        $action->execute($step, (int) $this->delegateUserId, $user);

        $this->delegateModalOpen = false;
        $this->activeStepId = null;
        $this->dispatch('notify', message: 'Step delegated successfully.', type: 'success');
    }

    public function cancel(CancelApprovalRequestAction $action)
    {
        /** @var User $user */
        $user = auth()->user();
        $request = $this->approvalRequest;

        if ($request->requestor_id !== $user->id) {
            abort(403);
        }

        $action->execute($request, $user);
        session()->flash('success', 'Approval request canceled.');

        return $this->redirect(route('approvals.requests'), navigate: true);
    }

    public function resolvePreviousManager(ResolvePreviousManagerAction $action): void
    {
        $this->validate([
            'resolveApproverId' => ['required', 'integer', 'exists:users,id'],
        ]);

        if (! $this->activeStepId) {
            return;
        }

        /** @var User $user */
        $user = auth()->user();
        $step = ApprovalRequestStep::findOrFail($this->activeStepId);

        if (! $step->isAwaitingPrevious()) {
            $this->dispatch('notify', message: 'Step is not awaiting previous resolution.', type: 'error');
            $this->resolveModalOpen = false;
            return;
        }

        $action->execute($step, $user, (int) $this->resolveApproverId);

        $this->resolveModalOpen = false;
        $this->activeStepId = null;
        $this->dispatch('notify', message: 'Approver resolved successfully.', type: 'success');
    }

    #[Computed]
    public function companyUsers(): Collection
    {
        $company = $this->approvalRequest->company;
        if (! $company) {
            return new Collection;
        }

        $companyIds = collect([$company->id])
            ->merge($company->allAncestors()->pluck('id'));

        return User::whereHas('companies', fn ($q) => $q->whereIn('companies.id', $companyIds))
            ->orderBy('name')
            ->get();
    }

    public function render()
    {
        return view('livewire.approvals.approval-request-viewer');
    }
}
