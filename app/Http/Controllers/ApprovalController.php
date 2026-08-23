<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Approvals\ApproveStepAction;
use App\Actions\Approvals\CancelApprovalRequestAction;
use App\Actions\Approvals\DelegateApprovalStepAction;
use App\Actions\Approvals\RejectStepAction;
use App\Actions\Approvals\ResolvePreviousManagerAction;
use App\DataTables\ApprovalHistoryDataTable;
use App\DataTables\ApprovalInboxDataTable;
use App\DataTables\ApprovalRequestsDataTable;
use App\Http\Requests\Approval\ApproveStepRequest;
use App\Http\Requests\Approval\DelegateApprovalStepRequest;
use App\Http\Requests\Approval\RejectStepRequest;
use App\Http\Requests\Approval\ResolvePreviousManagerRequest;
use App\Models\Approval\ApprovalRequest;
use App\Models\Approval\ApprovalRequestStep;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApprovalController extends Controller
{
    /**
     * Inbox — pending steps where the current user is the approver.
     */
    public function inbox(Request $request, ApprovalInboxDataTable $dataTable): View
    {
        $dataTable->handle($request);

        if ($dataTable->isPartialRequest($request)) {
            return $dataTable->renderPartialView();
        }

        return view('approvals.inbox', [
            'dataTable' => $dataTable,
        ]);
    }

    /**
     * History — all requests where the user was an approver or delegate.
     */
    public function history(Request $request, ApprovalHistoryDataTable $dataTable): View
    {
        $dataTable->handle($request);

        if ($dataTable->isPartialRequest($request)) {
            return $dataTable->renderPartialView();
        }

        return view('approvals.history', [
            'dataTable' => $dataTable,
        ]);
    }

    /**
     * My requests — submitted by the current user.
     */
    public function requests(Request $request, ApprovalRequestsDataTable $dataTable): View
    {
        $dataTable->handle($request);

        if ($dataTable->isPartialRequest($request)) {
            return $dataTable->renderPartialView();
        }

        return view('approvals.requests', [
            'dataTable' => $dataTable,
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
    public function approve(
        ApproveStepRequest $request,
        ApprovalRequestStep $step,
        ApproveStepAction $action
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();

        if ($step->approver_id !== $user->id) {
            abort(403);
        }

        if (! $step->isPending()) {
            return redirect()->back()->with('error', 'This step has already been processed. The approval request status may have changed.');
        }

        $action->execute($step, $user, $request->validated('notes'));

        return redirect()->back()->with('success', 'Step approved.');
    }

    /**
     * Reject a step.
     */
    public function reject(
        RejectStepRequest $request,
        ApprovalRequestStep $step,
        RejectStepAction $action
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();

        if ($step->approver_id !== $user->id) {
            abort(403);
        }

        if (! $step->isPending()) {
            return redirect()->back()->with('error', 'This step has already been processed. The approval request status may have changed.');
        }

        $action->execute($step, $user, (string) $request->validated('reason'));

        return redirect()->back()->with('success', 'Request rejected.');
    }

    /**
     * Cancel an approval request (requestor only).
     */
    public function cancel(
        Request $httpRequest,
        ApprovalRequest $request,
        CancelApprovalRequestAction $action
    ): RedirectResponse {
        /** @var User $user */
        $user = $httpRequest->user();

        $action->execute($request, $user);

        return redirect()->route('approvals.requests')->with('success', 'Approval request canceled.');
    }

    /**
     * Delegate a step to a different approver.
     */
    public function delegate(
        DelegateApprovalStepRequest $request,
        ApprovalRequestStep $step,
        DelegateApprovalStepAction $action
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();

        if ($step->approver_id !== $user->id) {
            abort(403);
        }

        $approverId = (int) $request->validated('approver_id');
        $action->execute($step, $approverId, $user);

        return redirect()->back()->with('success', 'Step delegated successfully.');
    }

    /**
     * Resolve the N+1_of_previous approver at runtime and redirect back.
     * Only the requestor or a platform admin can manually resolve stuck steps.
     */
    public function resolvePreviousManager(
        ResolvePreviousManagerRequest $httpRequest,
        ApprovalRequestStep $step,
        ResolvePreviousManagerAction $action
    ): RedirectResponse {
        /** @var User $user */
        $user = $httpRequest->user();

        if (! $step->isAwaitingPrevious()) {
            abort(403, 'This step is not awaiting resolution.');
        }

        $request = $step->loadMissing('request')->request;
        if ($request->requestor_id !== $user->id && ! $user->hasPlatformAccess()) {
            abort(403);
        }

        $approverId = (int) $httpRequest->validated('approver_id');
        $action->execute($step, $user, $approverId);

        return redirect()->back()->with('success', 'Approver resolved successfully.');
    }
}
