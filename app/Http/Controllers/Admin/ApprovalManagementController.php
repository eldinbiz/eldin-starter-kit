<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Approvals\DelegateApprovalStepAction;
use App\Actions\Approvals\SkipApprovalStepAction;
use App\DataTables\ApprovalManagementDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Approval\DelegateApprovalStepRequest;
use App\Models\Approval\ApprovalRequestStep;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApprovalManagementController extends Controller
{
    public function index(Request $request, ApprovalManagementDataTable $dataTable): View
    {
        $dataTable->handle($request);

        if ($dataTable->isPartialRequest($request)) {
            return $dataTable->renderPartialView();
        }

        return view('admin.approval-management.index', [
            'dataTable' => $dataTable,
        ]);
    }

    public function delegate(
        DelegateApprovalStepRequest $request,
        ApprovalRequestStep $step,
        DelegateApprovalStepAction $action
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();
        $approverId = (int) $request->validated('approver_id');

        $action->execute($step, $approverId, $user);

        return redirect()->back()->with('success', 'Step delegated successfully.');
    }

    public function skip(
        Request $request,
        ApprovalRequestStep $step,
        SkipApprovalStepAction $action
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();

        $action->execute($step, $user);

        return redirect()->back()->with('success', 'Step skipped successfully.');
    }
}
