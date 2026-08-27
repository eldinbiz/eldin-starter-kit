<?php

declare(strict_types=1);

namespace App\Livewire\Approvals;

use App\Actions\Approvals\ApproveStepAction;
use App\Actions\Approvals\CancelApprovalRequestAction;
use App\Actions\Approvals\DelegateApprovalStepAction;
use App\Actions\Approvals\RejectStepAction;
use App\Enums\ApprovalStatus;
use App\Enums\StepState;
use App\Helpers\ApprovableTypes;
use App\Livewire\DataTables\DataTableComponent;
use App\Models\Approval\ApprovalRequest;
use App\Models\Approval\ApprovalRequestStep;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;

class ApprovalInbox extends DataTableComponent
{
    #[Url(as: 'tab', history: true)]
    public string $activeTab = 'inbox'; // 'inbox' | 'history' | 'requests'

    // Modal States
    public bool $approveModalOpen = false;
    public bool $rejectModalOpen = false;
    public bool $delegateModalOpen = false;
    public bool $cancelModalOpen = false;

    public ?int $selectedStepId = null;
    public ?int $selectedRequestId = null;
    public string $selectedTitle = '';

    // Form Inputs
    public string $approvalNotes = '';
    public string $rejectionReason = '';
    public ?int $delegateUserId = null;

    public function mount(string $tab = 'inbox'): void
    {
        if (in_array($tab, ['inbox', 'history', 'requests'], true)) {
            $this->activeTab = $tab;
        }
    }

    public function setTab(string $tab): void
    {
        if (in_array($tab, ['inbox', 'history', 'requests'], true)) {
            $this->activeTab = $tab;
            $this->resetPage();
        }
    }

    public function query(): Builder
    {
        /** @var User|null $user */
        $user = auth()->user();
        $company = $user?->getActiveCompany();

        if (! $user || ! $company) {
            return ApprovalRequest::query()->whereRaw('1 = 0');
        }

        if ($this->activeTab === 'inbox') {
            // Steps pending for current user in active company
            return ApprovalRequestStep::query()
                ->where('approver_id', $user->id)
                ->where('state', StepState::Pending)
                ->whereHas('request', function (Builder $q) use ($company): void {
                    $q->where('company_id', $company->id)
                        ->where('status', ApprovalStatus::Pending);
                })
                ->with(['request.approvable', 'request.requestor', 'request.company', 'approver']);
        }

        if ($this->activeTab === 'history') {
            // Steps or requests where user acted in active company
            return ApprovalRequestStep::query()
                ->where(function (Builder $q) use ($user): void {
                    $q->where('approver_id', $user->id)
                        ->orWhere('delegated_from_id', $user->id);
                })
                ->whereIn('state', [StepState::Approved, StepState::Rejected])
                ->whereHas('request', function (Builder $q) use ($company): void {
                    $q->where('company_id', $company->id);
                })
                ->with(['request.approvable', 'request.requestor', 'request.company', 'approver']);
        }

        // My Requests: requests submitted by current user
        return ApprovalRequest::query()
            ->where('requestor_id', $user->id)
            ->where('company_id', $company->id)
            ->with(['approvable', 'steps.approver', 'company']);
    }

    protected function applySearch(Builder $query): void
    {
        if ($this->search === '') {
            return;
        }

        if ($this->activeTab === 'requests') {
            $query->whereHas('approvable', function (Builder $q): void {
                // If model has title or name
                $q->where('title', 'like', "%{$this->search}%");
            });
        } else {
            $query->whereHas('request', function (Builder $rq): void {
                $rq->whereHas('requestor', function (Builder $uq): void {
                    $uq->where('name', 'like', "%{$this->search}%");
                });
            });
        }
    }

    protected function applyFilters(Builder $query): void
    {
        if (! empty($this->filterValues['type'])) {
            $type = $this->filterValues['type'];
            if ($this->activeTab === 'requests') {
                $query->where('approvable_type', $type);
            } else {
                $query->whereHas('request', fn (Builder $rq) => $rq->where('approvable_type', $type));
            }
        }
    }

    // Modal openers
    public function openApproveModal(int $stepId, string $title = ''): void
    {
        $this->selectedStepId = $stepId;
        $this->selectedTitle = $title;
        $this->approvalNotes = '';
        $this->approveModalOpen = true;
    }

    public function openRejectModal(int $stepId, string $title = ''): void
    {
        $this->selectedStepId = $stepId;
        $this->selectedTitle = $title;
        $this->rejectionReason = '';
        $this->rejectModalOpen = true;
    }

