<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\ApiSetActiveCompany;
use App\Http\Middleware\EnsureFeatureEnabled;
use App\Http\Middleware\HandleMultipartTestRequests;
use App\Http\Middleware\SetActiveCompany;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            require __DIR__.'/../routes/session.php';
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(HandleMultipartTestRequests::class);

        $middleware->alias([
            'admin' => AdminMiddleware::class,
            'active.company' => SetActiveCompany::class,
            'api.company' => ApiSetActiveCompany::class,
            'ability' => CheckForAnyAbility::class,
            'abilities' => CheckAbilities::class,
            'feature' => EnsureFeatureEnabled::class,
        ]);

        $middleware->web(
            append: [
                SetActiveCompany::class,
            ],
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
