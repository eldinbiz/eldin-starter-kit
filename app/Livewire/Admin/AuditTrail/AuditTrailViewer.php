<?php

declare(strict_types=1);

namespace App\Livewire\Admin\AuditTrail;

use App\Livewire\DataTables\DataTableComponent;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Computed;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditTrailViewer extends DataTableComponent
{
    public string $sortField = 'occurred_at';
    public string $sortDirection = 'desc';

    // Modals & Inspection State
    public bool $diffModalOpen = false;
    public ?int $selectedLogId = null;
    public ?array $selectedLogDetails = null;

    public function query(): Builder
    {
        // Dummy Builder because DataTableComponent expects a Builder, or we wrap with a custom paginate()
        // We will override rows computed property directly for optimal DB table querying.
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

    public function render()
    {
        return view('livewire.admin.audit-trail.audit-trail-viewer');
    }
}
