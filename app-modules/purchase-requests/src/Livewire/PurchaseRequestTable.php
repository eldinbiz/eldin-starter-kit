<?php

declare(strict_types=1);

namespace Modules\PurchaseRequests\Livewire;

use App\Livewire\DataTables\DataTableComponent;
use App\Models\Approval\WorkflowTemplate;
use App\Models\User;
use App\Services\ApprovalEngine;
use Illuminate\Database\Eloquent\Builder;
use Modules\PurchaseRequests\Models\PurchaseRequest;

class PurchaseRequestTable extends DataTableComponent
{
    public ?int $submitModalPrId = null;
    public ?int $selectedTemplateId = null;
    public bool $submitModalOpen = false;

    public function query(): Builder
    {
        /** @var User|null $user */
        $user = auth()->user();
        $company = $user?->getActiveCompany();

        if (! $company) {
            return PurchaseRequest::query()->whereRaw('1 = 0');
        }

        return PurchaseRequest::query()
            ->where('company_id', $company->id)
            ->with(['requester', 'approvalRequests']);
    }

    protected function applySearch(Builder $query): void
    {
        if ($this->search === '') {
            return;
        }

        $query->where(function (Builder $q): void {
            $q->where('title', 'like', "%{$this->search}%")
                ->orWhere('pr_number', 'like', "%{$this->search}%")
                ->orWhere('department', 'like', "%{$this->search}%")
                ->orWhereHas('requester', function (Builder $uq): void {
                    $uq->where('name', 'like', "%{$this->search}%");
                });
        });
    }

    protected function applyFilters(Builder $query): void
    {
        if (! empty($this->filterValues['status'])) {
            $query->where('status', $this->filterValues['status']);
        }

        if (! empty($this->filterValues['department'])) {
            $query->where('department', $this->filterValues['department']);
        }
    }

    public function openSubmitModal(int $prId): void
    {
        $this->submitModalPrId = $prId;
        $this->selectedTemplateId = null;
        $this->submitModalOpen = true;
    }

    public function submitDraft(ApprovalEngine $engine): void
    {
        $this->validate([
            'selectedTemplateId' => ['required', 'integer', 'exists:approval_workflow_templates,id'],
        ]);

        if (! $this->submitModalPrId) {
            return;
        }

        /** @var User $user */
        $user = auth()->user();
        $pr = PurchaseRequest::findOrFail($this->submitModalPrId);

        if ($pr->requester_id !== $user->id || $pr->status !== 'draft') {
            $this->dispatch('notify', message: 'Only draft requests can be submitted.', type: 'error');
            $this->submitModalOpen = false;
            return;
        }

        $template = WorkflowTemplate::findOrFail($this->selectedTemplateId);
        $engine->submit($pr, $template);
        $pr->update(['status' => 'pending_approval']);

        $this->submitModalOpen = false;
        $this->submitModalPrId = null;
        $this->dispatch('notify', message: "Purchase Request {$pr->pr_number} submitted for approval.", type: 'success');
    }

    public function deleteDraft(int $prId): void
    {
        /** @var User $user */
        $user = auth()->user();
        $pr = PurchaseRequest::findOrFail($prId);

        if ($pr->requester_id !== $user->id && ! $user->hasPlatformAccess()) {
            abort(403);
        }

        if ($pr->status !== 'draft') {
            $this->dispatch('notify', message: 'Only draft requests can be deleted.', type: 'error');
            return;
        }

        $pr->delete();
        $this->dispatch('notify', message: 'Purchase request draft deleted.', type: 'info');
    }

    public function render()
    {
        /** @var User|null $user */
        $user = auth()->user();
        $company = $user?->getActiveCompany();

        $templates = $company
            ? WorkflowTemplate::availableFor($company)
                ->where('is_active', true)
                ->where(fn ($q) => $q->where('approvable_type', PurchaseRequest::class)->orWhereNull('approvable_type'))
                ->get()
            : collect();

        return view('purchase-requests::livewire.purchase-request-table', [
            'templates' => $templates,
        ]);
    }
}
