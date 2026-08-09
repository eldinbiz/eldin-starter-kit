<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\App;

class ApiSetActiveCompany
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $companyId = $request->header('X-Company-ID');

        if (! $companyId) {
            return response()->json([
                'message' => 'The X-Company-ID header is required for this endpoint.'
            ], 400);
        }

        // Check if user is platform admin or member of company
        $isPlatformAdmin = $user->hasPlatformAccess();
        $isMember = $user->companies()->where('companies.id', $companyId)->exists();

        if (! $isPlatformAdmin && ! $isMember) {
            return response()->json([
                'message' => "User does not have access to company {$companyId}."
            ], 403);
        }

        // Bind active company context into memory
        App::instance('active_company_id', (int) $companyId);

        return $next($request);
    }
}