    public function openDelegateModal(int $stepId, string $title = ''): void
    {
        $this->selectedStepId = $stepId;
        $this->selectedTitle = $title;
        $this->delegateUserId = null;
        $this->delegateModalOpen = true;
    }

    public function openCancelModal(int $requestId, string $title = ''): void
    {
        $this->selectedRequestId = $requestId;
        $this->selectedTitle = $title;
        $this->cancelModalOpen = true;
    }

    // Actions execution
    public function approveStep(ApproveStepAction $action): void
    {
        if (! $this->selectedStepId) {
            return;
        }

        /** @var User $user */
        $user = auth()->user();
        $step = ApprovalRequestStep::findOrFail($this->selectedStepId);

        if ($step->approver_id !== $user->id || ! $step->isPending()) {
            $this->dispatch('notify', message: 'Step cannot be approved or has already changed state.', type: 'error');
            $this->approveModalOpen = false;
            return;
        }

        $action->execute($step, $user, $this->approvalNotes !== '' ? $this->approvalNotes : null);

        $this->approveModalOpen = false;
        $this->selectedStepId = null;
        $this->dispatch('notify', message: 'Approval step confirmed.', type: 'success');
    }

    public function rejectStep(RejectStepAction $action): void
    {
        $this->validate([
            'rejectionReason' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        if (! $this->selectedStepId) {
            return;
        }

        /** @var User $user */
        $user = auth()->user();
        $step = ApprovalRequestStep::findOrFail($this->selectedStepId);

        if ($step->approver_id !== $user->id || ! $step->isPending()) {
            $this->dispatch('notify', message: 'Step cannot be rejected or has already changed state.', type: 'error');
            $this->rejectModalOpen = false;
            return;
        }

        $action->execute($step, $user, $this->rejectionReason);

        $this->rejectModalOpen = false;
        $this->selectedStepId = null;
        $this->dispatch('notify', message: 'Request rejected.', type: 'info');
    }

    public function delegateStep(DelegateApprovalStepAction $action): void
    {
        $this->validate([
            'delegateUserId' => ['required', 'integer', 'exists:users,id'],
        ]);

        if (! $this->selectedStepId) {
            return;
        }

        /** @var User $user */
        $user = auth()->user();
        $step = ApprovalRequestStep::findOrFail($this->selectedStepId);

        if ($step->approver_id !== $user->id || ! $step->isPending()) {
            $this->dispatch('notify', message: 'Step cannot be delegated.', type: 'error');
            $this->delegateModalOpen = false;
            return;
        }

        $action->execute($step, (int) $this->delegateUserId, $user);

        $this->delegateModalOpen = false;
        $this->selectedStepId = null;
        $this->dispatch('notify', message: 'Step delegated successfully.', type: 'success');
    }

    public function cancelRequest(CancelApprovalRequestAction $action): void
    {
        if (! $this->selectedRequestId) {
            return;
        }

        /** @var User $user */
        $user = auth()->user();
        $request = ApprovalRequest::findOrFail($this->selectedRequestId);

        if ($request->requestor_id !== $user->id) {
            abort(403);
        }

        $action->execute($request, $user);

        $this->cancelModalOpen = false;
        $this->selectedRequestId = null;
        $this->dispatch('notify', message: 'Approval request canceled.', type: 'warning');
    }

    #[Computed]
    public function inboxCount(): int
    {
        /** @var User|null $user */
        $user = auth()->user();
        $company = $user?->getActiveCompany();

        if (! $user || ! $company) {
            return 0;
        }

        return ApprovalRequestStep::query()
            ->where('approver_id', $user->id)
            ->where('state', StepState::Pending)
            ->whereHas('request', function (Builder $q) use ($company): void {
                $q->where('company_id', $company->id)
                    ->where('status', ApprovalStatus::Pending);
            })
            ->count();
    }

    #[Computed]
    public function approvableTypeOptions(): array
    {
        return ApprovableTypes::options();
    }

    #[Computed]
    public function companyUsers()
    {
        /** @var User|null $user */
        $user = auth()->user();
        $company = $user?->getActiveCompany();

        if (! $company) {
            return collect();
        }

        $companyIds = collect([$company->id])
            ->merge($company->allAncestors()->pluck('id'));

        return User::whereHas('companies', fn ($q) => $q->whereIn('companies.id', $companyIds))
            ->orderBy('name')
            ->get();
    }

    public function render()
    {
        return view('livewire.approvals.approval-inbox');
    }
}
