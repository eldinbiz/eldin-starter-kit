<?php

declare(strict_types=1);

namespace App\DataTables;

use App\Models\Approval\ApprovalRequestStep;
use Illuminate\Database\Eloquent\Builder;

class ApprovalManagementDataTable extends DataTable
{
    public function query(): Builder
    {
        $company = auth()->user()?->getActiveCompany();

        return ApprovalRequestStep::with('request.requestor', 'request.approvable', 'approver')
            ->whereHas('request', fn ($q) => $q->where('company_id', $company?->id));
    }

    public function columns(): array
    {
        return [
            Column::make('request_title')
                ->label('Request')
                ->searchable(),
            Column::make('requestor_name')
                ->label('Requestor')
                ->searchable(),
            Column::make('step_label')
                ->label('Step'),
            Column::make('approver_name')
                ->label('Approver')
                ->searchable(),
            Column::make('state')
                ->label('Status'),
            Column::make('created_at')
                ->label('Submitted')
                ->sortable(),
            Column::make('actions')
                ->label('Actions')
                ->align('right'),
        ];
    }

    public function filters(): array
    {
        return [
            Filter::make('search')
                ->type('text')
                ->label('Search')
                ->placeholder('Search by title, name...')
                ->apply(function (Builder $q, string $value): void {
                    $q->where(function (Builder $sub) use ($value): void {
                        $sub->where('step_label', 'like', "%{$value}%")
                            ->orWhereHas('request.requestor', fn ($sq) => $sq->where('name', 'like', "%{$value}%"))
                            ->orWhereHas('request.approvable', fn ($sq) => $sq->where('id', 'like', "%{$value}%"))
                            ->orWhereHas('approver', fn ($sq) => $sq->where('name', 'like', "%{$value}%"));
                    });
                }),

            Filter::make('step_state')
                ->type('select')
                ->label('Step State')
                ->options([
                    'pending' => 'Pending',
                    'approved' => 'Approved',
                    'rejected' => 'Rejected',
                    'skipped' => 'Skipped',
                    'awaiting_previous' => 'Awaiting Previous',
                ])
                ->apply(fn (Builder $q, string $v) => $q->where('state', $v)),

            Filter::make('request_status')
                ->type('select')
                ->label('Request Status')
                ->options([
                    'pending' => 'Pending',
                    'approved' => 'Approved',
                    'rejected' => 'Rejected',
                    'canceled' => 'Canceled',
                ])
                ->apply(fn (Builder $q, string $v) => $q->whereHas('request', fn ($sq) => $sq->where('status', $v))),

            Filter::make('submitted_from')
                ->type('date')
                ->label('Submitted From')
                ->apply(fn (Builder $q, string $v) => $q->whereHas('request', fn ($sq) => $sq->where('submitted_at', '>=', $v))),

            Filter::make('submitted_to')
                ->type('date')
                ->label('Submitted To')
                ->apply(fn (Builder $q, string $v) => $q->whereHas('request', fn ($sq) => $sq->where('submitted_at', '<=', $v))),
        ];
    }

    public function renderRow(mixed $row): string
    {
        /** @var ApprovalRequestStep $row */
        return view('admin.approval-management._row', [
            'step' => $row,
            'companyUsers' => $this->request->attributes->get('_company_users', collect()),
        ])->render();
    }

    public function renderMobileRow(mixed $row): string
    {
        /** @var ApprovalRequestStep $row */
        return view('admin.approval-management._mobile_row', [
            'step' => $row,
            'companyUsers' => $this->request->attributes->get('_company_users', collect()),
        ])->render();
    }
}
