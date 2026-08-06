<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Approval\WorkflowTemplate;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Services\ApprovalEngine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class PurchaseRequestController
{
    public function __construct(
        private readonly ApprovalEngine $engine
    ) {}

    public function index(): View
    {
        /** @var User $user */
        $user = auth()->user();
        $company = $user->getActiveCompany();

        if (! $company) {
            abort(403, 'No active company selected.');
        }

        $purchaseRequests = PurchaseRequest::with(['requester', 'approvalRequests'])
            ->where('company_id', $company->id)
            ->latest()
            ->paginate(15);

        return view('purchase-requests.index', [
            'purchaseRequests' => $purchaseRequests,
        ]);
    }

    public function create(): View
    {
        return view('purchase-requests.create');
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();
        $company = $user->getActiveCompany();

        if (! $company) {
            abort(403, 'No active company selected.');
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'department' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'total_amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $prNumber = 'PR-'.date('Ym').'-'.str_pad((string) (PurchaseRequest::count() + 1), 4, '0', STR_PAD_LEFT);

        $purchaseRequest = PurchaseRequest::create([
            'company_id' => $company->id,
            'requester_id' => $user->id,
            'pr_number' => $prNumber,
            'title' => $validated['title'],
            'department' => $validated['department'],
            'description' => $validated['description'] ?? null,
            'total_amount' => $validated['total_amount'],
            'status' => 'draft',
        ]);

        return redirect()->route('purchase-requests.show', $purchaseRequest->id)
            ->with('success', 'Purchase request created successfully.');
    }

    public function show(PurchaseRequest $purchaseRequest): View
    {
        /** @var User $user */
        $user = auth()->user();
        $company = $user->getActiveCompany();

        if (! $company || $purchaseRequest->company_id !== $company->id) {
            abort(403);
        }

        $purchaseRequest->load(['requester', 'approvalRequests.steps' => fn ($q) => $q->with(['approver', 'delegatedFrom'])]);

        $templates = WorkflowTemplate::availableFor($company)
            ->where('is_active', true)
            ->where(fn ($q) => $q->where('approvable_type', PurchaseRequest::class)->orWhereNull('approvable_type'))
            ->get();

        $latestRequest = $purchaseRequest->approvalRequests->last();

        return view('purchase-requests.show', [
            'purchaseRequest' => $purchaseRequest,
            'templates' => $templates,
            'latestRequest' => $latestRequest,
        ]);
    }

    public function previewWorkflow(PurchaseRequest $purchaseRequest, Request $request): View
    {
        $template = WorkflowTemplate::findOrFail($request->integer('template_id'));

        return view('approvals._workflow-diagram', [
            'preview' => $this->engine->preview($purchaseRequest, $template),
        ]);
    }

    public function submitForApproval(PurchaseRequest $purchaseRequest, Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if ($purchaseRequest->requester_id !== $user->id) {
            abort(403);
        }

        if ($purchaseRequest->status !== 'draft') {
            return back()->with('error', 'This purchase request cannot be submitted.');
        }

        $templateId = $request->integer('template_id');
        $template = WorkflowTemplate::findOrFail($templateId);

        try {
            $this->engine->submit($purchaseRequest, $template);
        } catch (RuntimeException $e) {
            return back()->with('error', 'Cannot submit for approval: '.$e->getMessage());
        }

        $purchaseRequest->update(['status' => 'pending_approval']);

        return redirect()->route('purchase-requests.show', $purchaseRequest->id)
            ->with('success', 'Purchase request submitted for approval.');
    }
}
