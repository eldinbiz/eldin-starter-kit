<?php

use App\Actions\Approvals\ApproveStepAction;
use App\Actions\Approvals\CancelApprovalRequestAction;
use App\Actions\Approvals\DelegateApprovalStepAction;
use App\Actions\Approvals\RejectStepAction;
use App\Enums\ApprovalStatus;
use App\Enums\StepState;
use App\Helpers\ApprovableTypes;
use App\Livewire\DataTables\WithDataTable;
use App\Models\Approval\ApprovalRequest;
use App\Models\Approval\ApprovalRequestStep;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

new class extends Component
{
    use WithDataTable;

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
    public function companyUsers(): Collection
    {
        /** @var User|null $user */
        $user = auth()->user();
        $company = $user?->getActiveCompany();

        if (! $company) {
            return new Collection;
        }

        $companyIds = collect([$company->id])
            ->merge($company->allAncestors()->pluck('id'));

        return User::whereHas('companies', fn ($q) => $q->whereIn('companies.id', $companyIds))
            ->orderBy('name')
            ->get();
    }
};
?>

<div class="space-y-6">

    {{-- Filter & Search Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="relative flex-1 max-w-md">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-gray-400 dark:text-gray-500">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
            </span>
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Search requests by requester, title..."
                class="h-10 w-full rounded-lg border border-gray-300 bg-white pl-10 pr-9 text-sm text-gray-800 placeholder-gray-400 focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-white"
            />
        </div>

        <div class="flex items-center gap-3">
            <select
                wire:model.live="filterValues.type"
                class="h-10 rounded-lg border border-gray-300 bg-white px-3 text-xs text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-white"
            >
                <option value="">All Approvable Modules</option>
                @foreach ($this->approvableTypeOptions as $class => $label)
                    <option value="{{ $class }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Loading State --}}
    <div wire:loading.delay.shortest class="w-full">
        @include('livewire.datatables.loading', ['columns' => 4, 'rows' => 4])
    </div>

    {{-- Tab Contents --}}
    <div wire:loading.remove.delay.shortest>
        @if ($this->rows->isNotEmpty())
            <div class="space-y-4">
                @if ($activeTab === 'inbox')
                    {{-- INBOX TAB ITEMS --}}
                    @foreach ($this->rows as $step)
                        @php
                            $req = $step->request;
                            $approvable = $req?->approvable;
                            $title = ($approvable && method_exists($approvable, 'getApprovalTitle'))
                                ? $approvable->getApprovalTitle()
                                : ($approvable?->name ?? $approvable?->title ?? "Approval Request #{$req->id}");
                        @endphp
                        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03] transition-all hover:border-brand-500/50">
                            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 border-b border-gray-100 pb-4 dark:border-gray-800">
                                <div>
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="inline-flex items-center rounded-md bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-400 dark:ring-amber-500/20">
                                            Awaiting Your Action
                                        </span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            Step: <strong class="text-gray-700 dark:text-gray-200">{{ $step->display_label ?: 'Approval Step' }}</strong>
                                        </span>
                                    </div>
                                    <h4 class="text-base font-semibold text-gray-900 dark:text-white">
                                        {{ $title }}
                                    </h4>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                        Submitted by <strong class="text-gray-700 dark:text-gray-300">{{ $req->requestor?->name }}</strong> &bull; {{ $req->created_at->diffForHumans() }}
                                    </p>
                                </div>

                                {{-- Quick Action Buttons --}}
                                <div class="flex items-center gap-2">
                                    <a
                                        href="{{ route('approvals.show', $req->id) }}"
                                        wire:navigate
                                        class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                                    >
                                        View Details
                                    </a>
                                    <button
                                        type="button"
                                        wire:click="openDelegateModal({{ $step->id }}, '{{ addslashes($title) }}')"
                                        class="inline-flex items-center justify-center rounded-lg border border-purple-300 bg-purple-50 px-3 py-2 text-xs font-medium text-purple-700 shadow-theme-xs hover:bg-purple-100 dark:border-purple-800 dark:bg-purple-900/20 dark:text-purple-300 cursor-pointer"
                                    >
                                        Delegate
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="openRejectModal({{ $step->id }}, '{{ addslashes($title) }}')"
                                        class="inline-flex items-center justify-center rounded-lg border border-rose-300 bg-rose-50 px-3.5 py-2 text-xs font-medium text-rose-700 shadow-theme-xs hover:bg-rose-100 dark:border-rose-800 dark:bg-rose-900/20 dark:text-rose-300 cursor-pointer"
                                    >
                                        Reject
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="openApproveModal({{ $step->id }}, '{{ addslashes($title) }}')"
                                        class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-theme-xs hover:bg-emerald-700 cursor-pointer"
                                    >
                                        Approve
                                    </button>
                                </div>
                            </div>

                            {{-- Preview View (if approvable supports preview) --}}
                            @if ($approvable && method_exists($approvable, 'getApprovalPreview') && ($preview = $approvable->getApprovalPreview()))
                                <div class="mt-4 p-3.5 rounded-xl bg-gray-50/70 border border-gray-100 dark:bg-gray-800/40 dark:border-gray-800">
                                    @include($preview['view'], $preview['data'] ?? [])
                                </div>
                            @endif
                        </div>
                    @endforeach

                @elseif ($activeTab === 'history')
                    {{-- HISTORY TAB ITEMS --}}
                    @foreach ($this->rows as $step)
                        @php
                            $req = $step->request;
                            $approvable = $req?->approvable;
                            $title = ($approvable && method_exists($approvable, 'getApprovalTitle'))
                                ? $approvable->getApprovalTitle()
                                : ($approvable?->name ?? $approvable?->title ?? "Approval Request #{$req->id}");
                        @endphp
                        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03] flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    @if ($step->state->value === 'approved')
                                        <span class="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Approved by You
                                        </span>
                                    @elseif ($step->state->value === 'rejected')
                                        <span class="inline-flex items-center gap-1 rounded-md bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700 dark:bg-rose-500/10 dark:text-rose-400">
                                            <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span> Rejected by You
                                        </span>
                                    @endif
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        Action taken: {{ $step->action_at?->diffForHumans() ?? 'Completed' }}
                                    </span>
                                </div>
                                <h4 class="text-base font-semibold text-gray-900 dark:text-white">
                                    {{ $title }}
                                </h4>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    Requester: {{ $req->requestor?->name }} &bull; Step: {{ $step->display_label }}
                                </p>
                                @if ($step->notes)
                                    <div class="mt-2 text-xs bg-gray-50 dark:bg-gray-800/60 p-2 rounded-lg text-gray-600 dark:text-gray-300 italic">
                                        "{{ $step->notes }}"
                                    </div>
                                @endif
                            </div>

                            <div>
                                <a
                                    href="{{ route('approvals.show', $req->id) }}"
                                    wire:navigate
                                    class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-xs font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                                >
                                    View Full Request
                                </a>
                            </div>
                        </div>
                    @endforeach

                @else
                    {{-- MY SUBMITTED REQUESTS TAB --}}
                    @foreach ($this->rows as $req)
                        @php
                            $approvable = $req->approvable;
                            $title = ($approvable && method_exists($approvable, 'getApprovalTitle'))
                                ? $approvable->getApprovalTitle()
                                : ($approvable?->name ?? $approvable?->title ?? "Approval Request #{$req->id}");
                        @endphp
                        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03] flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    @if ($req->status->value === 'pending')
                                        <span class="inline-flex items-center rounded-md bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-500/10 dark:text-amber-400">
                                            In Progress
                                        </span>
                                    @elseif ($req->status->value === 'approved')
                                        <span class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">
                                            Fully Approved
                                        </span>
                                    @elseif ($req->status->value === 'rejected')
                                        <span class="inline-flex items-center rounded-md bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700 dark:bg-rose-500/10 dark:text-rose-400">
                                            Rejected
                                        </span>
                                    @elseif ($req->status->value === 'canceled')
                                        <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-700/40 dark:text-gray-400">
                                            Canceled
                                        </span>
                                    @endif
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        Submitted {{ $req->created_at->format('M d, Y') }}
                                    </span>
                                </div>
                                <h4 class="text-base font-semibold text-gray-900 dark:text-white">
                                    {{ $title }}
                                </h4>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    Company: {{ $req->company?->name }} &bull; Total Steps: {{ $req->steps->count() }}
                                </p>
                            </div>

                            <div class="flex items-center gap-2">
                                @if ($req->status->value === 'pending')
                                    <button
                                        type="button"
                                        wire:click="openCancelModal({{ $req->id }}, '{{ addslashes($title) }}')"
                                        class="inline-flex items-center justify-center rounded-lg border border-rose-300 bg-rose-50 px-3 py-2 text-xs font-medium text-rose-700 shadow-theme-xs hover:bg-rose-100 dark:border-rose-800 dark:bg-rose-900/20 dark:text-rose-300 cursor-pointer"
                                    >
                                        Cancel Request
                                    </button>
                                @endif
                                <a
                                    href="{{ route('approvals.show', $req->id) }}"
                                    wire:navigate
                                    class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-3.5 py-2 text-xs font-medium text-white shadow-theme-xs hover:bg-brand-600"
                                >
                                    Track Progress
                                </a>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>

            {{-- Pagination --}}
            <div class="mt-5">
                {{ $this->rows->links() }}
            </div>
        @else
            @include('livewire.datatables.empty', [
                'title' => 'No approval items found',
                'message' => $activeTab === 'inbox'
                    ? 'You have zero pending approvals waiting for your review.'
                    : ($activeTab === 'history' ? 'No historical approval decisions recorded.' : 'You have not submitted any approval requests yet.'),
            ])
        @endif
    </div>

    {{-- APPROVE MODAL --}}
    <div x-show="$wire.approveModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500/75 backdrop-blur-xs transition-opacity dark:bg-gray-900/80" @click="$wire.set('approveModalOpen', false)"></div>
            <span class="hidden sm:inline-block sm:h-screen sm:align-middle">&#8203;</span>
            <div class="relative inline-block transform overflow-hidden rounded-2xl bg-white p-6 text-left align-bottom shadow-xl transition-all dark:bg-gray-800 sm:my-8 sm:w-full sm:max-w-lg sm:align-middle">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Confirm Step Approval</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Approve: <strong class="text-gray-800 dark:text-white">{{ $selectedTitle }}</strong></p>

                <div class="mt-4">
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Approval Notes (Optional)</label>
                    <textarea
                        wire:model="approvalNotes"
                        rows="3"
                        placeholder="Add optional notes or instructions for the requester/next step..."
                        class="w-full rounded-lg border border-gray-300 bg-white p-3 text-xs text-gray-800 shadow-theme-xs focus:border-emerald-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    ></textarea>
                </div>

                <div class="mt-5 sm:flex sm:flex-row-reverse gap-2">
                    <button
                        type="button"
                        wire:click="approveStep"
                        class="inline-flex w-full justify-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-emerald-500 sm:w-auto cursor-pointer"
                    >
                        Confirm Approval
                    </button>
                    <button
                        type="button"
                        wire:click="$set('approveModalOpen', false)"
                        class="mt-3 inline-flex w-full justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 sm:mt-0 sm:w-auto cursor-pointer"
                    >
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- REJECT MODAL --}}
    <div x-show="$wire.rejectModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500/75 backdrop-blur-xs transition-opacity dark:bg-gray-900/80" @click="$wire.set('rejectModalOpen', false)"></div>
            <span class="hidden sm:inline-block sm:h-screen sm:align-middle">&#8203;</span>
            <div class="relative inline-block transform overflow-hidden rounded-2xl bg-white p-6 text-left align-bottom shadow-xl transition-all dark:bg-gray-800 sm:my-8 sm:w-full sm:max-w-lg sm:align-middle">
                <h3 class="text-base font-semibold text-rose-600 dark:text-rose-400">Reject Approval Request</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Rejecting: <strong class="text-gray-800 dark:text-white">{{ $selectedTitle }}</strong></p>

                <div class="mt-4">
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Reason for Rejection <span class="text-rose-500">*</span>
                    </label>
                    <textarea
                        wire:model="rejectionReason"
                        rows="3"
                        placeholder="Please provide a clear reason for rejecting this request..."
                        class="w-full rounded-lg border border-gray-300 bg-white p-3 text-xs text-gray-800 shadow-theme-xs focus:border-rose-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    ></textarea>
                    @error('rejectionReason')
                        <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mt-5 sm:flex sm:flex-row-reverse gap-2">
                    <button
                        type="button"
                        wire:click="rejectStep"
                        class="inline-flex w-full justify-center rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-rose-500 sm:w-auto cursor-pointer"
                    >
                        Confirm Rejection
                    </button>
                    <button
                        type="button"
                        wire:click="$set('rejectModalOpen', false)"
                        class="mt-3 inline-flex w-full justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 sm:mt-0 sm:w-auto cursor-pointer"
                    >
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- DELEGATE MODAL --}}
    <div x-show="$wire.delegateModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500/75 backdrop-blur-xs transition-opacity dark:bg-gray-900/80" @click="$wire.set('delegateModalOpen', false)"></div>
            <span class="hidden sm:inline-block sm:h-screen sm:align-middle">&#8203;</span>
            <div class="relative inline-block transform overflow-hidden rounded-2xl bg-white p-6 text-left align-bottom shadow-xl transition-all dark:bg-gray-800 sm:my-8 sm:w-full sm:max-w-lg sm:align-middle">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Delegate Approval Step</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Reassign this step to a colleague: <strong class="text-gray-800 dark:text-white">{{ $selectedTitle }}</strong></p>

                <div class="mt-4">
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Select New Approver <span class="text-rose-500">*</span>
                    </label>
                    <select
                        wire:model="delegateUserId"
                        class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-xs text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    >
                        <option value="">Choose User...</option>
                        @foreach ($this->companyUsers as $user)
                            <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                        @endforeach
                    </select>
                    @error('delegateUserId')
                        <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mt-5 sm:flex sm:flex-row-reverse gap-2">
                    <button
                        type="button"
                        wire:click="delegateStep"
                        class="inline-flex w-full justify-center rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-brand-500 sm:w-auto cursor-pointer"
                    >
                        Confirm Delegation
                    </button>
                    <button
                        type="button"
                        wire:click="$set('delegateModalOpen', false)"
                        class="mt-3 inline-flex w-full justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 sm:mt-0 sm:w-auto cursor-pointer"
                    >
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- CANCEL MODAL --}}
    <div x-show="$wire.cancelModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500/75 backdrop-blur-xs transition-opacity dark:bg-gray-900/80" @click="$wire.set('cancelModalOpen', false)"></div>
            <span class="hidden sm:inline-block sm:h-screen sm:align-middle">&#8203;</span>
            <div class="relative inline-block transform overflow-hidden rounded-2xl bg-white p-6 text-left align-bottom shadow-xl transition-all dark:bg-gray-800 sm:my-8 sm:w-full sm:max-w-lg sm:align-middle">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Cancel Approval Request</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                    Are you sure you want to cancel <strong class="text-gray-800 dark:text-white">{{ $selectedTitle }}</strong>?
                    All pending approvers will be dismissed.
                </p>

                <div class="mt-5 sm:flex sm:flex-row-reverse gap-2">
                    <button
                        type="button"
                        wire:click="cancelRequest"
                        class="inline-flex w-full justify-center rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-rose-500 sm:w-auto cursor-pointer"
                    >
                        Yes, Cancel Request
                    </button>
                    <button
                        type="button"
                        wire:click="$set('cancelModalOpen', false)"
                        class="mt-3 inline-flex w-full justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 sm:mt-0 sm:w-auto cursor-pointer"
                    >
                        Nevermind
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
