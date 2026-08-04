<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\DataTables\ApprovalManagementDataTable;
use App\Models\Approval\ApprovalRequestStep;
use App\Models\User;
use App\Services\ApprovalEngine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApprovalManagementController
{
    public function __construct(
        private readonly ApprovalEngine $engine,
    ) {}

    public function index(Request $request, ApprovalManagementDataTable $dataTable): View
    {
        /** @var User $user */
        $user = auth()->user();
        $company = $user->getActiveCompany();

        $companyUsers = $company->users()->orderBy('name')->get();
        $request->attributes->set('_company_users', $companyUsers);

        return view('admin.approval-management.index', [
            'dataTable' => $dataTable->handle($request),
            'companyUsers' => $companyUsers,
        ]);
    }

    public function delegate(Request $httpRequest, ApprovalRequestStep $step): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $httpRequest->validate([
            'approver_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $newApprover = User::findOrFail((int) $httpRequest->input('approver_id'));

        $this->engine->delegate($step, $newApprover, $user);

        return redirect()->back()->with('success', 'Step delegated successfully.');
    }

    public function skip(Request $httpRequest, ApprovalRequestStep $step): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $this->engine->skipStep($step, $user);

        return redirect()->back()->with('success', 'Step skipped successfully.');
    }
}
