<?php

declare(strict_types=1);

use App\Actions\Approvals\DelegateApprovalStepAction;
use App\Actions\Approvals\SkipApprovalStepAction;
use App\Contracts\Approvable;
use App\Enums\ApprovalStatus;
use App\Enums\StepState;
use App\Livewire\DataTables\WithDataTable;
use App\Models\Approval\ApprovalRequest;
use App\Models\Approval\ApprovalRequestStep;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    use WithDataTable;

    public bool $delegateModalOpen = false;
    public bool $skipModalOpen = false;
    public ?int $selectedStepId = null;
    public ?int $selectedApproverId = null;
    public string $stepLabel = '';
    public string $approvalTitle = '';
    public string $currentApproverName = '';
    public ?int $currentApproverId = null;

    public function mount(): void
    {
        $this->sortField = 'created_at';
        $this->sortDirection = 'desc';

        if (request()->filled('step_state')) {
            $this->filterValues['step_state'] = (string) request()->query('step_state');
        }

        if (request()->filled('request_status')) {
            $this->filterValues['request_status'] = (string) request()->query('request_status');
        }

        if (request()->filled('company_id')) {
            $this->filterValues['company_id'] = (int) request()->query('company_id');
        }
    }

    public function query(): Builder
    {
        /** @var User|null $user */
        $user = auth()->user();
        $isSysAdmin = $user?->hasPlatformAccess() ?? false;
        $company = $user?->getActiveCompany();

        $query = ApprovalRequestStep::query()
            ->with(['request.requestor', 'request.approvable', 'request.company', 'approver.companies']);

        if (! $isSysAdmin && $company) {
            $query->whereHas('request', fn (Builder $q) => $q->where('company_id', $company->id));
        }

        return $query;
    }

    protected function applySearch(Builder $query): void
    {
        if ($this->search === '') {
            return;
        }

        $query->where(function (Builder $sub): void {
            $sub->where('step_label', 'like', "%{$this->search}%")
                ->orWhereHas('request.requestor', fn ($sq) => $sq->where('name', 'like', "%{$this->search}%"))
                ->orWhereHas('approver', fn ($sq) => $sq->where('name', 'like', "%{$this->search}%"))
                ->orWhereHas('request.approvable', function ($sq) {
                    $sq->where('id', 'like', "%{$this->search}%");
                });
        });
    }

    protected function applyFilters(Builder $query): void
    {
        if (! empty($this->filterValues['step_state'])) {
            $query->where('state', $this->filterValues['step_state']);
        }

        if (! empty($this->filterValues['request_status'])) {
            $query->whereHas('request', fn (Builder $q) => $q->where('status', $this->filterValues['request_status']));
        }

        if (! empty($this->filterValues['submitted_from'])) {
            $query->whereHas('request', fn (Builder $q) => $q->where('submitted_at', '>=', $this->filterValues['submitted_from']));
        }

        if (! empty($this->filterValues['submitted_to'])) {
            $query->whereHas('request', fn (Builder $q) => $q->where('submitted_at', '<=', $this->filterValues['submitted_to']));
        }

        if (! empty($this->filterValues['company_id'])) {
            $query->whereHas('request', fn (Builder $q) => $q->where('company_id', (int) $this->filterValues['company_id']));
        }
    }

    public function openDelegateModal(int $stepId): void
    {
        $step = ApprovalRequestStep::with(['request.requestor', 'request.approvable', 'approver'])->findOrFail($stepId);
        $this->authorizeStep($step);

        $this->selectedStepId = $step->id;
        $this->stepLabel = $step->display_label;
        $this->approvalTitle = $step->request->approvable instanceof Approvable
            ? $step->request->approvable->getApprovalTitle()
            : 'Approval Request #' . $step->request->id;
        $this->currentApproverName = $step->approver?->name ?? 'None';
        $this->currentApproverId = $step->approver_id;
        $this->selectedApproverId = null;
        $this->delegateModalOpen = true;
    }

    public function openSkipModal(int $stepId): void
    {
        $step = ApprovalRequestStep::with(['request'])->findOrFail($stepId);
        $this->authorizeStep($step);

        $this->selectedStepId = $step->id;
        $this->stepLabel = $step->display_label;
        $this->skipModalOpen = true;
    }

    public function delegate(DelegateApprovalStepAction $action): void
    {
        if (! $this->selectedStepId || ! $this->selectedApproverId) {
            return;
        }

        $step = ApprovalRequestStep::findOrFail($this->selectedStepId);
        $this->authorizeStep($step);

        /** @var User $user */
        $user = auth()->user();
        $action->execute($step, (int) $this->selectedApproverId, $user);

        $this->delegateModalOpen = false;
        $this->selectedStepId = null;
        $this->selectedApproverId = null;

        $this->dispatch('notify', message: 'Step delegated successfully.', type: 'success');
    }

    public function skip(SkipApprovalStepAction $action): void
    {
        if (! $this->selectedStepId) {
            return;
        }

        $step = ApprovalRequestStep::findOrFail($this->selectedStepId);
        $this->authorizeStep($step);

        /** @var User $user */
        $user = auth()->user();
        $action->execute($step, $user);

        $this->skipModalOpen = false;
        $this->selectedStepId = null;

        $this->dispatch('notify', message: 'Step skipped successfully.', type: 'success');
    }

    private function authorizeStep(ApprovalRequestStep $step): void
    {
        /** @var User|null $user */
        $user = auth()->user();
        if ($user?->hasPlatformAccess()) {
            return;
        }

        $company = $user?->getActiveCompany();
        if (! $company || $step->request->company_id !== $company->id) {
            abort(403, 'Unauthorized to manage this approval step.');
        }
    }

    #[Computed]
    public function companyUsers(): Collection
    {
        /** @var User|null $user */
        $user = auth()->user();
        $company = $user?->getActiveCompany();

        return $company ? $company->users()->orderBy('name')->get() : collect();
    }

    #[Computed]
    public function companies(): array
    {
        /** @var User|null $user */
        $user = auth()->user();
        if ($user?->hasPlatformAccess()) {
            return Company::orderBy('name')->pluck('name', 'id')->all();
        }

        return [];
    }
};
?>

