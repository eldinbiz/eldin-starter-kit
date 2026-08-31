<?php

use App\Actions\Approvals\ForceDeleteWorkflowTemplateAction;
use App\Actions\Approvals\ToggleWorkflowTemplateAction;
use App\Actions\Approvals\TrashWorkflowTemplateAction;
use App\Enums\TemplateScope;
use App\Helpers\ApprovableTypes;
use App\Livewire\DataTables\WithDataTable;
use App\Models\Approval\WorkflowTemplate;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

new class extends Component
{
    use WithDataTable;

    public ?int $selectedCompanyId = null;

    public function mount(): void
    {
        $this->sortField = 'created_at';
        $this->sortDirection = 'desc';

        if (request()->has('trashed') && request()->boolean('trashed')) {
            $this->filterValues['trashed'] = true;
        }

        if (request()->filled('company_id')) {
            $this->selectedCompanyId = (int) request()->query('company_id');
            $this->filterValues['company_id'] = $this->selectedCompanyId;
        }
    }

    public function query(): Builder
    {
        /** @var User|null $user */
        $user = auth()->user();
        $isSysAdmin = $user?->hasPlatformAccess() ?? false;
        $company = $user?->getActiveCompany();

        $showTrashed = ! empty($this->filterValues['trashed']);

        $query = WorkflowTemplate::query()
            ->withCount('groups')
            ->with(['company', 'groups.steps']);

        if ($showTrashed) {
            $query->onlyTrashed();
        }

        if (! $isSysAdmin) {
            if ($company && ! $showTrashed) {
                $query->availableFor($company);
            } elseif ($company) {
                $query->where('company_id', $company->id);
            }
        }

        return $query;
    }

    protected function applySearch(Builder $query): void
    {
        if ($this->search === '') {
            return;
        }

        $query->where(function (Builder $q): void {
            $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('description', 'like', "%{$this->search}%");
        });
    }

    protected function applyFilters(Builder $query): void
    {
        if (! empty($this->filterValues['type'])) {
            $type = $this->filterValues['type'];
            if ($type === '__null__') {
                $query->whereNull('approvable_type');
            } else {
                $query->where('approvable_type', $type);
            }
        }

        if (isset($this->filterValues['is_active']) && $this->filterValues['is_active'] !== '') {
            $query->where('is_active', (bool) $this->filterValues['is_active']);
        }

        if (! empty($this->filterValues['scope'])) {
            $query->where('scope', $this->filterValues['scope']);
        }

        if (! empty($this->filterValues['company_id'])) {
            $query->where('company_id', (int) $this->filterValues['company_id']);
        }
    }

    public function toggleStatus(int $templateId, ToggleWorkflowTemplateAction $action): void
    {
        $template = WorkflowTemplate::withTrashed()->findOrFail($templateId);
        $this->authorizeTemplate($template);

        if ($template->is_active) {
            $action->disable($template);
            $this->dispatch('notify', message: 'Workflow template disabled.', type: 'info');
        } else {
            $action->enable($template);
            $this->dispatch('notify', message: 'Workflow template enabled.', type: 'success');
        }
    }

    public function trash(int $templateId, TrashWorkflowTemplateAction $action): void
    {
        $template = WorkflowTemplate::findOrFail($templateId);
        $this->authorizeTemplate($template);

        $action->execute($template);
        $this->dispatch('notify', message: 'Workflow template moved to trash.', type: 'warning');
    }

    public function restore(int $templateId, ToggleWorkflowTemplateAction $action): void
    {
        $template = WorkflowTemplate::onlyTrashed()->findOrFail($templateId);
        $this->authorizeTemplate($template);

        $template->restore();
        $this->dispatch('notify', message: 'Workflow template restored successfully.', type: 'success');
    }

    public function forceDelete(int $templateId, ForceDeleteWorkflowTemplateAction $action): void
    {
        $template = WorkflowTemplate::onlyTrashed()->findOrFail($templateId);
        $this->authorizeTemplate($template);

        $action->execute($template);
        $this->dispatch('notify', message: 'Workflow template permanently deleted.', type: 'error');
    }

    private function authorizeTemplate(WorkflowTemplate $template): void
    {
        /** @var User|null $user */
        $user = auth()->user();
        if ($user?->hasPlatformAccess()) {
            return;
        }

        $company = $user?->getActiveCompany();
        if (! $company || $template->company_id !== $company->id) {
            abort(403, 'Unauthorized to modify this workflow template.');
        }
    }

    #[Computed]
    public function approvableTypeOptions(): array
    {
        return ApprovableTypes::options();
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

<div x-data="{ deleteModalOpen: false, targetId: null, targetName: '', actionType: '' }" class="w-full">
    {{-- Header with Search, Filter toggle, and Actions --}}
    @include('livewire.datatables.header', [
        'placeholder' => 'Search template name, description...',
        'hasFilters' => true,
        'actionSlot' => true,
    ])

    {{-- Filter Drawer --}}
    @if ($this->filterPanelOpen)
        <div class="mb-4 p-4 rounded-xl border border-gray-200 bg-gray-50/50 dark:border-gray-800 dark:bg-white/[0.02] grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Applies To (Module)</label>
                <select
                    wire:model.live="filterValues.type"
                    class="h-9 w-full rounded-lg border border-gray-300 bg-white px-3 text-xs text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                >
                    <option value="">All Approvable Modules</option>
                    <option value="__null__">Universal / Any Module</option>
                    @foreach ($this->approvableTypeOptions as $class => $label)
                        <option value="{{ $class }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                <select
                    wire:model.live="filterValues.is_active"
                    class="h-9 w-full rounded-lg border border-gray-300 bg-white px-3 text-xs text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                >
                    <option value="">All Statuses</option>
                    <option value="1">Active</option>
                    <option value="0">Disabled</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Scope</label>
                <select
                    wire:model.live="filterValues.scope"
                    class="h-9 w-full rounded-lg border border-gray-300 bg-white px-3 text-xs text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                >
                    <option value="">All Scopes</option>
                    <option value="this_company">This Company</option>
                    <option value="subsidiaries">This Company & Subsidiaries</option>
                    <option value="global">Global (All Companies)</option>
                </select>
            </div>

            @if (count($this->companies) > 0)
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Company</label>
                    <select
                        wire:model.live="filterValues.company_id"
                        class="h-9 w-full rounded-lg border border-gray-300 bg-white px-3 text-xs text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    >
                        <option value="">All Companies</option>
                        @foreach ($this->companies as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>
    @endif

    {{-- Active Filter Chips --}}
    @include('livewire.datatables.active-chips')

    {{-- Loading Skeleton --}}
    <div wire:loading.delay.shortest class="w-full">
        @include('livewire.datatables.loading', ['columns' => 6, 'rows' => 5])
    </div>

    {{-- Table Content --}}
    <div wire:loading.remove.delay.shortest>
        @if ($this->rows->isNotEmpty())
            <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800 text-left text-sm">
                    <thead class="bg-gray-50/75 dark:bg-white/[0.02] text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        <tr>
                            @include('livewire.datatables.th', ['field' => 'name', 'label' => 'Template Name', 'sortable' => true])
                            @include('livewire.datatables.th', ['field' => 'approvable_type', 'label' => 'Applies To', 'sortable' => true])
                            @include('livewire.datatables.th', ['field' => 'groups_count', 'label' => 'Groups & Steps', 'sortable' => false])
                            @include('livewire.datatables.th', ['field' => 'scope', 'label' => 'Scope', 'sortable' => true])
                            @include('livewire.datatables.th', ['field' => 'is_active', 'label' => 'Status', 'sortable' => true])
                            @include('livewire.datatables.th', ['field' => 'created_at', 'label' => 'Created', 'sortable' => true])
                            <th class="px-5 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800 text-gray-800 dark:text-gray-200">
                        @foreach ($this->rows as $template)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-white/[0.01] transition-colors">
                                <td class="px-5 py-3.5">
                                    <div class="font-medium text-gray-800 dark:text-white">
                                        {{ $template->name }}
                                    </div>
                                    @if ($template->description)
                                        <div class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-xs">
                                            {{ $template->description }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    @if ($template->approvable_type)
                                        <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10 dark:bg-blue-400/10 dark:text-blue-400 dark:ring-blue-400/30">
                                            {{ $this->approvableTypeOptions[$template->approvable_type] ?? class_basename($template->approvable_type) }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-md bg-purple-50 px-2 py-1 text-xs font-medium text-purple-700 ring-1 ring-inset ring-purple-700/10 dark:bg-purple-400/10 dark:text-purple-400 dark:ring-purple-400/30">
                                            Universal / Any
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    <div class="text-xs">
                                        <span class="font-medium text-gray-800 dark:text-white">{{ $template->groups_count }} {{ Str::plural('Group', $template->groups_count) }}</span>
                                        <span class="text-gray-400">({{ $template->groups->sum(fn ($g) => $g->steps->count()) }} steps)</span>
                                    </div>
                                    <div class="flex items-center gap-1 mt-1">
                                        @foreach ($template->groups as $group)
                                            <span class="inline-block px-1.5 py-0.5 rounded text-[10px] {{ $group->isSequential() ? 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300' }}">
                                                {{ $group->isSequential() ? 'Seq' : 'Par (' . ($group->min_approvals ?? 'All') . ')' }}
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    <span class="text-xs text-gray-600 dark:text-gray-400">
                                        {{ $template->scope->label($template->company) }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    @if ($template->trashed())
                                        <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600 dark:bg-gray-700/30 dark:text-gray-400">
                                            Trashed
                                        </span>
                                    @elseif ($template->is_active)
                                        <button
                                            type="button"
                                            wire:click="toggleStatus({{ $template->id }})"
                                            class="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/20 hover:bg-emerald-100 dark:bg-emerald-500/10 dark:text-emerald-400 dark:ring-emerald-500/20 cursor-pointer"
                                        >
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                            Active
                                        </button>
                                    @else
                                        <button
                                            type="button"
                                            wire:click="toggleStatus({{ $template->id }})"
                                            class="inline-flex items-center gap-1 rounded-md bg-rose-50 px-2 py-1 text-xs font-medium text-rose-700 ring-1 ring-inset ring-rose-600/20 hover:bg-rose-100 dark:bg-rose-500/10 dark:text-rose-400 dark:ring-rose-500/20 cursor-pointer"
                                        >
                                            <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                                            Disabled
                                        </button>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap text-xs text-gray-500 dark:text-gray-400">
                                    {{ $template->created_at->format('M d, Y') }}
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap text-right text-xs font-medium space-x-2">
                                    @if ($template->trashed())
                                        <button
                                            type="button"
                                            wire:click="restore({{ $template->id }})"
                                            class="text-brand-600 hover:text-brand-700 dark:text-brand-400 cursor-pointer"
                                        >
                                            Restore
                                        </button>
                                        <button
                                            type="button"
                                            @click="deleteModalOpen = true; targetId = {{ $template->id }}; targetName = '{{ addslashes($template->name) }}'; actionType = 'forceDelete'"
                                            class="text-rose-600 hover:text-rose-700 dark:text-rose-400 cursor-pointer"
                                        >
                                            Delete Permanently
                                        </button>
                                    @else
                                        <a
                                            href="{{ route('admin.approval-workflows.edit', $template->id) }}"
                                            wire:navigate
                                            class="text-brand-600 hover:text-brand-700 dark:text-brand-400"
                                        >
                                            Edit
                                        </a>
                                        <button
                                            type="button"
                                            @click="deleteModalOpen = true; targetId = {{ $template->id }}; targetName = '{{ addslashes($template->name) }}'; actionType = 'trash'"
                                            class="text-amber-600 hover:text-amber-700 dark:text-amber-400 cursor-pointer"
                                        >
                                            Trash
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="mt-4">
                {{ $this->rows->links() }}
            </div>
        @else
            @include('livewire.datatables.empty', [
                'title' => 'No workflow templates found',
                'message' => 'Try adjusting your search criteria or create a new approval workflow template.',
            ])
        @endif
    </div>

    {{-- Confirmation Modal --}}
    <div
        x-show="deleteModalOpen"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto"
        aria-labelledby="modal-title"
        role="dialog"
        aria-modal="true"
    >
        <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
            <div
                x-show="deleteModalOpen"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-gray-500/75 backdrop-blur-xs transition-opacity dark:bg-gray-900/80"
                @click="deleteModalOpen = false"
            ></div>

            <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>

            <div
                x-show="deleteModalOpen"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="relative inline-block transform overflow-hidden rounded-2xl bg-white p-6 text-left align-bottom shadow-xl transition-all dark:bg-gray-800 sm:my-8 sm:w-full sm:max-w-lg sm:align-middle"
            >
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-rose-100 dark:bg-rose-900/30 sm:mx-0 sm:h-10 sm:w-10">
                        <svg class="h-6 w-6 text-rose-600 dark:text-rose-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                    </div>
                    <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white" id="modal-title">
                            <span x-text="actionType === 'forceDelete' ? 'Permanently Delete Template' : 'Trash Workflow Template'"></span>
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Are you sure you want to proceed with <strong x-text="targetName"></strong>?
                                <span x-show="actionType === 'forceDelete'" class="block mt-1 text-rose-500 font-medium">This action cannot be undone.</span>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse gap-2">
                    <button
                        type="button"
                        @click="
                            if (actionType === 'forceDelete') {
                                $wire.forceDelete(targetId);
                            } else {
                                $wire.trash(targetId);
                            }
                            deleteModalOpen = false;
                        "
                        class="inline-flex w-full justify-center rounded-lg bg-rose-600 px-3 py-2 text-sm font-semibold text-white shadow-xs hover:bg-rose-500 sm:w-auto cursor-pointer"
                    >
                        Confirm
                    </button>
                    <button
                        type="button"
                        @click="deleteModalOpen = false"
                        class="mt-3 inline-flex w-full justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 sm:mt-0 sm:w-auto cursor-pointer"
                    >
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
