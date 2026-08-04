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
        'manage-platform' => [
            'label' => 'Manage Platform',
            'group' => 'System Administration',
        ],
        'view-approval-inbox' => [
            'label' => 'Inbox: Menu access for all approval requests assigned to you',
            'group' => 'Approval Management',
        ],
        'view-approval-history' => [
            'label' => 'History: Menu access to view past approval decisions and actions',
            'group' => 'Approval Management',
        ],
        'view-approval-requests' => [
            'label' => 'My Requests: Menu access to track approval requests you submitted',
            'group' => 'Approval Management',
        ],
        'manage-approvals' => [
            'label' => 'Manage Approvals: Administrative access to monitor, reassign, skip, or override any approval request',
            'group' => 'Approval Management',
        ],
        'manage-approval-workflows' => [
            'label' => 'Workflow Templates: Administrative access to create, edit, and configure approval workflow templates',
            'group' => 'Approval Management',
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
        'Approval Management',
        'System Administration',
    ],

];
