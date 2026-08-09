<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\v1\ActivityLogResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AuditLogController extends Controller
{
    /**
     * Display a listing of audit logs (platform admin only).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasPlatformAccess()) {
            return response()->json(['message' => 'Unauthorized. Platform Admin required.'], 403);
        }

        $logs = DB::table('audit_log')
            ->orderBy('occurred_at', 'desc')
            ->paginate($request->query('per_page', 15));

        return response()->json([
            'data' => ActivityLogResource::collection($logs),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ]);
    }
}
