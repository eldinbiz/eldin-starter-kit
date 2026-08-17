<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\v1\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of human users.
     */
    public function index(Request $request): JsonResponse
    {
        // Scope to human users to hide bots
        $users = User::human()
            ->with(['companies', 'companyRoles'])
            ->paginate($request->query('per_page', 15));

        return response()->json([
            'data' => UserResource::collection($users),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    /**
     * Display the specified user.
     */
    public function show(User $user): JsonResponse
    {
        if ($user->is_system) {
            return response()->json(['message' => 'System accounts cannot be queried.'], 403);
        }

        $user->load(['companies', 'companyRoles']);

        return response()->json([
            'data' => new UserResource($user),
        ]);
    }
}
