<?php

declare(strict_types=1);

use Dedoc\Scramble\Http\Middleware\RestrictedDocsAccess;

return [
    /*
     |--------------------------------------------------------------------------
     | API Route Scope
     |--------------------------------------------------------------------------
     | All routes starting with this path prefix will be analyzed and added to the
     | generated OpenAPI documentation.
     */
    'api_path' => 'api',

    'api_domain' => null,

    /*
     |--------------------------------------------------------------------------
     | Specification Export Path
     |--------------------------------------------------------------------------
     */
    'export_path' => 'api.json',

    /*
     |--------------------------------------------------------------------------
     | API Information
     |--------------------------------------------------------------------------
     */
    'info' => [
        'version' => env('API_VERSION', '1.0.0'),
        'description' => 'Multi-Company ERP Scaffolding REST API documentation with Sanctum token authentication and tenant scoping.',
    ],

    /*
     |--------------------------------------------------------------------------
     | UI Configuration
     |--------------------------------------------------------------------------
     */
    'ui' => [
        'title' => 'ERP Multi-Company API Documentation',
        'theme' => 'light',
        'hide_try_it' => false,
        'logo' => '',
        'try_it_credentials_policy' => 'include',
    ],

    /*
     |--------------------------------------------------------------------------
     | Middleware
     |--------------------------------------------------------------------------
     */
    'middleware' => [
        'web',
        RestrictedDocsAccess::class,
    ],

    'extensions' => [],
];
