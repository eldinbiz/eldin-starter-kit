<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetActiveCompany
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if ($user && ! session()->has('active_company_id')) {
            $firstCompany = $user->companies()->first();

            if ($firstCompany) {
                session(['active_company_id' => $firstCompany->id]);
            }
        }

        return $next($request);
    }
}
