<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\Approval\ApprovalCanceled;
use App\Events\Approval\ApprovalCompleted;
use App\Events\Approval\ApprovalRejected;
use App\Listeners\Approval\UpdateApprovableStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Lab404\Impersonate\Events\LeaveImpersonation;
use Lab404\Impersonate\Events\TakeImpersonation;
use SocialiteProviders\Auth0\Auth0ExtendSocialite;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Microsoft\MicrosoftExtendSocialite;
use SocialiteProviders\Saml2\Saml2ExtendSocialite;

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
        // Catch N+1 lazy loading violations loudly during development
        Model::preventLazyLoading(! app()->isProduction());

        // Register a Gate for each permission defined in config/permissions.php
        foreach (config('permissions.list', []) as $key => $definition) {
            Gate::define($key, function (User $user) use ($key): bool {
                return $user->getActiveRole()?->hasPermission($key) ?? false;
            });
        }

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
        });

        Event::listen(LeaveImpersonation::class, function ($event): void {
            session()->flash('impersonation_switched', 'stopped');

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
        });

        // Platform-level gate: checks ALL roles across ALL companies
        Gate::define('manage-platform', function (User $user): bool {
            return $user->hasPlatformAccess();
        });

        // Gate for Audit Log UI (Yammi audit log checks viewAuditLog gate)
        Gate::define('viewAuditLog', function (User $user): bool {
            return $user->hasPlatformAccess() || Gate::allows('view-audit-logs');
        });

        // Share company context with all views — memoized, single-query per request
        view()->composer('*', function ($view): void {
            if (! auth()->check()) {
                return;
            }

            $user = auth()->user();

            // Memoized via HasCompanyContext trait
            $activeCompany = $user->getActiveCompany();

            // Only resolve role if there's active company context
            $activeRole = $activeCompany
                ? $user->getActiveRole($activeCompany)
                : null;

            $view->with([
                'activeCompany' => $activeCompany,
                'activeRole' => $activeRole,
                'userCompanies' => $user->companies,
            ]);
        });
    }
}
