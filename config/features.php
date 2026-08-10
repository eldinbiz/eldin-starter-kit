<?php

declare(strict_types=1);

return [
    'approvals' => [
        'label' => 'Approvals',
        'description' => 'Approval inbox, history, requests, manage, and workflow templates',
        'default_enabled' => true,
        'bypass_permissions' => ['manage-approvals', 'manage-approval-workflows'],
        'setting_key' => 'feature_approvals',
    ],
    'audit_logs' => [
        'label' => 'Audit Logs',
        'description' => 'System audit trail viewer',
        'default_enabled' => true,
        'bypass_permissions' => ['view-audit-logs'],
        'setting_key' => 'feature_audit_logs',
    ],
    'api_access' => [
        'label' => 'API Access',
        'description' => 'REST API endpoints and API token management',
        'default_enabled' => true,
        'bypass_permissions' => ['manage-api-tokens'],
        'setting_key' => 'feature_api_access',
    ],
];
