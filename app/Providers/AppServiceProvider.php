<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\Approval\ApprovalCanceled;
use App\Events\Approval\ApprovalCompleted;
use App\Events\Approval\ApprovalRejected;
use App\Helpers\DateFormatter;
use App\Listeners\Approval\UpdateApprovableStatus;
use App\Models\User;
use App\Services\FeatureService;
use App\Services\SettingService;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Lab404\Impersonate\Events\LeaveImpersonation;
use Lab404\Impersonate\Events\TakeImpersonation;
use SocialiteProviders\Auth0\Auth0ExtendSocialite;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Microsoft\MicrosoftExtendSocialite;
use SocialiteProviders\Saml2\Saml2ExtendSocialite;
use Yammi\AuditLog\Infrastructure\Facade\AuditLog;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        app(SettingService::class)->applyRuntimeAppConfig();

        // Catch N+1 lazy loading violations loudly during development
        Model::preventLazyLoading(! app()->isProduction());

        RateLimiter::for('api.auth', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('api.v1', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Register a Gate for each permission defined in config/permissions.php
        foreach (config('permissions.list', []) as $key => $definition) {
            Gate::define($key, function (User $user) use ($key): bool {
                return $user->getActiveRole()?->hasPermission($key) ?? false;
            });
        }

        // Blade directive @feature('approvals') ... @endfeature
        Blade::if('feature', fn (string $key): bool => app(FeatureService::class)->isEnabled($key));

        // Date and Time Formatting Blade Directives
        Blade::directive('formatDate', fn ($expression): string => "<?php echo \App\Helpers\DateFormatter::formatDate({$expression}); ?>");
        Blade::directive('formatDateTime', fn ($expression): string => "<?php echo \App\Helpers\DateFormatter::formatDateTime({$expression}); ?>");

        // Register approval event listeners
        Event::listen(ApprovalCompleted::class, UpdateApprovableStatus::class);
        Event::listen(ApprovalRejected::class, UpdateApprovableStatus::class);
        Event::listen(ApprovalCanceled::class, UpdateApprovableStatus::class);

        // Register SocialiteProviders extension listeners if packages are present
        if (class_exists(SocialiteWasCalled::class)) {
            if (class_exists(Saml2ExtendSocialite::class)) {
                Event::listen(SocialiteWasCalled::class, Saml2ExtendSocialite::class);
            }
            if (class_exists(Auth0ExtendSocialite::class)) {
                Event::listen(SocialiteWasCalled::class, Auth0ExtendSocialite::class);
            }
            if (class_exists(MicrosoftExtendSocialite::class)) {
                Event::listen(SocialiteWasCalled::class, MicrosoftExtendSocialite::class);
            }
        }

        // Register impersonation session switch event listeners
        Event::listen(TakeImpersonation::class, function ($event): void {
            session()->flash('impersonation_switched', 'started');

            /** @var User $impersonated */
            $impersonated = $event->impersonated;
            $company = $impersonated->getActiveCompany();

            if ($company) {
                session(['active_company_id' => $company->id]);
                $role = $impersonated->getActiveRole($company);
                if ($role) {
                    session(["active_role.{$company->id}" => $role->name]);
                }
            }

            if (class_exists(AuditLog::class)) {
                AuditLog::recordAccess($impersonated);
            }
        });

        Event::listen(LeaveImpersonation::class, function ($event): void {
            session()->flash('impersonation_switched', 'stopped');

            /** @var User $impersonated */
            $impersonated = $event->impersonated;
            /** @var User $impersonator */
            $impersonator = $event->impersonator;
            $company = $impersonator->getActiveCompany();

            if ($company) {
                session(['active_company_id' => $company->id]);
                $role = $impersonator->getActiveRole($company);
                if ($role) {
                    session(["active_role.{$company->id}" => $role->name]);
                }
            }

            if (class_exists(AuditLog::class)) {
                AuditLog::recordAccess($impersonated);
            }
        });

        // Platform-level gate: checks ALL roles across ALL companies
        Gate::define('manage-platform', function (User $user): bool {
            return $user->hasPlatformAccess();
        });

        // Gate for Audit Log UI (Yammi audit log checks viewAuditLog gate)
        Gate::define('viewAuditLog', function (User $user): bool {
            return $user->hasPlatformAccess() || Gate::allows('view-audit-logs');
        });

        // Scramble OpenAPI Configuration & Security
        if (class_exists(Scramble::class)) {
            Scramble::afterOpenApiGenerated(function (OpenApi $openApi): void {
                $openApi->secure(
                    SecurityScheme::http('bearer')
                );
            });

            Gate::define('viewApiDocs', function (?User $user): bool {
                if (app()->environment('local', 'testing')) {
                    return true;
                }

                return $user?->hasPlatformAccess() ?? false;
            });
        }

        // Share company context, appLogo, and appFavicon with all views
        view()->composer('*', function ($view): void {
            $settingService = app(SettingService::class);
            $appLogo = (string) $settingService->get('app_logo', null, '');
            $appFavicon = (string) $settingService->get('app_favicon', null, '');

            $data = [
                'appLogo' => $appLogo,
                'appFavicon' => $appFavicon,
            ];

            if (auth()->check()) {
                $user = auth()->user();
                $activeCompany = $user->getActiveCompany();
                $activeRole = $activeCompany ? $user->getActiveRole($activeCompany) : null;

                $data['activeCompany'] = $activeCompany;
                $data['activeRole'] = $activeRole;
                $data['userCompanies'] = $user->companies;
            }

            $view->with($data);
        });
    }
}
