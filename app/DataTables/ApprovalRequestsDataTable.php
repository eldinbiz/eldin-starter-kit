<?php

declare(strict_types=1);

namespace App\DataTables;

use App\Models\Approval\ApprovalRequest;
use Illuminate\Database\Eloquent\Builder;

class ApprovalRequestsDataTable extends DataTable
{
    public function query(): Builder
    {
        $user = auth()->user();
        $company = $user?->getActiveCompany();

        return ApprovalRequest::with(['steps.approver', 'approvable'])
            ->where('company_id', $company?->id)
            ->where('requestor_id', $user?->id);
    }

    public function columns(): array
    {
        return [
            Column::make('request_title')
                ->label('Request')
                ->searchable(),
            Column::make('status')
                ->label('Status'),
            Column::make('current_step')
                ->label('Current Step'),
            Column::make('created_at')
                ->label('Submitted')
                ->sortable(),
            Column::make('actions')
                ->label('Action')
                ->align('right'),
        ];
    }

    public function filters(): array
    {
        return [
            Filter::make('search')
                ->type('text')
                ->label('Search')
                ->placeholder('Search requests...')
                ->apply(function (Builder $q, string $value): void {
                    $q->where(function (Builder $sub) use ($value): void {
                        $sub->whereHas('approvable', fn ($sq) => $sq->where('id', 'like', "%{$value}%"));
                    });
                }),

            Filter::make('status')
                ->type('select')
                ->label('Status')
                ->options([
                    'pending' => 'Pending',
                    'approved' => 'Approved',
                    'rejected' => 'Rejected',
                    'canceled' => 'Canceled',
                ])
                ->apply(fn (Builder $q, string $v) => $q->where('status', $v)),

            Filter::make('submitted_from')
                ->type('date')
                ->label('Submitted From')
                ->apply(fn (Builder $q, string $v) => $q->where('created_at', '>=', $v)),
        ];
    }

    public function rowView(): string
    {
        return 'approvals._requests_row';
    }

    public function mobileRowView(): string
    {
        return 'approvals._requests_mobile_row';
    }

    public function rowViewData(mixed $row): array
    {
        return [
            'request' => $row,
        ];
    }
}
