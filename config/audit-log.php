<?php

declare(strict_types=1);

return [
    'enabled' => (bool) env('AUDIT_LOG_ENABLED', true),

    'database' => [
        'connection' => env('AUDIT_LOG_DB_CONNECTION'),
        'table' => env('AUDIT_LOG_TABLE', 'audit_log'),
    ],

    'capture' => [
        'mode' => env('AUDIT_LOG_CAPTURE_MODE', 'all'),
        'request_context' => (bool) env('AUDIT_LOG_REQUEST_CONTEXT', false),
        'exclude' => [],
        'ignore_attributes' => [
            'created_at',
            'updated_at',
        ],
    ],

    'actor' => [
        'guards' => [],
        'impersonation_keys' => ['impersonated_by'],
    ],

    'timezone' => env('AUDIT_LOG_TIMEZONE', ''),

    'labels' => [
        'map' => [],
    ],

    'redaction' => [
        'placeholder' => '[redacted]',
        'keys' => [
            'password',
            'remember_token',
            'token',
            'secret',
            'authorization',
            'api_key',
            'credit_card',
            'ssn',
        ],
    ],

    'write' => [
        'async' => (bool) env('AUDIT_LOG_WRITE_ASYNC', false),
        'queue' => env('AUDIT_LOG_WRITE_QUEUE'),
    ],

    'alerts' => [
        'rules' => [],
        'mail_to' => [],
        'slack_webhook_url' => env('AUDIT_LOG_SLACK_WEBHOOK'),
        'webhook' => [
            'url' => env('AUDIT_LOG_WEBHOOK_URL'),
            'secret' => env('AUDIT_LOG_WEBHOOK_SECRET'),
        ],
    ],

    'stream' => [
        'enabled' => env('AUDIT_LOG_STREAM_ENABLED', false),
        'driver' => env('AUDIT_LOG_STREAM_DRIVER', 'http'),
        'endpoint' => env('AUDIT_LOG_STREAM_ENDPOINT'),
        'token' => env('AUDIT_LOG_STREAM_TOKEN'),
        'source' => env('AUDIT_LOG_STREAM_SOURCE', 'audit-log'),
        'headers' => [],
        'queue' => env('AUDIT_LOG_STREAM_QUEUE'),
    ],

    'tenancy' => [
        'resolver' => null,
    ],

    'anomalies' => [
        'window_minutes' => 60,
        'rate_threshold' => 200,
        'delete_threshold' => 25,
        'cascade_threshold' => 150,
        'off_hours' => [],
        'cron' => env('AUDIT_LOG_ANOMALY_CRON'),
        'rules' => [],
    ],

    'integrity' => [
        'enabled' => (bool) env('AUDIT_LOG_INTEGRITY', false),
        'signing' => [
            'private_key' => env('AUDIT_LOG_SIGNING_PRIVATE_KEY'),
            'public_key' => env('AUDIT_LOG_SIGNING_PUBLIC_KEY'),
        ],
        'digest_cron' => env('AUDIT_LOG_DIGEST_CRON'),
    ],

    'archive' => [
        'disk' => env('AUDIT_LOG_ARCHIVE_DISK', 'local'),
    ],

    'retention' => [
        'days' => (int) env('AUDIT_LOG_RETENTION_DAYS', 180),
        'schedule' => [
            'enabled' => (bool) env('AUDIT_LOG_RETENTION_SCHEDULE', true),
            'cron' => env('AUDIT_LOG_RETENTION_CRON', '0 3 * * *'),
        ],
    ],

    'integrations' => [
        'jobs_monitor' => [
            'url' => env('AUDIT_LOG_JOBS_MONITOR_URL'),
        ],
        'observability' => [
            'trace_url' => env('AUDIT_LOG_TRACE_URL'),
        ],
    ],

    'api' => [
        'enabled' => (bool) env('AUDIT_LOG_API_ENABLED', false),
        'path' => env('AUDIT_LOG_API_PATH', 'audit-log/api'),
        'middleware' => ['api'],
        'allow_unauthenticated' => (bool) env('AUDIT_LOG_API_ALLOW_UNAUTHENTICATED', false),
        'postman' => (bool) env('AUDIT_LOG_API_POSTMAN', true),
    ],

    'ui' => [
        'enabled' => (bool) env('AUDIT_LOG_UI_ENABLED', true),
        'path' => env('AUDIT_LOG_UI_PATH', 'audit-log'),
        'middleware' => ['web', 'auth'],
        'gate' => env('AUDIT_LOG_UI_GATE', 'viewAuditLog'),
        'throttle' => env('AUDIT_LOG_UI_THROTTLE', '60,1'),
    ],

    'transfer' => [
        'gate' => env('AUDIT_LOG_TRANSFER_GATE'),
    ],

    'playground' => [
        'gate' => env('AUDIT_LOG_PLAYGROUND_GATE'),
    ],
];
