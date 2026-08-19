<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\DataTables\ApprovalWorkflowTemplatesDataTable;
use App\Helpers\ApprovableTypes;
use App\Http\Controllers\Controller;
use App\Http\Requests\Approval\StoreWorkflowTemplateRequest;
use App\Models\Approval\WorkflowTemplate;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApprovalWorkflowController extends Controller
{
    /**
     * List all workflow templates for the active company.
     */
    public function index(Request $request, ApprovalWorkflowTemplatesDataTable $dataTable): View
    {
        $dataTable->showTrashed = $request->boolean('trashed');
        $dataTable->handle($request);

        if ($dataTable->isPartialRequest($request)) {
            return $dataTable->renderPartialView();
        }

        return view('admin.approval-workflows.index', [
            'dataTable' => $dataTable,
            'showTrashed' => $dataTable->showTrashed,
        ]);
    }

    /**
     * Show form to create a new workflow template.
     */
    public function create(): View
    {
        $company = auth()->user()?->getActiveCompany();

        if (! $company) {
            abort(403);
        }

        $users = $this->getCompanyAndAncestorUsers($company);

        $company->loadMissing('children');

        return view('admin.approval-workflows.create', [
            'company' => $company,
            'users' => $users,
            'approvableTypes' => ApprovableTypes::options(),
        ]);
    }

    /**
     * Store a new workflow template with groups and steps.
     */
    public function store(StoreWorkflowTemplateRequest $request): RedirectResponse
    {
        $company = auth()->user()?->getActiveCompany();

        if (! $company) {
            abort(403);
        }

        $template = WorkflowTemplate::create([
            'company_id' => $company->id,
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'approvable_type' => $request->input('approvable_type') ?: null,
            'is_active' => $request->boolean('is_active', true),
            'scope' => $request->input('scope', 'this_company'),
        ]);

        foreach ($request->input('groups', []) as $groupIndex => $groupData) {
            $minApprovals = ($groupData['type'] === 'parallel' && ! empty($groupData['min_approvals']))
                ? (int) $groupData['min_approvals']
                : null;

            $group = $template->groups()->create([
                'name' => $groupData['name'],
                'order' => $groupIndex,
                'type' => $groupData['type'],
                'min_approvals' => $minApprovals,
            ]);

            foreach ($groupData['steps'] ?? [] as $stepIndex => $stepData) {
                $group->steps()->create([
                    'label' => $stepData['label'],
                    'order' => $stepIndex,
                    'resolver_type' => $stepData['resolver_type'],
                    'resolver_payload' => $stepData['resolver_payload'] ?? null,
                ]);
            }
        }

        return redirect()->route('admin.approval-workflows.index')
            ->with('success', 'Workflow template created successfully.');
    }

    /**
     * Show form to edit a workflow template.
     */
    public function edit(WorkflowTemplate $workflow): View
    {
        /** @var User|null $user */
        $user = auth()->user();
        $company = $user?->getActiveCompany();

        if (! $this->canManageWorkflow($user, $company, $workflow)) {
            abort(403);
        }

        $workflow->load('groups.steps');
        $workflow->load('company.children');
        $targetCompany = $workflow->company ?? $company;
        $users = $targetCompany ? $this->getCompanyAndAncestorUsers($targetCompany) : new Collection;

        return view('admin.approval-workflows.edit', [
            'template' => $workflow,
            'company' => $targetCompany,
            'users' => $users,
            'approvableTypes' => ApprovableTypes::options(),
        ]);
    }

    /**
     * Update an existing workflow template.
     */
    public function update(StoreWorkflowTemplateRequest $request, WorkflowTemplate $workflow): RedirectResponse
    {
        /** @var User|null $user */
        $user = auth()->user();
        $company = $user?->getActiveCompany();

        if (! $this->canManageWorkflow($user, $company, $workflow)) {
            abort(403);
        }

        $workflow->update([
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'approvable_type' => $request->input('approvable_type') ?: null,
            'is_active' => $request->boolean('is_active', true),
            'scope' => $request->input('scope', 'this_company'),
        ]);

        // Remove old groups (and their steps via cascade)
        $workflow->groups()->delete();

        foreach ($request->input('groups', []) as $groupIndex => $groupData) {
            $minApprovals = ($groupData['type'] === 'parallel' && ! empty($groupData['min_approvals']))
                ? (int) $groupData['min_approvals']
                : null;

            $group = $workflow->groups()->create([
                'name' => $groupData['name'],
                'order' => $groupIndex,
                'type' => $groupData['type'],
                'min_approvals' => $minApprovals,
            ]);

            foreach ($groupData['steps'] ?? [] as $stepIndex => $stepData) {
                $group->steps()->create([
                    'label' => $stepData['label'],
                    'order' => $stepIndex,
                    'resolver_type' => $stepData['resolver_type'],
                    'resolver_payload' => $stepData['resolver_payload'] ?? null,
                ]);
            }
        }

        return redirect()->route('admin.approval-workflows.index')
            ->with('success', 'Workflow template updated successfully.');
    }

    /**
     * Disable a workflow template (set is_active = false).
     */
    public function destroy(WorkflowTemplate $workflow): RedirectResponse
    {
        /** @var User|null $user */
        $user = auth()->user();
        $company = $user?->getActiveCompany();

        if (! $this->canManageWorkflow($user, $company, $workflow)) {
            abort(403);
        }

        $workflow->update(['is_active' => false]);

        return redirect()->route('admin.approval-workflows.index')
            ->with('success', 'Workflow template disabled.');
    }

    /**
     * Enable a workflow template (set is_active = true).
     */
    public function restore(string $id): RedirectResponse
    {
        /** @var User|null $user */
        $user = auth()->user();
        $company = $user?->getActiveCompany();

        $workflow = WorkflowTemplate::withTrashed()->findOrFail((int) $id);

        if (! $this->canManageWorkflow($user, $company, $workflow)) {
            abort(403);
        }

        $workflow->update(['is_active' => true]);

        // If it was in the trash, restore it too
        if ($workflow->trashed()) {
            $workflow->restore();
        }

        return redirect()->route('admin.approval-workflows.index')
            ->with('success', 'Workflow template enabled.');
    }

    /**
     * Soft-delete a disabled workflow template.
     */
    public function trash(WorkflowTemplate $workflow): RedirectResponse
    {
        /** @var User|null $user */
        $user = auth()->user();
        $company = $user?->getActiveCompany();

        if (! $this->canManageWorkflow($user, $company, $workflow)) {
            abort(403);
        }

        if ($workflow->is_active) {
            abort(400, 'Only disabled templates can be deleted.');
        }

        $workflow->delete();

        return redirect()->route('admin.approval-workflows.index')
            ->with('success', 'Workflow template deleted.');
    }

    /**
     * Permanently delete a workflow template (force delete).
     */
    public function softDelete(string $id): RedirectResponse
    {
        /** @var User|null $user */
        $user = auth()->user();
        $company = $user?->getActiveCompany();

        $workflow = WorkflowTemplate::withTrashed()->findOrFail((int) $id);

        if (! $this->canManageWorkflow($user, $company, $workflow)) {
            abort(403);
        }

        $workflow->forceDelete();

        return redirect()->route('admin.approval-workflows.index', ['trashed' => 1])
            ->with('success', 'Workflow template permanently deleted.');
    }

    /**
     * Check if user is authorized to manage the workflow template.
     */
    private function canManageWorkflow(?User $user, ?Company $company, WorkflowTemplate $workflow): bool
    {
        if ($user?->hasPlatformAccess()) {
            return true;
        }

        return $company !== null && $workflow->company_id === $company->id;
    }

    /**
     * Get users from the current company and all ancestor companies.
     *
     * @return Collection<int, User>
     */
    private function getCompanyAndAncestorUsers(Company $company): Collection
    {
        $companyIds = collect([$company->id])
            ->merge($company->allAncestors()->pluck('id'));

        return User::whereHas('companies', fn ($q) => $q->whereIn('companies.id', $companyIds))
            ->with(['companies' => fn ($q) => $q->whereIn('companies.id', $companyIds)])
            ->orderBy('name')
            ->get();
    }
}
