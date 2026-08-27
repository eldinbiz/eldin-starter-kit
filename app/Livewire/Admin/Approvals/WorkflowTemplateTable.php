<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Approvals;

use App\Actions\Approvals\ForceDeleteWorkflowTemplateAction;
use App\Actions\Approvals\ToggleWorkflowTemplateAction;
use App\Actions\Approvals\TrashWorkflowTemplateAction;
use App\Enums\TemplateScope;
use App\Helpers\ApprovableTypes;
use App\Livewire\DataTables\DataTableComponent;
use App\Models\Approval\WorkflowTemplate;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;

class WorkflowTemplateTable extends DataTableComponent
{
    public ?int $selectedCompanyId = null;

    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

    public function mount(): void
    {
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

    public function render()
    {
        return view('livewire.admin.approvals.workflow-template-table');
    }
}
