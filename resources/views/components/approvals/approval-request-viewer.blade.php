<?php

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

new class extends Component
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
};
?>

@php
    $req = $this->approvalRequest;
    $approvable = $req->approvable;
    $title = ($approvable && method_exists($approvable, 'getApprovalTitle'))
        ? $approvable->getApprovalTitle()
        : ($approvable?->name ?? $approvable?->title ?? "Approval Request #{$req->id}");
    $user = auth()->user();
@endphp

<div class="space-y-6">
    {{-- Header & Quick Status Bar --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3 mb-1">
                <a
                    href="{{ route('approvals.inbox') }}"
                    wire:navigate
                    class="inline-flex items-center gap-1 text-xs font-medium text-gray-500 hover:text-brand-600 dark:text-gray-400 dark:hover:text-brand-400"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                    </svg>
                    <span>Back to Approvals</span>
                </a>

                {{-- Status Badge --}}
                @if ($req->status->value === 'pending')
                    <span class="inline-flex items-center gap-1 rounded-md bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-400">
                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse"></span> Pending Approval
                    </span>
                @elseif ($req->status->value === 'approved')
                    <span class="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-400">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Fully Approved
                    </span>
                @elseif ($req->status->value === 'rejected')
                    <span class="inline-flex items-center gap-1 rounded-md bg-rose-50 px-2 py-0.5 text-xs font-semibold text-rose-700 ring-1 ring-inset ring-rose-600/20 dark:bg-rose-500/10 dark:text-rose-400">
                        <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span> Rejected
                    </span>
                @elseif ($req->status->value === 'canceled')
                    <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-600 dark:bg-gray-700/40 dark:text-gray-400">
                        Canceled
                    </span>
                @endif
            </div>

            <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                {{ $title }}
            </h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                Requested by <strong class="text-gray-700 dark:text-gray-300">{{ $req->requestor?->name }}</strong> ({{ $req->requestor?->email }}) &bull; Company: {{ $req->company?->name }} &bull; Submitted {{ $req->created_at->format('M d, Y H:i') }}
            </p>
        </div>

        <div class="flex items-center gap-2">
            @if ($approvable && method_exists($approvable, 'getApprovalUrl'))
                <a
                    href="{{ $approvable->getApprovalUrl() }}"
                    class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-xs font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                >
                    View Original Document &rarr;
                </a>
            @endif

            @if ($req->status->value === 'pending' && $req->requestor_id === $user->id)
                <button
                    type="button"
                    wire:click="cancel"
                    wire:confirm="Are you sure you want to cancel this approval request?"
                    class="inline-flex items-center justify-center rounded-lg border border-rose-300 bg-rose-50 px-4 py-2 text-xs font-semibold text-rose-700 shadow-theme-xs hover:bg-rose-100 dark:border-rose-800 dark:bg-rose-900/20 dark:text-rose-300 cursor-pointer"
                >
                    Cancel Request
                </button>
            @endif
        </div>
    </div>

    {{-- Execution Timeline / Workflow Steps Table --}}
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03] md:p-6 space-y-4">
        <div class="flex items-center justify-between">
            <h4 class="text-sm font-semibold text-gray-800 dark:text-white uppercase tracking-wider text-xs">
                Workflow Progress &amp; Step States
            </h4>
        </div>

        @if ($req->steps->contains(fn ($s) => $s->approver_id === $user->id && $s->isPending() && $req->status->value === 'pending'))
            <div class="rounded-lg bg-amber-50 p-3 text-xs font-medium text-amber-800 dark:bg-amber-950/40 dark:text-amber-300">
                You are the current approver. Please review the details below and make your decision.
            </div>
        @endif

        <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.02]">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800 text-left text-sm">
                <thead class="bg-gray-50/75 dark:bg-white/[0.02] text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">
                    <tr>
                        <th class="px-5 py-3">Group</th>
                        <th class="px-5 py-3">Step / Role</th>
                        <th class="px-5 py-3">Assigned Approver</th>
                        <th class="px-5 py-3">State</th>
                        <th class="px-5 py-3">Decision / Notes</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800 text-gray-800 dark:text-gray-200">
                    @foreach ($req->steps as $step)
                        @php
                            $isCurrentApprover = $step->approver_id === $user->id;
                            $canAct = $isCurrentApprover && $step->isPending() && $req->status->value === 'pending';
                            $canResolve = $step->isAwaitingPrevious() && ($req->requestor_id === $user->id || $user->hasPlatformAccess());
                        @endphp
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-white/[0.01] transition-colors {{ $canAct ? 'bg-amber-50/20 dark:bg-amber-500/5' : '' }}">
                            <td class="px-5 py-3.5 whitespace-nowrap text-xs font-medium text-gray-600 dark:text-gray-400">
                                {{ $step->group_name ?: 'Group ' . ($step->order) }}
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap font-medium text-gray-900 dark:text-white">
                                {{ $step->display_label ?: 'Approval Step' }}
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap text-xs">
                                @if ($step->approver)
                                    <div class="font-medium text-gray-800 dark:text-gray-200">
                                        {{ $step->approver->name }}
                                        @if ($step->delegated_from_id)
                                            <span class="text-[10px] text-purple-600 dark:text-purple-400">(Delegated from {{ $step->delegatedFrom?->name }})</span>
                                        @endif
                                    </div>
                                    <div class="text-gray-400">{{ $step->approver->email }}</div>
                                @elseif ($step->isAwaitingPrevious())
                                    <span class="inline-flex items-center text-amber-600 dark:text-amber-400 italic">
                                        Awaiting previous step resolution
                                    </span>
                                @else
                                    <span class="text-gray-400 italic">Unassigned</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap">
                                @if ($step->state->value === 'approved')
                                    <span class="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Approved
                                    </span>
                                @elseif ($step->state->value === 'rejected')
                                    <span class="inline-flex items-center gap-1 rounded-md bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700 dark:bg-rose-500/10 dark:text-rose-400">
                                        <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span> Rejected
                                    </span>
                                @elseif ($step->state->value === 'pending')
                                    <span class="inline-flex items-center gap-1 rounded-md bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-500/10 dark:text-amber-400">
                                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse"></span> Pending
                                    </span>
                                @elseif ($step->state->value === 'awaiting_previous')
                                    <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                        Awaiting Prev
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                                        {{ ucfirst($step->state->value) }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-xs text-gray-600 dark:text-gray-300">
                                @if ($step->notes)
                                    <div class="italic">"{{ $step->notes }}"</div>
                                    @if ($step->action_at)
                                        <div class="text-[10px] text-gray-400 mt-0.5">{{ $step->action_at->format('M d, Y H:i') }}</div>
                                    @endif
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap text-right text-xs space-x-1">
                                @if ($canAct)
                                    <button
                                        type="button"
                                        wire:click="openApprove({{ $step->id }})"
                                        class="inline-flex items-center justify-center rounded-md bg-emerald-600 px-2.5 py-1 text-xs font-semibold text-white shadow-theme-xs hover:bg-emerald-700 cursor-pointer"
                                    >
                                        Approve
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="openReject({{ $step->id }})"
                                        class="inline-flex items-center justify-center rounded-md border border-rose-300 bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700 shadow-theme-xs hover:bg-rose-100 dark:border-rose-800 dark:bg-rose-900/20 dark:text-rose-300 cursor-pointer"
                                    >
                                        Reject
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="openDelegate({{ $step->id }})"
                                        class="inline-flex items-center justify-center rounded-md border border-purple-300 bg-purple-50 px-2.5 py-1 text-xs font-semibold text-purple-700 shadow-theme-xs hover:bg-purple-100 dark:border-purple-800 dark:bg-purple-900/20 dark:text-purple-300 cursor-pointer"
                                    >
                                        Delegate
                                    </button>
                                @elseif ($canResolve)
                                    <button
                                        type="button"
                                        wire:click="openResolve({{ $step->id }})"
                                        class="inline-flex items-center justify-center rounded-md bg-amber-600 px-2.5 py-1 text-xs font-semibold text-white shadow-theme-xs hover:bg-amber-700 cursor-pointer"
                                    >
                                        Resolve Approver
                                    </button>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Approvable Entity Preview --}}
    @if ($approvable && method_exists($approvable, 'getApprovalPreview') && ($preview = $approvable->getApprovalPreview()))
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03] md:p-6 space-y-3">
            <h4 class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                Entity Summary Preview
            </h4>
            <div class="p-4 rounded-xl bg-gray-50/70 border border-gray-100 dark:bg-gray-800/40 dark:border-gray-800">
                @include($preview['view'], $preview['data'] ?? [])
            </div>
        </div>
    @endif

    {{-- APPROVE MODAL --}}
    <div x-show="$wire.approveModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500/75 backdrop-blur-xs transition-opacity dark:bg-gray-900/80" @click="$wire.set('approveModalOpen', false)"></div>
            <span class="hidden sm:inline-block sm:h-screen sm:align-middle">&#8203;</span>
            <div class="relative inline-block transform overflow-hidden rounded-2xl bg-white p-6 text-left align-bottom shadow-xl transition-all dark:bg-gray-800 sm:my-8 sm:w-full sm:max-w-lg sm:align-middle">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Confirm Step Approval</h3>
                <div class="mt-4">
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Approval Notes (Optional)</label>
                    <textarea
                        wire:model="approvalNotes"
                        rows="3"
                        placeholder="Add optional comments or instructions..."
                        class="w-full rounded-lg border border-gray-300 bg-white p-3 text-xs text-gray-800 shadow-theme-xs focus:border-emerald-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    ></textarea>
                </div>
                <div class="mt-5 sm:flex sm:flex-row-reverse gap-2">
                    <button
                        type="button"
                        wire:click="approve"
                        class="inline-flex w-full justify-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-emerald-500 sm:w-auto cursor-pointer"
                    >
                        Approve Step
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
                <div class="mt-4">
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Reason for Rejection <span class="text-rose-500">*</span>
                    </label>
                    <textarea
                        wire:model="rejectionReason"
                        rows="3"
                        placeholder="State why this step/request is being rejected..."
                        class="w-full rounded-lg border border-gray-300 bg-white p-3 text-xs text-gray-800 shadow-theme-xs focus:border-rose-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    ></textarea>
                    @error('rejectionReason')
                        <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>
                <div class="mt-5 sm:flex sm:flex-row-reverse gap-2">
                    <button
                        type="button"
                        wire:click="reject"
                        class="inline-flex w-full justify-center rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-rose-500 sm:w-auto cursor-pointer"
                    >
                        Reject Request
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
                <div class="mt-4">
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Select New Approver <span class="text-rose-500">*</span>
                    </label>
                    <select
                        wire:model="delegateUserId"
                        class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-xs text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    >
                        <option value="">Choose User...</option>
                        @foreach ($this->companyUsers as $u)
                            <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                        @endforeach
                    </select>
                    @error('delegateUserId')
                        <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>
                <div class="mt-5 sm:flex sm:flex-row-reverse gap-2">
                    <button
                        type="button"
                        wire:click="delegate"
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

    {{-- RESOLVE PREVIOUS MANAGER MODAL --}}
    <div x-show="$wire.resolveModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500/75 backdrop-blur-xs transition-opacity dark:bg-gray-900/80" @click="$wire.set('resolveModalOpen', false)"></div>
            <span class="hidden sm:inline-block sm:h-screen sm:align-middle">&#8203;</span>
            <div class="relative inline-block transform overflow-hidden rounded-2xl bg-white p-6 text-left align-bottom shadow-xl transition-all dark:bg-gray-800 sm:my-8 sm:w-full sm:max-w-lg sm:align-middle">
                <h3 class="text-base font-semibold text-amber-700 dark:text-amber-400">Resolve Previous Approver's Manager</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    The previous approver had no manager in the company hierarchy. Manually assign an approver for this step.
                </p>
                <div class="mt-4">
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Select Approver <span class="text-rose-500">*</span>
                    </label>
                    <select
                        wire:model="resolveApproverId"
                        class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-xs text-gray-800 shadow-theme-xs focus:border-amber-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    >
                        <option value="">Choose Approver User...</option>
                        @foreach ($this->companyUsers as $u)
                            <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                        @endforeach
                    </select>
                    @error('resolveApproverId')
                        <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>
                <div class="mt-5 sm:flex sm:flex-row-reverse gap-2">
                    <button
                        type="button"
                        wire:click="resolvePreviousManager"
                        class="inline-flex w-full justify-center rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-amber-500 sm:w-auto cursor-pointer"
                    >
                        Resolve &amp; Assign
                    </button>
                    <button
                        type="button"
                        wire:click="$set('resolveModalOpen', false)"
                        class="mt-3 inline-flex w-full justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 sm:mt-0 sm:w-auto cursor-pointer"
                    >
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
