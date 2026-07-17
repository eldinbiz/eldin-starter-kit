<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
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
            abort(403, 'Unauthorized.');
        }

        $company = $user->getActiveCompany();

        if (! $company) {
            abort(403, 'No active company selected.');
        }

        // Accept: admin role in active company OR platform-admin role in any company
        $hasAdminInCompany = $user->hasRole('admin', $company);
        $hasPlatformAccess = $user->hasPlatformAccess();

        if (! $hasAdminInCompany && ! $hasPlatformAccess) {
            abort(403, 'Unauthorized access.');
        }

        return $next($request);
    }
}
