<?php

declare(strict_types=1);

namespace App\DataTables;

use App\Models\Approval\ApprovalRequestStep;
use Illuminate\Database\Eloquent\Builder;

class ApprovalInboxDataTable extends DataTable
{
    public function query(): Builder
    {
        $user = auth()->user();
        $company = $user?->getActiveCompany();

        return ApprovalRequestStep::with('request.requestor', 'request.approvable')
            ->where('approver_id', $user?->id)
            ->whereHas('request', function ($query) use ($company): void {
                $query->where('company_id', $company?->id)
                    ->where('status', 'pending');
            })
            ->pending();
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
                        $sub->where('step_label', 'like', "%{$value}%")
                            ->orWhereHas('request.requestor', fn ($sq) => $sq->where('name', 'like', "%{$value}%"))
                            ->orWhereHas('request.approvable', fn ($sq) => $sq->where('id', 'like', "%{$value}%"));
                    });
                }),

            Filter::make('submitted_from')
                ->type('date')
                ->label('Submitted From')
                ->apply(fn (Builder $q, string $v) => $q->where('created_at', '>=', $v)),
        ];
    }

    public function rowView(): string
    {
        return 'approvals._inbox_row';
    }

    public function mobileRowView(): string
    {
        return 'approvals._inbox_mobile_row';
    }

    public function rowViewData(mixed $row): array
    {
        return [
            'step' => $row,
        ];
    }
}
