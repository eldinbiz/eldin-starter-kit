<?php

declare(strict_types=1);

namespace App\DataTables;

use App\Enums\TemplateScope;
use App\Helpers\ApprovableTypes;
use App\Models\Approval\WorkflowTemplate;
use Illuminate\Database\Eloquent\Builder;

class ApprovalWorkflowTemplatesDataTable extends DataTable
{
    public bool $showTrashed = false;

    public function query(): Builder
    {
        $user = auth()->user();
        $isSysAdmin = $user?->hasPlatformAccess() ?? false;
        $company = $user?->getActiveCompany();

        $query = WorkflowTemplate::withCount('groups')
            ->with('groups.steps');

        if (! $isSysAdmin) {
            // Non-trashed queries are scoped to the active company + its available templates
            if ($company && ! $this->showTrashed) {
                $query->availableFor($company);
            } elseif ($company) {
                $query->where('company_id', $company->id);
            }
        }

        if ($this->showTrashed) {
            $query->onlyTrashed();
        }

        return $query;
    }

    public function columns(): array
    {
        return [
            Column::make('name')
                ->label('Name')
                ->sortable()
                ->searchable(),
            Column::make('approvable_type')
                ->label('Applies To')
                ->sortable(),
            Column::make('groups_count')
                ->label('Groups')
                ->sortable(),
            Column::make('is_active')
                ->label('Status'),
            Column::make('created_at')
                ->label('Created')
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
                ->placeholder('Search templates...')
                ->apply(function (Builder $q, string $value): void {
                    $q->where(function (Builder $sub) use ($value): void {
                        $sub->where('name', 'like', "%{$value}%")
                            ->orWhere('description', 'like', "%{$value}%");
                    });
                }),

            Filter::make('type')
                ->type('select')
                ->label('Applies To')
                ->options(fn (): array => ApprovableTypes::options())
                ->apply(function (Builder $q, string $v): void {
                    if ($v === '') {
                        $q->whereNull('approvable_type');
                    } else {
                        $q->where('approvable_type', $v);
                    }
                }),

            Filter::make('status')
                ->type('select')
                ->label('Status')
                ->options([
                    '1' => 'Active',
                    '0' => 'Disabled',
                ])
                ->apply(fn (Builder $q, string $v) => $q->where('is_active', $v)),

            Filter::make('scope')
                ->type('select')
                ->label('Scope')
                ->options(function (): array {
                    $company = auth()->user()?->getActiveCompany();

                    return [
                        TemplateScope::ThisCompany->value => TemplateScope::ThisCompany->label($company),
                        TemplateScope::ThisCompanyAndSubsidiaries->value => TemplateScope::ThisCompanyAndSubsidiaries->label($company),
                        TemplateScope::Global->value => TemplateScope::Global->label(),
                    ];
                })
                ->apply(fn (Builder $q, string $v) => $q->where('scope', $v)),

            Filter::make('created_from')
                ->type('date')
                ->label('Created From')
                ->apply(fn (Builder $q, string $v) => $q->where('created_at', '>=', $v)),
        ];

        if (auth()->user()?->hasPlatformAccess()) {
            $companyOptions = \App\Models\Company::orderBy('name')->pluck('name', 'id')->all();
            $filters[] = Filter::make('company_id')
                ->type('select')
                ->label('Company')
                ->options($companyOptions)
                ->apply(fn (Builder $q, string $v) => $q->where('company_id', $v));
        }

        return $filters;
    }

    public function renderRow(mixed $row): string
    {
        /** @var WorkflowTemplate $row */
        return view('admin.approval-workflows._row', [
            'template' => $row,
        ])->render();
    }

    public function renderMobileRow(mixed $row): string
    {
        /** @var WorkflowTemplate $row */
        return view('admin.approval-workflows._mobile_row', [
            'template' => $row,
        ])->render();
    }
}