<div x-data="{ filterPanelOpen: $wire.entangle('filterPanelOpen'), delegateModalOpen: $wire.entangle('delegateModalOpen'), skipModalOpen: $wire.entangle('skipModalOpen') }" class="w-full">
    {{-- Top Controls (Search, Filters toggle, Per-page) --}}
    @include('livewire.datatables.header', [
        'placeholder' => 'Search by title, requestor, approver...',
        'hasFilters' => true,
    ])

    {{-- Collapsible Filter Panel --}}
    <div
        x-show="filterPanelOpen"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-2"
        class="mb-4 rounded-xl border border-gray-200 bg-gray-50/75 p-4 dark:border-gray-800 dark:bg-gray-800/40"
    >
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            {{-- Step State Filter --}}
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    Step State
                </label>
                <select
                    wire:model.live="filterValues.step_state"
                    class="h-9.5 w-full rounded-lg border border-gray-300 bg-white px-3 text-xs text-gray-700 focus:border-brand-500 focus:outline-hidden focus:ring-1 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                >
                    <option value="">All States</option>
                    <option value="pending">Pending</option>
                    <option value="awaiting_previous">Awaiting Previous</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                    <option value="skipped">Skipped</option>
                </select>
            </div>

            {{-- Request Status Filter --}}
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    Request Status
                </label>
                <select
                    wire:model.live="filterValues.request_status"
                    class="h-9.5 w-full rounded-lg border border-gray-300 bg-white px-3 text-xs text-gray-700 focus:border-brand-500 focus:outline-hidden focus:ring-1 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                >
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                    <option value="canceled">Canceled</option>
                </select>
            </div>

            {{-- Submitted From Date --}}
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    Submitted From
                </label>
                <input
                    type="date"
                    wire:model.live="filterValues.submitted_from"
                    class="h-9.5 w-full rounded-lg border border-gray-300 bg-white px-3 text-xs text-gray-700 focus:border-brand-500 focus:outline-hidden focus:ring-1 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                />
            </div>

            {{-- Submitted To Date --}}
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    Submitted To
                </label>
                <input
                    type="date"
                    wire:model.live="filterValues.submitted_to"
                    class="h-9.5 w-full rounded-lg border border-gray-300 bg-white px-3 text-xs text-gray-700 focus:border-brand-500 focus:outline-hidden focus:ring-1 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                />
            </div>

            {{-- Company Filter (Platform Admin only) --}}
            @if (count($this->companies) > 0)
                <div class="sm:col-span-2 lg:col-span-4">
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        Company
                    </label>
                    <select
                        wire:model.live="filterValues.company_id"
                        class="h-9.5 w-full max-w-xs rounded-lg border border-gray-300 bg-white px-3 text-xs text-gray-700 focus:border-brand-500 focus:outline-hidden focus:ring-1 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                    >
                        <option value="">All Companies</option>
                        @foreach ($this->companies as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>

        {{-- Filter Actions --}}
        <div class="mt-4 flex items-center justify-end gap-2 border-t border-gray-200/60 pt-3 dark:border-gray-700/60">
            @if ($this->activeFilterCount() > 0)
                <button
                    type="button"
                    wire:click="resetFilters"
                    class="rounded-lg px-3 py-1.5 text-xs font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 cursor-pointer"
                >
                    Reset Filters
                </button>
            @endif
            <button
                type="button"
                @click="filterPanelOpen = false"
                class="rounded-lg bg-gray-200 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 cursor-pointer"
            >
                Close
            </button>
        </div>
    </div>

    {{-- Active Filter Chips --}}
    @include('livewire.datatables.active-chips', [
        'filterLabels' => [
            'step_state' => 'Step State',
            'request_status' => 'Request Status',
            'submitted_from' => 'Submitted From',
            'submitted_to' => 'Submitted To',
            'company_id' => 'Company',
        ],
    ])

    {{-- Loading Skeleton --}}
    <div wire:loading.delay.shortest wire:target="search, sortBy, gotoPage, nextPage, previousPage, perPage, filterValues, resetFilters, removeFilter" class="w-full">
        @include('livewire.datatables.loading', ['columns' => 7, 'rows' => 5])
    </div>

    {{-- Table Content --}}
    <div wire:loading.remove.delay.shortest wire:target="search, sortBy, gotoPage, nextPage, previousPage, perPage, filterValues, resetFilters, removeFilter">
        @if ($this->rows->isNotEmpty())
            {{-- Desktop Table View --}}
            <div class="hidden sm:block overflow-x-auto rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800 text-left text-sm">
                    <thead class="bg-gray-50/75 dark:bg-white/[0.02] text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        <tr>
                            @include('livewire.datatables.th', ['field' => '', 'label' => 'Request', 'sortable' => false])
                            @include('livewire.datatables.th', ['field' => '', 'label' => 'Requestor', 'sortable' => false])
                            @include('livewire.datatables.th', ['field' => 'step_label', 'label' => 'Step', 'sortable' => true])
                            @include('livewire.datatables.th', ['field' => '', 'label' => 'Approver', 'sortable' => false])
                            @include('livewire.datatables.th', ['field' => 'state', 'label' => 'Status', 'sortable' => true])
                            @include('livewire.datatables.th', ['field' => 'created_at', 'label' => 'Submitted', 'sortable' => true])
                            <th class="px-5 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800 text-gray-800 dark:text-gray-200">
                        @foreach ($this->rows as $step)
                            @php
                                $isActionable = ($step->state === StepState::Pending || $step->state === StepState::AwaitingPrevious)
                                    && $step->request->isPending();
                                $canDelegate = $isActionable && $step->state === StepState::Pending;
                                $canSkip = $isActionable;
                                $approvalTitle = $step->request->approvable instanceof Approvable
                                    ? $step->request->approvable->getApprovalTitle()
                                    : 'Approval Request #' . $step->request->id;
                            @endphp
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-white/[0.01] transition-colors">
                                <td class="px-5 py-3.5">
                                    <a href="{{ route('approvals.show', $step->request->id) }}" wire:navigate class="font-medium text-gray-800 text-theme-sm hover:text-brand-500 dark:text-white/90 dark:hover:text-brand-400">
                                        {{ $approvalTitle }}
                                    </a>
                                </td>
                                <td class="px-5 py-3.5">
                                    <p class="text-gray-600 dark:text-gray-400 text-theme-sm">{{ $step->request->requestor->name }}</p>
                                </td>
                                <td class="px-5 py-3.5">
                                    <p class="text-gray-700 dark:text-gray-300 text-theme-sm font-medium">{{ $step->display_label }}</p>
                                </td>
                                <td class="px-5 py-3.5">
                                    <p class="text-gray-600 dark:text-gray-400 text-theme-sm">{{ $step->approver?->name ?? '—' }}</p>
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    @if ($step->state === StepState::Pending)
                                        @if ($step->request->isPending())
                                            <span class="inline-flex items-center rounded-full bg-warning-50 px-2.5 py-0.5 text-xs font-medium text-warning-600 dark:bg-warning-500/10 dark:text-warning-500">Pending</span>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-500 dark:bg-gray-800 dark:text-gray-400">Pending</span>
                                        @endif
                                    @elseif ($step->state === StepState::AwaitingPrevious)
                                        <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-600 dark:bg-blue-500/10 dark:text-blue-500">Awaiting</span>
                                    @elseif ($step->state === StepState::Approved)
                                        <span class="inline-flex items-center rounded-full bg-success-50 px-2.5 py-0.5 text-xs font-medium text-success-600 dark:bg-success-500/10 dark:text-success-500">Approved</span>
                                    @elseif ($step->state === StepState::Rejected)
                                        <span class="inline-flex items-center rounded-full bg-error-50 px-2.5 py-0.5 text-xs font-medium text-error-600 dark:bg-error-500/10 dark:text-error-400">Rejected</span>
                                    @elseif ($step->state === StepState::Skipped)
                                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-500 dark:bg-gray-800 dark:text-gray-400">Skipped</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    <p class="text-gray-500 dark:text-gray-400 text-xs">@formatDate($step->created_at)</p>
                                </td>
                                <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                    @if ($canDelegate || $canSkip)
                                        <div class="inline-flex items-center gap-2">
                                            @if ($canDelegate)
                                                <button
                                                    type="button"
                                                    wire:click="openDelegateModal({{ $step->id }})"
                                                    class="inline-flex items-center justify-center rounded-lg bg-brand-50 px-3 py-1.5 text-xs font-medium text-brand-600 hover:bg-brand-100 dark:bg-brand-500/10 dark:text-brand-400 dark:hover:bg-brand-500/20 cursor-pointer transition"
                                                >
                                                    Delegate
                                                </button>
                                            @endif

                                            @if ($canSkip)
                                                <button
                                                    type="button"
                                                    wire:click="openSkipModal({{ $step->id }})"
                                                    class="inline-flex items-center justify-center rounded-lg bg-error-50 px-3 py-1.5 text-xs font-medium text-error-600 hover:bg-error-100 dark:bg-error-500/10 dark:text-error-400 dark:hover:bg-error-500/20 cursor-pointer transition"
                                                >
                                                    Skip
                                                </button>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-gray-400 text-xs">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Mobile Cards View --}}
            <div class="sm:hidden flex flex-col gap-3">
                @foreach ($this->rows as $step)
                    @php
                        $isActionable = ($step->state === StepState::Pending || $step->state === StepState::AwaitingPrevious)
                            && $step->request->isPending();
                        $canDelegate = $isActionable && $step->state === StepState::Pending;
                        $canSkip = $isActionable;
                        $approvalTitle = $step->request->approvable instanceof Approvable
                            ? $step->request->approvable->getApprovalTitle()
                            : 'Approval Request #' . $step->request->id;
                    @endphp
                    <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
                        <div class="flex items-center gap-3 mb-3">
                            <span class="h-10 w-10 rounded-full bg-brand-50 flex items-center justify-center text-brand-600 font-bold text-xs shrink-0 dark:bg-brand-500/10 dark:text-brand-400">
                                @if ($step->request->approvable instanceof Approvable)
                                    {{ strtoupper(substr($step->request->approvable->getApprovalTitle(), 0, 2)) }}
                                @else
                                    PR{{ $step->request->id }}
                                @endif
                            </span>
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('approvals.show', $step->request->id) }}" wire:navigate class="font-medium text-gray-800 text-theme-sm dark:text-white/90 truncate block hover:text-brand-500">
                                    {{ $approvalTitle }}
                                </a>
                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $step->request->requestor->name }}</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 mb-3">
                            <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">{{ $step->display_label }}</p>
                            <span class="text-xs text-gray-400 dark:text-gray-500">·</span>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $step->approver?->name ?? '—' }}</p>
                        </div>

                        <div class="flex items-center justify-between pt-3 border-t border-gray-100 dark:border-gray-800">
                            <div class="flex items-center gap-2">
                                @if ($step->state === StepState::Pending)
                                    @if ($step->request->isPending())
                                        <span class="inline-flex items-center rounded-full bg-warning-50 px-2 py-0.5 text-xs font-medium text-warning-600 dark:bg-warning-500/10 dark:text-warning-500">Pending</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500 dark:bg-gray-800 dark:text-gray-400">Pending</span>
                                    @endif
                                @elseif ($step->state === StepState::AwaitingPrevious)
                                    <span class="inline-flex items-center rounded-full bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-600 dark:bg-blue-500/10 dark:text-blue-500">Awaiting</span>
                                @elseif ($step->state === StepState::Approved)
                                    <span class="inline-flex items-center rounded-full bg-success-50 px-2 py-0.5 text-xs font-medium text-success-600 dark:bg-success-500/10 dark:text-success-500">Approved</span>
                                @elseif ($step->state === StepState::Rejected)
                                    <span class="inline-flex items-center rounded-full bg-error-50 px-2 py-0.5 text-xs font-medium text-error-600 dark:bg-error-500/10 dark:text-error-400">Rejected</span>
                                @elseif ($step->state === StepState::Skipped)
                                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500 dark:bg-gray-800 dark:text-gray-400">Skipped</span>
                                @endif
                                <span class="text-xs text-gray-500 dark:text-gray-400">@formatDate($step->created_at)</span>
                            </div>

                            @if ($canDelegate || $canSkip)
                                <div class="flex items-center gap-2">
                                    @if ($canDelegate)
                                        <button
                                            type="button"
                                            wire:click="openDelegateModal({{ $step->id }})"
                                            class="inline-flex items-center justify-center rounded-lg bg-brand-50 px-3 py-1.5 text-xs font-medium text-brand-600 hover:bg-brand-100 dark:bg-brand-500/10 dark:text-brand-400 dark:hover:bg-brand-500/20 cursor-pointer"
                                        >
                                            Delegate
                                        </button>
                                    @endif

                                    @if ($canSkip)
                                        <button
                                            type="button"
                                            wire:click="openSkipModal({{ $step->id }})"
                                            class="inline-flex items-center justify-center rounded-lg bg-error-50 px-3 py-1.5 text-xs font-medium text-error-600 hover:bg-error-100 dark:bg-error-500/10 dark:text-error-400 dark:hover:bg-error-500/20 cursor-pointer"
                                        >
                                            Skip
                                        </button>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            <div class="mt-4">
                {{ $this->rows->links() }}
            </div>
        @else
            @include('livewire.datatables.empty', [
                'icon' => 'inbox',
                'title' => 'No approval steps found',
                'description' => $this->search !== '' || $this->activeFilterCount() > 0
                    ? 'Try adjusting your search query or removing active filters.'
                    : 'All approval workflows are running normally with no pending oversight actions required.',
            ])
        @endif
    </div>

    {{-- Reusable Delegate Modal --}}
    <div
        x-show="delegateModalOpen"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto"
        aria-labelledby="delegate-modal-title"
        role="dialog"
        aria-modal="true"
    >
        <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
            <div
                x-show="delegateModalOpen"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-gray-500/75 backdrop-blur-xs transition-opacity dark:bg-gray-900/80"
                @click="delegateModalOpen = false"
                aria-hidden="true"
            ></div>

            <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>

            <div
                x-show="delegateModalOpen"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="relative inline-block transform overflow-hidden rounded-2xl bg-white p-6 text-left align-bottom shadow-xl transition-all dark:bg-gray-900 sm:my-8 sm:w-full sm:max-w-md sm:align-middle"
            >
                <h4 class="text-lg font-semibold text-gray-800 dark:text-white/90" id="delegate-modal-title">
                    Reassign Approver
                </h4>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Reassign <span class="font-medium text-gray-700 dark:text-gray-300">"{{ $this->stepLabel }}"</span> — {{ $this->approvalTitle }}
                </p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Current approver: <span class="font-medium text-gray-700 dark:text-gray-300">{{ $this->currentApproverName }}</span>
                </p>

                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        New Approver
                    </label>
                    <select
                        wire:model.live="selectedApproverId"
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 focus:border-brand-500 focus:outline-hidden"
                    >
                        <option value="">Select user...</option>
                        @foreach ($this->companyUsers as $u)
                            @if ($u->id !== $this->currentApproverId)
                                <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                            @endif
                        @endforeach
                    </select>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button
                        type="button"
                        @click="delegateModalOpen = false"
                        class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        wire:click="delegate"
                        @if (! $this->selectedApproverId) disabled @endif
                        class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer transition"
                    >
                        Delegate
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Reusable Skip Modal --}}
    <div
        x-show="skipModalOpen"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto"
        aria-labelledby="skip-modal-title"
        role="dialog"
        aria-modal="true"
    >
        <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
            <div
                x-show="skipModalOpen"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-gray-500/75 backdrop-blur-xs transition-opacity dark:bg-gray-900/80"
                @click="skipModalOpen = false"
                aria-hidden="true"
            ></div>

            <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>

            <div
                x-show="skipModalOpen"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="relative inline-block transform overflow-hidden rounded-2xl bg-white p-6 text-left align-bottom shadow-xl transition-all dark:bg-gray-900 sm:my-8 sm:w-full sm:max-w-md sm:align-middle"
            >
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-error-100 dark:bg-error-900/30 sm:mx-0 sm:h-10 sm:w-10">
                        <svg class="h-6 w-6 text-error-600 dark:text-error-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                    </div>
                    <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                        <h4 class="text-lg font-semibold text-gray-800 dark:text-white/90" id="skip-modal-title">
                            Skip Step
                        </h4>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            Skip <span class="font-medium text-gray-700 dark:text-gray-300">"{{ $this->stepLabel }}"</span>?
                        </p>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            This will bypass the step and advance the workflow. This action cannot be reversed.
                        </p>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button
                        type="button"
                        @click="skipModalOpen = false"
                        class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        wire:click="skip"
                        class="inline-flex items-center justify-center rounded-lg bg-error-600 px-4 py-2 text-sm font-medium text-white hover:bg-error-500 cursor-pointer transition"
                    >
                        Skip Step
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
