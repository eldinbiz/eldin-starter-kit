<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DataTables\ApprovalHistoryDataTable;
use App\DataTables\ApprovalInboxDataTable;
use App\DataTables\ApprovalRequestsDataTable;
use App\Models\Approval\ApprovalRequest;
use App\Models\Approval\ApprovalRequestStep;
use App\Models\User;
use App\Services\ApprovalEngine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApprovalController extends Controller
{
    public function __construct(
        private readonly ApprovalEngine $engine,
    ) {}

    /**
     * Inbox — pending steps where the current user is the approver.
     */
    public function inbox(Request $request, ApprovalInboxDataTable $dataTable): View
    {
        return view('approvals.inbox', [
            'dataTable' => $dataTable->handle($request),
        ]);
    }

    /**
     * History — all requests where the user was an approver or delegate.
     */
    public function history(Request $request, ApprovalHistoryDataTable $dataTable): View
    {
        return view('approvals.history', [
            'dataTable' => $dataTable->handle($request),
        ]);
    }

    /**
     * My requests — submitted by the current user.
     */
    public function requests(Request $request, ApprovalRequestsDataTable $dataTable): View
    {
        return view('approvals.requests', [
            'dataTable' => $dataTable->handle($request),
        ]);
    }

    /**
     * Show a single approval request with full workflow visualization.
     */
    public function show(ApprovalRequest $request): View
    {
        /** @var User $user */
        $user = auth()->user();
        $company = $user->getActiveCompany();

        if (! $company || $request->company_id !== $company->id) {
            abort(403);
        }

        $request->load([
            'steps' => fn ($q) => $q->with(['approver', 'delegatedFrom']),
            'approvable',
            'requestor',
        ]);

        $companyUsers = $company->users()->orderBy('name')->get();

        return view('approvals.show', [
            'approvalRequest' => $request,
            'companyUsers' => $companyUsers,
        ]);
    }

    /**
     * Approve a step.
     */
    public function approve(Request $httpRequest, ApprovalRequestStep $step): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if ($step->approver_id !== $user->id) {
            abort(403);
        }

        $httpRequest->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->engine->approve($step, $user, $httpRequest->input('notes'));

        return redirect()->back()->with('success', 'Step approved.');
    }

    /**
     * Reject a step.
     */
    public function reject(Request $httpRequest, ApprovalRequestStep $step): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if ($step->approver_id !== $user->id) {
            abort(403);
        }

        $httpRequest->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $this->engine->reject($step, $user, $httpRequest->input('reason'));

        return redirect()->back()->with('success', 'Request rejected.');
    }

    /**
     * Cancel an approval request (requestor only).
     */
    public function cancel(Request $httpRequest, ApprovalRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if ($request->requestor_id !== $user->id) {
            abort(403);
        }

        $this->engine->cancel($request, $user);

        return redirect()->route('approvals.requests')->with('success', 'Approval request canceled.');
    }

    /**
     * Delegate a step to a different approver.
     */
    public function delegate(Request $httpRequest, ApprovalRequestStep $step): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if ($step->approver_id !== $user->id) {
            abort(403);
        }

        $httpRequest->validate([
            'approver_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $newApprover = User::findOrFail((int) $httpRequest->input('approver_id'));

        $this->engine->delegate($step, $newApprover, $user);

        return redirect()->back()->with('success', 'Step delegated successfully.');
    }

    /**
     * Resolve the N+1_of_previous approver at runtime and redirect back.
     */
    public function resolvePreviousManager(ApprovalRequestStep $step): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if ($step->approver_id !== $user->id) {
            abort(403);
        }

        // This is a placeholder — full N+1_of_previous logic can be implemented
        // when the engine supports runtime resolution
        return redirect()->back()->with('info', 'Runtime resolution will be implemented in the next iteration.');
    }
}
