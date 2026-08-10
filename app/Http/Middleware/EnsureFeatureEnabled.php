<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\FeatureService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class EnsureFeatureEnabled
{
    public function __construct(
        private readonly FeatureService $featureService
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $user = auth()->user();

        if ($user) {
            // Platform admins bypass feature gating entirely.
            if ($user->hasPlatformAccess()) {
                return $next($request);
            }

            // Holders of the feature's manage/view permissions bypass the toggle.
            $definition = config("features.{$feature}", []);

            foreach ($definition['bypass_permissions'] ?? [] as $permission) {
                if ($user->getActiveRole()?->hasPermission($permission) ?? false) {
                    return $next($request);
                }
            }
        }

        // Resolve the company context: API binds via api.company, web uses the session.
        $companyId = App::bound('active_company_id')
            ? App::get('active_company_id')
            : $request->header('X-Company-ID');

        $companyId ??= $user?->getActiveCompany()?->id;

        if ($companyId === null) {
            // No company context — cannot scope the toggle, allow the request.
            return $next($request);
        }

        if ($this->featureService->isEnabled($feature, (int) $companyId)) {
            return $next($request);
        }

        abort(404);
    }
}
