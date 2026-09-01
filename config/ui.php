<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Active UI Theme
    |--------------------------------------------------------------------------
    |
    | Defines the active UI theme used throughout the application layout,
    | navigation shells, and atomic UI components (<x-ui::*>).
    |
    | Supported: "tailadmin", "shadcn"
    |
    */
    'theme' => env('APP_UI_THEME', 'tailadmin'),

    /*
    |--------------------------------------------------------------------------
    | Fallback Theme
    |--------------------------------------------------------------------------
    |
    | If the configured theme is missing or invalid, the system falls back
    | to this default theme.
    |
    */
    'fallback' => 'tailadmin',

    /*
    |--------------------------------------------------------------------------
    | Available Themes Configuration
    |--------------------------------------------------------------------------
    |
    | Detailed metadata for each available theme.
    |
    */
    'themes' => [
        'tailadmin' => [
            'name' => 'TailAdmin',
            'font' => 'Outfit',
            'font_url' => 'https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&display=swap',
            'layout' => 'theme::layouts.app',
            'auth_layout' => 'theme::layouts.auth',
        ],
        'shadcn' => [
            'name' => 'shadcn/ui',
            'font' => 'Inter',
            'font_url' => 'https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap',
            'layout' => 'theme::layouts.app',
            'auth_layout' => 'theme::layouts.auth',
        ],
    ],
];
