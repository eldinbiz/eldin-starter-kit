<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\v1\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    /**
     * Get the authenticated user/system account details.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load(['companies', 'companyRoles']);

        return response()->json([
            'data' => new UserResource($user),
        ]);
    }
}
