<?php

use App\Livewire\DataTables\WithDataTable;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

new class extends Component
{
    use WithDataTable;

    public string $sortField = 'occurred_at';
    public string $sortDirection = 'desc';

    // Modals & Inspection State
    public bool $diffModalOpen = false;
    public ?int $selectedLogId = null;
    public ?array $selectedLogDetails = null;

    public function query(): Builder
    {
        return User::query()->whereRaw('1 = 0');
    }

    #[Computed]
    public function rows(): LengthAwarePaginator
    {
        if (! Schema::hasTable('audit_log')) {
            return new LengthAwarePaginator([], 0, $this->perPage);
        }

        $query = DB::table('audit_log');

        // Search query
        if ($this->search !== '') {
            $term = "%{$this->search}%";
            $query->where(function ($q) use ($term): void {
                $q->where('actor_label', 'like', $term)
                    ->orWhere('auditable_type', 'like', $term)
                    ->orWhere('correlation_id', 'like', $term)
                    ->orWhere('event', 'like', $term);
            });
        }

        // Filters
        if (! empty($this->filterValues['event'])) {
            $query->where('event', $this->filterValues['event']);
        }

        if (! empty($this->filterValues['auditable_type'])) {
            $query->where('auditable_type', 'like', "%{$this->filterValues['auditable_type']}%");
        }

        if (! empty($this->filterValues['correlation_id'])) {
            $query->where('correlation_id', $this->filterValues['correlation_id']);
        }

        if (! empty($this->filterValues['date_from'])) {
            $query->where('occurred_at', '>=', Carbon::parse($this->filterValues['date_from'])->startOfDay());
        }

        if (! empty($this->filterValues['date_to'])) {
            $query->where('occurred_at', '<=', Carbon::parse($this->filterValues['date_to'])->endOfDay());
        }

        // Sorting
        $allowedSorts = ['occurred_at', 'event', 'auditable_type', 'actor_label'];
        $sortField = in_array($this->sortField, $allowedSorts, true) ? $this->sortField : 'occurred_at';
        $sortDir = $this->sortDirection === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortField, $sortDir);

        return $query->paginate($this->perPage);
    }

    public function openDiffModal(int $logId): void
    {
        $log = DB::table('audit_log')->where('id', $logId)->first();
        if (! $log) {
            return;
        }

        $changes = is_string($log->changes ?? null)
            ? json_decode($log->changes, true)
            : ($log->changes ?? []);

        $this->selectedLogId = $logId;
        $this->selectedLogDetails = [
            'id' => $log->id,
            'event' => $log->event,
            'auditable_type' => $log->auditable_type,
            'auditable_id' => $log->auditable_id,
            'actor_label' => $log->actor_label ?? 'System',
            'correlation_id' => $log->correlation_id ?? null,
            'occurred_at' => $log->occurred_at,
            'changes' => $changes,
        ];
        $this->diffModalOpen = true;
    }

    public function filterByCorrelation(string $correlationId): void
    {
        $this->filterValues['correlation_id'] = $correlationId;
        $this->diffModalOpen = false;
        $this->resetPage();
    }

    public function exportCsv(): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="audit_logs_' . date('Y-m-d_His') . '.csv"',
        ];

        return response()->stream(function (): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Occurred At', 'Event', 'Auditable Type', 'Auditable ID', 'Actor', 'Correlation ID']);

            if (Schema::hasTable('audit_log')) {
                DB::table('audit_log')->orderBy('occurred_at', 'desc')->chunk(500, function ($records) use ($handle): void {
                    foreach ($records as $row) {
                        fputcsv($handle, [
                            $row->id,
                            $row->occurred_at,
                            $row->event,
                            $row->auditable_type,
                            $row->auditable_id,
                            $row->actor_label ?? 'System',
                            $row->correlation_id ?? '',
                        ]);
                    }
                });
            }

            fclose($handle);
        }, 200, $headers);
    }

    #[Computed]
    public function eventOptions(): array
    {
        return [
            'created' => 'Created',
            'updated' => 'Updated',
            'deleted' => 'Deleted',
            'restored' => 'Restored',
            'custom' => 'Custom Action',
        ];
    }

    #[Computed]
    public function auditableTypeOptions(): array
    {
        return [
            'User' => 'User',
            'Role' => 'Role',
            'Company' => 'Company',
            'WorkflowTemplate' => 'Workflow Template',
            'ApprovalRequest' => 'Approval Request',
            'PurchaseRequest' => 'Purchase Request',
            'Setting' => 'Setting',
            'PersonalAccessToken' => 'API Token',
        ];
    }
};
?>

