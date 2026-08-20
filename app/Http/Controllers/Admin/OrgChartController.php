<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\OrgChart\GetOrgChartTreeAction;
use App\Actions\OrgChart\ReassignManagerAction;
use App\Actions\OrgChart\TraceReportingLineAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OrgChart\ReassignManagerRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use InvalidArgumentException;

class OrgChartController extends Controller
{
    /**
     * Render the organization chart page.
     */
    public function index(): View
    {
        $activeCompany = auth()->user()?->getActiveCompany();

        return view('admin.org-chart.index', [
            'company' => $activeCompany,
            'canManage' => auth()->user()?->can('manage-org-chart') ?? false,
        ]);
    }

    /**
     * Return the full org chart tree as JSON for the active company.
     */
    public function tree(GetOrgChartTreeAction $action): JsonResponse
    {
        $user = auth()->user();
        $activeCompany = $user?->getActiveCompany();

        if (! $activeCompany) {
            return response()->json(['error' => 'No active company.'], 404);
        }

        $data = $action->execute($activeCompany);

        return response()->json($data);
    }

    /**
     * Reassign a user's manager within the active company.
     */
    public function updateManager(ReassignManagerRequest $request, ReassignManagerAction $action): JsonResponse
    {
        $activeCompany = auth()->user()?->getActiveCompany();

        if (! $activeCompany) {
            return response()->json(['error' => 'No active company.'], 404);
        }

        /** @var array{user_id: int|string, new_manager_id?: int|string|null, job_title?: string|null, status?: string|null} $validated */
        $validated = $request->validated();

        try {
            $action->execute($activeCompany, $validated);
        } catch (InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Trace the reporting line from a user up to the root.
     */
    public function trace(int $user, TraceReportingLineAction $action): JsonResponse
    {
        $activeCompany = auth()->user()?->getActiveCompany();

        if (! $activeCompany) {
            return response()->json(['error' => 'No active company.'], 404);
        }

        $chain = $action->execute($activeCompany, $user);

        return response()->json(['chain' => $chain]);
    }
}
