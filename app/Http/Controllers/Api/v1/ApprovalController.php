<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\v1\ApprovalRequestResource;
use App\Models\Approval\ApprovalRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ApprovalController extends Controller
{
    /**
     * Display a listing of approvals for the authenticated user/company context.
     */
    public function index(Request $request): JsonResponse
    {
        // For System integrations, they can query approvals across the active company
        $approvals = ApprovalRequest::query()
            ->with(['requestor', 'steps'])
            ->where('company_id', \App::get('active_company_id'))
            ->paginate($request->query('per_page', 15));

        return response()->json([
            'data' => ApprovalRequestResource::collection($approvals),
            'meta' => [
                'current_page' => $approvals->currentPage(),
                'last_page' => $approvals->lastPage(),
                'per_page' => $approvals->perPage(),
                'total' => $approvals->total(),
            ],
        ]);
    }

    /**
     * Display the specified approval.
     */
    public function show(ApprovalRequest $approval): JsonResponse
    {
        $approval->load(['requester', 'steps.actionedBy']);

        return response()->json([
            'data' => new ApprovalRequestResource($approval),
        ]);
    }
}
