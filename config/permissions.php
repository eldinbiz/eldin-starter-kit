<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Available Permissions
    |--------------------------------------------------------------------------
    |
    | This defines all assignable permissions in the system. Each permission
    | has a unique key, a human-readable label, and a group for organizing
    | the permission checkboxes in the admin UI.
    |
    | Permissions are stored as JSON on each role record and enforced via
    | Laravel Gates registered in AppServiceProvider.
    |
    */

    'list' => [
        'manage-users' => [
            'label' => 'Manage Users',
            'group' => 'User Management',
        ],
        'manage-roles' => [
            'label' => 'Manage Roles',
            'group' => 'Role Management',
        ],
        'view-dashboard' => [
            'label' => 'View Dashboard',
            'group' => 'General',
            'always_enabled' => true,
        ],
        'manage-org-chart' => [
            'label' => 'Manage Organization Chart',
            'group' => 'System Administration',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Permission Groups (for UI display order)
    |--------------------------------------------------------------------------
    */
    'groups' => [
        'General',
        'User Management',
        'Role Management',
        'System Administration',
    ],

];
