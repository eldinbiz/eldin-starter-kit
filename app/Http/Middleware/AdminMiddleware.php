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

        // Get active role from session, fallback to user's first role
        $activeRole = session('active_role') ?? $user->roles()->first()->name ?? 'user';

        if ($activeRole !== 'admin') {
            abort(403, 'Unauthorized access.');
        }

        return $next($request);
    }
}
