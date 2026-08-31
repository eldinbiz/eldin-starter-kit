<?php

use App\Livewire\DataTables\WithDataTable;
use App\Models\Approval\WorkflowTemplate;
use App\Models\User;
use App\Services\ApprovalEngine;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Modules\PurchaseRequests\Models\PurchaseRequest;

new class extends Component
{
    use WithDataTable;

    public ?int $submitModalPrId = null;
    public ?int $selectedTemplateId = null;
    public bool $submitModalOpen = false;

    public function query(): Builder
    {
        /** @var User|null $user */
        $user = auth()->user();
        $company = $user?->getActiveCompany();

        if (! $company) {
            return PurchaseRequest::query()->whereRaw('1 = 0');
        }

        return PurchaseRequest::query()
            ->where('company_id', $company->id)
            ->with(['requester', 'approvalRequests']);
    }

    protected function applySearch(Builder $query): void
    {
        if ($this->search === '') {
            return;
        }

        $query->where(function (Builder $q): void {
            $q->where('title', 'like', "%{$this->search}%")
                ->orWhere('pr_number', 'like', "%{$this->search}%")
                ->orWhere('department', 'like', "%{$this->search}%")
                ->orWhereHas('requester', function (Builder $uq): void {
                    $uq->where('name', 'like', "%{$this->search}%");
                });
        });
    }

    protected function applyFilters(Builder $query): void
    {
        if (! empty($this->filterValues['status'])) {
            $query->where('status', $this->filterValues['status']);
        }

        if (! empty($this->filterValues['department'])) {
            $query->where('department', $this->filterValues['department']);
        }
    }

    public function openSubmitModal(int $prId): void
    {
        $this->submitModalPrId = $prId;
        $this->selectedTemplateId = null;
        $this->submitModalOpen = true;
    }

    public function submitDraft(ApprovalEngine $engine): void
    {
        $this->validate([
            'selectedTemplateId' => ['required', 'integer', 'exists:approval_workflow_templates,id'],
        ]);

        if (! $this->submitModalPrId) {
            return;
        }

        /** @var User $user */
        $user = auth()->user();
        $pr = PurchaseRequest::findOrFail($this->submitModalPrId);

        if ($pr->requester_id !== $user->id || $pr->status !== 'draft') {
            $this->dispatch('notify', message: 'Only draft requests can be submitted.', type: 'error');
            $this->submitModalOpen = false;
            return;
        }

        $template = WorkflowTemplate::findOrFail($this->selectedTemplateId);
        $engine->submit($pr, $template);
        $pr->update(['status' => 'pending_approval']);

        $this->submitModalOpen = false;
        $this->submitModalPrId = null;
        $this->dispatch('notify', message: "Purchase Request {$pr->pr_number} submitted for approval.", type: 'success');
    }

    public function deleteDraft(int $prId): void
    {
        /** @var User $user */
        $user = auth()->user();
        $pr = PurchaseRequest::findOrFail($prId);

        if ($pr->requester_id !== $user->id && ! $user->hasPlatformAccess()) {
            abort(403);
        }

        if ($pr->status !== 'draft') {
            $this->dispatch('notify', message: 'Only draft requests can be deleted.', type: 'error');
            return;
        }

        $pr->delete();
        $this->dispatch('notify', message: 'Purchase request draft deleted.', type: 'info');
    }

    #[Computed]
    public function templates(): Collection
    {
        /** @var User|null $user */
        $user = auth()->user();
        $company = $user?->getActiveCompany();

        return $company
            ? WorkflowTemplate::availableFor($company)
                ->where('is_active', true)
                ->where(fn ($q) => $q->where('approvable_type', PurchaseRequest::class)->orWhereNull('approvable_type'))
                ->get()
            : collect();
    }
};
?>

