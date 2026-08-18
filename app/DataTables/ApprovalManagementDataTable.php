<?php

declare(strict_types=1);

namespace App\DataTables;

use App\Models\Approval\ApprovalRequestStep;
use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;

class ApprovalManagementDataTable extends DataTable
{
    public function query(): Builder
    {
        $user = auth()->user();
        $isSysAdmin = $user?->hasPlatformAccess() ?? false;
        $company = $user?->getActiveCompany();

        $query = ApprovalRequestStep::with('request.requestor', 'request.approvable', 'approver', 'request.company');

        if (! $isSysAdmin && $company) {
            $query->whereHas('request', fn ($q) => $q->where('company_id', $company->id));
        }

        return $query;
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

        if (auth()->user()?->hasPlatformAccess()) {
            $companyOptions = Company::orderBy('name')->pluck('name', 'id')->all();
            $filters[] = Filter::make('company_id')
                ->type('select')
                ->label('Company')
                ->options($companyOptions)
                ->apply(fn (Builder $q, string $v) => $q->whereHas('request', fn ($sq) => $sq->where('company_id', $v)));
        }

        return $filters;
    }

    public function bodyView(): string
    {
        return 'admin.approval-management._tbody';
    }

    public function mobileBodyView(): string
    {
        return 'admin.approval-management._mobile_body';
    }

    public function rowView(): string
    {
        return 'admin.approval-management._row';
    }

    public function mobileRowView(): string
    {
        return 'admin.approval-management._mobile_row';
    }

    public function rowViewData(mixed $row): array
    {
        return [
            'step' => $row,
        ];
    }

    public function sharedViewData(): array
    {
        return [
            'companyUsers' => $this->request->attributes->get('_company_users', collect()),
        ];
    }
}
