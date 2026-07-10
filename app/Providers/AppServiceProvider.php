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
    }
}
