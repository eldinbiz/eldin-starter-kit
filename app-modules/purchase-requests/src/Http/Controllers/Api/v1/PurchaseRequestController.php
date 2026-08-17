<?php

declare(strict_types=1);

namespace Modules\PurchaseRequests\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\PurchaseRequests\Http\Resources\Api\v1\PurchaseRequestResource;
use Modules\PurchaseRequests\Models\PurchaseRequest;

class PurchaseRequestController extends Controller
{
    /**
     * Display a listing of purchase requests.
     */
    public function index(Request $request): JsonResponse
    {
        $purchases = PurchaseRequest::query()
            ->with(['requester'])
            ->where('company_id', \App::get('active_company_id'))
            ->paginate($request->query('per_page', 15));

        return response()->json([
            'data' => PurchaseRequestResource::collection($purchases),
            'meta' => [
                'current_page' => $purchases->currentPage(),
                'last_page' => $purchases->lastPage(),
                'per_page' => $purchases->perPage(),
                'total' => $purchases->total(),
            ],
        ]);
    }

    /**
     * Display the specified purchase request.
     */
    public function show(PurchaseRequest $purchaseRequest): JsonResponse
    {
        $purchaseRequest->load(['requester']);

        return response()->json([
            'data' => new PurchaseRequestResource($purchaseRequest),
        ]);
    }
}