<div class="w-full space-y-6">
    {{-- Header Controls (Search, Filters, Export) --}}
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
                placeholder="Search actor, entity, event, correlation..."
                class="h-10 w-full rounded-lg border border-gray-300 bg-white pl-10 pr-9 text-sm text-gray-800 placeholder-gray-400 focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-white"
            />
        </div>

        <div class="flex items-center gap-2">
            <button
                type="button"
                wire:click="toggleFilterPanel"
                class="inline-flex items-center gap-2 h-10 px-3.5 rounded-lg border text-sm font-medium transition cursor-pointer {{ $this->filterPanelOpen || $this->activeFilterCount() > 0 ? 'border-brand-500 bg-brand-50 text-brand-600 dark:border-brand-500/30 dark:bg-brand-500/10 dark:text-brand-400' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300' }}"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z" />
                </svg>
                <span>Filters</span>
                @if ($this->activeFilterCount() > 0)
                    <span class="inline-flex items-center justify-center h-5 min-w-[20px] px-1 rounded-full text-xs font-semibold bg-brand-500 text-white">
                        {{ $this->activeFilterCount() }}
                    </span>
                @endif
            </button>

            <button
                type="button"
                wire:click="exportCsv"
                class="inline-flex items-center gap-1.5 h-10 px-3.5 rounded-lg border border-gray-300 bg-white text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 cursor-pointer"
            >
                <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                <span>Export CSV</span>
            </button>
        </div>
    </div>

    {{-- Filter Drawer --}}
    @if ($this->filterPanelOpen)
        <div class="p-4 rounded-xl border border-gray-200 bg-gray-50/50 dark:border-gray-800 dark:bg-white/[0.02] grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Event Action</label>
                <select
                    wire:model.live="filterValues.event"
                    class="h-9 w-full rounded-lg border border-gray-300 bg-white px-3 text-xs text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                >
                    <option value="">All Events</option>
                    @foreach ($this->eventOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Auditable Entity</label>
                <select
                    wire:model.live="filterValues.auditable_type"
                    class="h-9 w-full rounded-lg border border-gray-300 bg-white px-3 text-xs text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                >
                    <option value="">All Entity Types</option>
                    @foreach ($this->auditableTypeOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Date From</label>
                <input
                    type="date"
                    wire:model.live="filterValues.date_from"
                    class="h-9 w-full rounded-lg border border-gray-300 bg-white px-3 text-xs text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                />
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Date To</label>
                <input
                    type="date"
                    wire:model.live="filterValues.date_to"
                    class="h-9 w-full rounded-lg border border-gray-300 bg-white px-3 text-xs text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                />
            </div>
        </div>
    @endif

    {{-- Active Filter Chips --}}
    @include('livewire.datatables.active-chips')

    {{-- Loading Skeleton --}}
    <div wire:loading.delay.shortest class="w-full">
        @include('livewire.datatables.loading', ['columns' => 6, 'rows' => 5])
    </div>

    {{-- Audit Log Table Content --}}
    <div wire:loading.remove.delay.shortest>
        @if ($this->rows->isNotEmpty())
            <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800 text-left text-sm">
                    <thead class="bg-gray-50/75 dark:bg-white/[0.02] text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        <tr>
                            @include('livewire.datatables.th', ['field' => 'occurred_at', 'label' => 'Timestamp', 'sortable' => true])
                            @include('livewire.datatables.th', ['field' => 'event', 'label' => 'Event', 'sortable' => true])
                            @include('livewire.datatables.th', ['field' => 'auditable_type', 'label' => 'Entity Affected', 'sortable' => true])
                            @include('livewire.datatables.th', ['field' => 'actor_label', 'label' => 'Actor / User', 'sortable' => true])
                            <th class="px-5 py-3">Correlation ID</th>
                            <th class="px-5 py-3 text-right">Details</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800 text-gray-800 dark:text-gray-200">
                        @foreach ($this->rows as $log)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-white/[0.01] transition-colors">
                                <td class="px-5 py-3.5 whitespace-nowrap text-xs text-gray-500 dark:text-gray-400">
                                    {{ \Illuminate\Support\Carbon::parse($log->occurred_at)->format('M d, Y H:i:s') }}
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    @if ($log->event === 'created')
                                        <span class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-400">
                                            Created
                                        </span>
                                    @elseif ($log->event === 'updated')
                                        <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-0.5 text-xs font-semibold text-blue-700 ring-1 ring-inset ring-blue-600/20 dark:bg-blue-500/10 dark:text-blue-400">
                                            Updated
                                        </span>
                                    @elseif ($log->event === 'deleted')
                                        <span class="inline-flex items-center rounded-md bg-rose-50 px-2 py-0.5 text-xs font-semibold text-rose-700 ring-1 ring-inset ring-rose-600/20 dark:bg-rose-500/10 dark:text-rose-400">
                                            Deleted
                                        </span>
                                    @elseif ($log->event === 'restored')
                                        <span class="inline-flex items-center rounded-md bg-purple-50 px-2 py-0.5 text-xs font-semibold text-purple-700 ring-1 ring-inset ring-purple-600/20 dark:bg-purple-500/10 dark:text-purple-400">
                                            Restored
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                            {{ ucfirst($log->event) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    <div class="font-medium text-gray-900 dark:text-white text-xs">
                                        {{ class_basename($log->auditable_type ?? 'Entity') }} #{{ $log->auditable_id }}
                                    </div>
                                    <div class="text-[10px] text-gray-400 truncate max-w-xs">
                                        {{ $log->auditable_type }}
                                    </div>
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap text-xs">
                                    <div class="font-medium text-gray-800 dark:text-gray-200">
                                        {{ $log->actor_label ?: 'System / CLI' }}
                                    </div>
                                    @if ($log->actor_id)
                                        <div class="text-[10px] text-gray-400">ID: {{ $log->actor_id }}</div>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap font-mono text-[11px] text-gray-500 dark:text-gray-400">
                                    @if (!empty($log->correlation_id))
                                        <button
                                            type="button"
                                            wire:click="filterByCorrelation('{{ $log->correlation_id }}')"
                                            class="text-brand-600 hover:underline dark:text-brand-400 truncate max-w-[140px] inline-block cursor-pointer"
                                            title="Filter all events in this request chain"
                                        >
                                            {{ substr($log->correlation_id, 0, 12) }}...
                                        </button>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap text-right text-xs">
                                    <button
                                        type="button"
                                        wire:click="openDiffModal({{ $log->id }})"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 cursor-pointer"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                        </svg>
                                        <span>Diff</span>
                                    </button>
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
                'title' => 'No audit records found',
                'message' => 'No matching audit trail events were recorded for the selected search or filter criteria.',
            ])
        @endif
    </div>

    {{-- JSON Diff Inspection Modal --}}
    <div
        x-show="$wire.diffModalOpen"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto"
        aria-labelledby="modal-title"
        role="dialog"
        aria-modal="true"
    >
        <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
            <div
                class="fixed inset-0 bg-gray-500/75 backdrop-blur-xs transition-opacity dark:bg-gray-900/80"
                @click="$wire.set('diffModalOpen', false)"
            ></div>

            <span class="hidden sm:inline-block sm:h-screen sm:align-middle">&#8203;</span>

            <div class="relative inline-block transform overflow-hidden rounded-2xl bg-white p-6 text-left align-bottom shadow-xl transition-all dark:bg-gray-800 sm:my-8 sm:w-full sm:max-w-2xl sm:align-middle">
                @if ($selectedLogDetails)
                    <div class="flex items-center justify-between border-b border-gray-100 pb-3 dark:border-gray-700">
                        <div>
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white" id="modal-title">
                                Audit Diff: {{ class_basename($selectedLogDetails['auditable_type'] ?? 'Entity') }} #{{ $selectedLogDetails['auditable_id'] }}
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                Event: <strong>{{ ucfirst($selectedLogDetails['event']) }}</strong> &bull; Actor: {{ $selectedLogDetails['actor_label'] }} &bull; {{ $selectedLogDetails['occurred_at'] }}
                            </p>
                        </div>
                        <button
                            type="button"
                            wire:click="$set('diffModalOpen', false)"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 cursor-pointer"
                        >
                            ✕
                        </button>
                    </div>

                    {{-- Payload Viewer --}}
                    <div class="mt-4 space-y-4 max-h-[60vh] overflow-y-auto">
                        @if (!empty($selectedLogDetails['correlation_id']))
                            <div class="flex items-center justify-between p-2.5 rounded-lg bg-gray-50 border border-gray-200 dark:bg-gray-900/50 dark:border-gray-700 text-xs">
                                <span class="font-mono text-gray-500 dark:text-gray-400 truncate max-w-sm">Correlation ID: {{ $selectedLogDetails['correlation_id'] }}</span>
                                <button
                                    type="button"
                                    wire:click="filterByCorrelation('{{ $selectedLogDetails['correlation_id'] }}')"
                                    class="text-xs font-semibold text-brand-600 hover:underline dark:text-brand-400 cursor-pointer"
                                >
                                    Filter Chain &rarr;
                                </button>
                            </div>
                        @endif

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Recorded Changes Payload</label>
                            <div class="rounded-xl bg-gray-900 p-4 text-xs font-mono text-emerald-400 overflow-x-auto">
                                <pre>{{ json_encode($selectedLogDetails['changes'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 sm:flex sm:flex-row-reverse">
                        <button
                            type="button"
                            wire:click="$set('diffModalOpen', false)"
                            class="inline-flex w-full justify-center rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-brand-500 sm:w-auto cursor-pointer"
                        >
                            Done
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
