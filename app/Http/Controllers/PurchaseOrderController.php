<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Approval\WorkflowTemplate;
use App\Models\PurchaseOrder;
use App\Services\ApprovalEngine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class PurchaseOrderController extends Controller
{
    public function __construct(
        private readonly ApprovalEngine $engine,
    ) {}

    /**
     * List all purchase orders for the active company.
     */
    public function index(): View
    {
        $company = auth()->user()?->getActiveCompany();

        if (! $company) {
            abort(403);
        }

        $orders = PurchaseOrder::with(['requester', 'approvalRequests'])
            ->where('company_id', $company->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('purchase-orders.index', [
            'purchaseOrders' => $orders,
        ]);
    }

    /**
     * Show form to create a purchase order.
     */
    public function create(): View
    {
        return view('purchase-orders.create');
    }

    /**
     * Store a new purchase order.
     */
    public function store(Request $request): RedirectResponse
    {
        $company = auth()->user()?->getActiveCompany();

        if (! $company) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'amount' => ['required', 'numeric', 'min:0'],
        ]);

        $po = PurchaseOrder::create([
            'company_id' => $company->id,
            'requester_id' => auth()->id(),
            'title' => $validated['title'],
            'description' => $validated['description'],
            'amount' => $validated['amount'],
            'status' => 'draft',
        ]);

        return redirect()->route('purchase-orders.show', $po->id)
            ->with('success', 'Purchase order created.');
    }

    /**
     * Show a single purchase order.
     */
    public function show(PurchaseOrder $purchaseOrder): View
    {
        $company = auth()->user()?->getActiveCompany();

        if (! $company || $purchaseOrder->company_id !== $company->id) {
            abort(403);
        }

        $purchaseOrder->load([
            'requester',
            'approvalRequests.steps' => fn ($q) => $q->with(['approver', 'delegatedFrom']),
        ]);

        $templates = WorkflowTemplate::availableFor($company)
            ->where('is_active', true)
            ->where(fn ($q) => $q->where('approvable_type', PurchaseOrder::class)->orWhereNull('approvable_type'))
            ->get();

        $latestRequest = $purchaseOrder->approvalRequests->last();

        return view('purchase-orders.show', [
            'purchaseOrder' => $purchaseOrder,
            'templates' => $templates,
            'latestRequest' => $latestRequest,
        ]);
    }

    /**
     * Preview a workflow for a given template (AJAX).
     */
    public function previewWorkflow(PurchaseOrder $purchaseOrder, Request $request): View
    {
        $templateId = $request->integer('template_id');
        $template = WorkflowTemplate::findOrFail($templateId);

        $preview = $this->engine->preview($purchaseOrder, $template);

        return view('approvals._workflow-diagram', [
            'preview' => $preview,
        ]);
    }

    /**
     * Submit the purchase order for approval.
     */
    public function submitForApproval(PurchaseOrder $purchaseOrder, Request $request): RedirectResponse
    {
        if ($purchaseOrder->requester_id !== auth()->id()) {
            abort(403);
        }

        if ($purchaseOrder->status !== 'draft') {
            return back()->with('error', 'This purchase order cannot be submitted.');
        }

        $templateId = $request->integer('template_id');
        $template = WorkflowTemplate::findOrFail($templateId);

        try {
            $this->engine->submit($purchaseOrder, $template);
        } catch (RuntimeException $e) {
            return back()->with('error', 'Cannot submit for approval: '.$e->getMessage());
        }

        $purchaseOrder->update(['status' => 'pending_approval']);

        return redirect()->route('purchase-orders.show', $purchaseOrder->id)
            ->with('success', 'Purchase order submitted for approval.');
    }
}
