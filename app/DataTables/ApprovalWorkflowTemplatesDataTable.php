<?php

declare(strict_types=1);

namespace App\DataTables;

use App\Models\Approval\WorkflowTemplate;
use Illuminate\Database\Eloquent\Builder;

class ApprovalWorkflowTemplatesDataTable extends DataTable
{
    public bool $showTrashed = false;

    public function query(): Builder
    {
        $company = auth()->user()?->getActiveCompany();

        $query = WorkflowTemplate::withCount('groups')
            ->with('groups.steps')
            ->where('company_id', $company?->id);

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
        ];
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
