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
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        if (!$user) {
            abort(403, 'Unauthorized.');
        }

        $company = $user->getActiveCompany();

        if (!$company) {
            abort(403, 'No active company selected.');
        }

        // Get active role from session (company-scoped), fallback to user's first role
        $activeRole = $user->getActiveRole($company);

        if (!$activeRole || $activeRole->name !== 'admin') {
            abort(403, 'Unauthorized access.');
        }

        return $next($request);
    }
}
