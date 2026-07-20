<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

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

        // Platform-level gate: checks ALL roles across ALL companies
        Gate::define('manage-platform', function (User $user): bool {
            return $user->hasPlatformAccess();
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
