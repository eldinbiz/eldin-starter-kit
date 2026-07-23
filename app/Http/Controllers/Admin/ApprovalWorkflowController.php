<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\DataTables\ApprovalWorkflowTemplatesDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Approval\StoreWorkflowTemplateRequest;
use App\Models\Approval\WorkflowTemplate;
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

        return view('admin.approval-workflows.index', [
            'dataTable' => $dataTable->handle($request),
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

        $users = $company->users()->orderBy('name')->get();

        return view('admin.approval-workflows.create', [
            'users' => $users,
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
            'approvable_type' => $request->input('approvable_type'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        foreach ($request->input('groups', []) as $groupIndex => $groupData) {
            $group = $template->groups()->create([
                'name' => $groupData['name'],
                'order' => $groupIndex,
                'type' => $groupData['type'],
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
        $company = auth()->user()?->getActiveCompany();

        if (! $company || $workflow->company_id !== $company->id) {
            abort(403);
        }

        $workflow->load('groups.steps');
        $users = $company->users()->orderBy('name')->get();

        return view('admin.approval-workflows.edit', [
            'template' => $workflow,
            'users' => $users,
        ]);
    }

    /**
     * Update an existing workflow template.
     */
    public function update(StoreWorkflowTemplateRequest $request, WorkflowTemplate $workflow): RedirectResponse
    {
        $company = auth()->user()?->getActiveCompany();

        if (! $company || $workflow->company_id !== $company->id) {
            abort(403);
        }

        $workflow->update([
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'approvable_type' => $request->input('approvable_type'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        // Remove old groups (and their steps via cascade)
        $workflow->groups()->delete();

        foreach ($request->input('groups', []) as $groupIndex => $groupData) {
            $group = $workflow->groups()->create([
                'name' => $groupData['name'],
                'order' => $groupIndex,
                'type' => $groupData['type'],
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
        $company = auth()->user()?->getActiveCompany();

        if (! $company || $workflow->company_id !== $company->id) {
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
        $company = auth()->user()?->getActiveCompany();

        if (! $company) {
            abort(403);
        }

        $workflow = WorkflowTemplate::withTrashed()->findOrFail((int) $id);

        if ($workflow->company_id !== $company->id) {
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
        $company = auth()->user()?->getActiveCompany();

        if (! $company || $workflow->company_id !== $company->id) {
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
        $company = auth()->user()?->getActiveCompany();

        if (! $company) {
            abort(403);
        }

        $workflow = WorkflowTemplate::withTrashed()->findOrFail((int) $id);

        if ($workflow->company_id !== $company->id) {
            abort(403);
        }

        $workflow->forceDelete();

        return redirect()->route('admin.approval-workflows.index', ['trashed' => 1])
            ->with('success', 'Workflow template permanently deleted.');
    }
}
