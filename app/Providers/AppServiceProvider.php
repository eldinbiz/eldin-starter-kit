<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
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

        // Share the active company with all views
        view()->composer('*', function ($view): void {
            if (auth()->check()) {
                $view->with('activeCompany', auth()->user()?->getActiveCompany());
            }
        });
    }
}