<div class="space-y-6">
    {{-- Header & Controls --}}
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
                placeholder="Search by PR #, title, requester, department..."
                class="h-10 w-full rounded-lg border border-gray-300 bg-white pl-10 pr-9 text-sm text-gray-800 placeholder-gray-400 focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-white"
            />
        </div>

        <div class="flex items-center gap-3">
            <select
                wire:model.live="filterValues.status"
                class="h-10 rounded-lg border border-gray-300 bg-white px-3 text-xs text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-white"
            >
                <option value="">All Statuses</option>
                <option value="draft">Draft</option>
                <option value="pending_approval">Pending Approval</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
                <option value="canceled">Canceled</option>
            </select>

            <select
                wire:model.live="filterValues.department"
                class="h-10 rounded-lg border border-gray-300 bg-white px-3 text-xs text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-white"
            >
                <option value="">All Departments</option>
                <option value="IT">IT</option>
                <option value="Finance">Finance</option>
                <option value="Procurement">Procurement</option>
                <option value="HR">HR</option>
                <option value="Operations">Operations</option>
            </select>

            <a
                href="{{ route('purchase-requests.create') }}"
                wire:navigate
                class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2.5 text-xs font-semibold text-white shadow-theme-xs hover:bg-brand-600"
            >
                + New Purchase Request
            </a>
        </div>
    </div>

    {{-- Loading State --}}
    <div wire:loading.delay.shortest class="w-full">
        @include('livewire.datatables.loading', ['columns' => 6, 'rows' => 5])
    </div>

    {{-- Table Area --}}
    <div wire:loading.remove.delay.shortest>
        @if ($this->rows->isNotEmpty())
            <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.02]">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800 text-left text-sm">
                    <thead class="bg-gray-50/75 dark:bg-white/[0.02] text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">
                        <tr>
                            <th class="px-5 py-3">PR Number</th>
                            <th class="px-5 py-3">Title &amp; Department</th>
                            <th class="px-5 py-3">Requester</th>
                            <th class="px-5 py-3">Amount</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800 text-gray-800 dark:text-gray-200">
                        @foreach ($this->rows as $pr)
                            @php
                                $user = auth()->user();
                                $isOwner = $pr->requester_id === $user?->id;
                            @endphp
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-white/[0.01] transition-colors">
                                <td class="px-5 py-3.5 whitespace-nowrap font-mono text-xs font-semibold text-brand-600 dark:text-brand-400">
                                    <a href="{{ route('purchase-requests.show', $pr->id) }}" wire:navigate class="hover:underline">
                                        {{ $pr->pr_number }}
                                    </a>
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="font-medium text-gray-900 dark:text-white">{{ $pr->title }}</div>
                                    <div class="text-xs text-gray-400">{{ $pr->department }} &bull; {{ $pr->created_at->format('M d, Y') }}</div>
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap text-xs">
                                    <div class="font-medium text-gray-800 dark:text-gray-200">{{ $pr->requester?->name }}</div>
                                    <div class="text-gray-400">{{ $pr->requester?->email }}</div>
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-white">
                                    ${{ number_format((float) $pr->total_amount, 2) }}
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    @if ($pr->status === 'draft')
                                        <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700 dark:bg-gray-700/50 dark:text-gray-300">
                                            Draft
                                        </span>
                                    @elseif ($pr->status === 'pending_approval')
                                        <span class="inline-flex items-center gap-1 rounded-md bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-500/10 dark:text-amber-400">
                                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse"></span> Pending Approval
                                        </span>
                                    @elseif ($pr->status === 'approved')
                                        <span class="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Approved
                                        </span>
                                    @elseif ($pr->status === 'rejected')
                                        <span class="inline-flex items-center gap-1 rounded-md bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700 dark:bg-rose-500/10 dark:text-rose-400">
                                            <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span> Rejected
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                                            {{ ucfirst($pr->status) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap text-right text-xs space-x-1">
                                    <a
                                        href="{{ route('purchase-requests.show', $pr->id) }}"
                                        wire:navigate
                                        class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-2.5 py-1 text-xs font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                                    >
                                        View
                                    </a>

                                    @if ($pr->status === 'draft' && $isOwner)
                                        <button
                                            type="button"
                                            wire:click="openSubmitModal({{ $pr->id }})"
                                            class="inline-flex items-center justify-center rounded-md bg-brand-500 px-2.5 py-1 text-xs font-semibold text-white shadow-theme-xs hover:bg-brand-600 cursor-pointer"
                                        >
                                            Submit
                                        </button>
                                        <button
                                            type="button"
                                            wire:click="deleteDraft({{ $pr->id }})"
                                            wire:confirm="Are you sure you want to delete this draft purchase request?"
                                            class="inline-flex items-center justify-center rounded-md border border-rose-300 bg-rose-50 px-2 py-1 text-xs font-medium text-rose-700 shadow-theme-xs hover:bg-rose-100 dark:border-rose-800 dark:bg-rose-900/20 dark:text-rose-300 cursor-pointer"
                                        >
                                            Delete
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-5">
                {{ $this->rows->links() }}
            </div>
        @else
            @include('livewire.datatables.empty', [
                'title' => 'No Purchase Requests Found',
                'message' => 'No purchase requests match the active filter criteria or none have been submitted.',
            ])
        @endif
    </div>

    {{-- SUBMIT DRAFT MODAL --}}
    <div x-show="$wire.submitModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500/75 backdrop-blur-xs transition-opacity dark:bg-gray-900/80" @click="$wire.set('submitModalOpen', false)"></div>
            <span class="hidden sm:inline-block sm:h-screen sm:align-middle">&#8203;</span>
            <div class="relative inline-block transform overflow-hidden rounded-2xl bg-white p-6 text-left align-bottom shadow-xl transition-all dark:bg-gray-800 sm:my-8 sm:w-full sm:max-w-lg sm:align-middle">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Submit Request for Approval</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Select an approval workflow template to route this purchase request.</p>

                <div class="mt-4">
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Approval Template <span class="text-rose-500">*</span>
                    </label>
                    <select
                        wire:model="selectedTemplateId"
                        class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-xs text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    >
                        <option value="">Select a Workflow Template...</option>
                        @foreach ($this->templates as $t)
                            <option value="{{ $t->id }}">{{ $t->name }} ({{ ucfirst($t->scope->value) }})</option>
                        @endforeach
                    </select>
                    @error('selectedTemplateId')
                        <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mt-5 sm:flex sm:flex-row-reverse gap-2">
                    <button
                        type="button"
                        wire:click="submitDraft"
                        class="inline-flex w-full justify-center rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-brand-500 sm:w-auto cursor-pointer"
                    >
                        Submit Request
                    </button>
                    <button
                        type="button"
                        wire:click="$set('submitModalOpen', false)"
                        class="mt-3 inline-flex w-full justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 sm:mt-0 sm:w-auto cursor-pointer"
                    >
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
