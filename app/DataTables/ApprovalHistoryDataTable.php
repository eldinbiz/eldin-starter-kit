<?php

declare(strict_types=1);

namespace App\DataTables;

use App\Models\Approval\ApprovalRequest;
use Illuminate\Database\Eloquent\Builder;

class ApprovalHistoryDataTable extends DataTable
{
    public function query(): Builder
    {
        $user = auth()->user();
        $company = $user?->getActiveCompany();

        return ApprovalRequest::with(['requestor', 'approvable', 'steps' => fn ($q) => $q->where('approver_id', $user?->id)->orWhere('delegated_from_id', $user?->id)])
            ->where('company_id', $company?->id)
            ->whereHas('steps', function ($q) use ($user): void {
                $q->where('approver_id', $user?->id)
                    ->orWhere('delegated_from_id', $user?->id);
            });
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
            Column::make('status')
                ->label('Status'),
            Column::make('user_role')
                ->label('Your Role'),
            Column::make('user_action')
                ->label('Your Action'),
            Column::make('created_at')
                ->label('Date')
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
                        $sub->whereHas('requestor', fn ($sq) => $sq->where('name', 'like', "%{$value}%"))
                            ->orWhereHas('approvable', fn ($sq) => $sq->where('id', 'like', "%{$value}%"));
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

    public function renderRow(mixed $row): string
    {
        /** @var ApprovalRequest $row */
        return view('approvals._history_row', [
            'request' => $row,
            'currentUser' => auth()->user(),
        ])->render();
    }

    public function renderMobileRow(mixed $row): string
    {
        /** @var ApprovalRequest $row */
        return view('approvals._history_mobile_row', [
            'request' => $row,
            'currentUser' => auth()->user(),
        ])->render();
    }
}
