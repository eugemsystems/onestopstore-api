<?php

return [

    // Filesystem disk the database dumps are uploaded to.
    'disk' => env('BACKUP_DISK', 'r2'),

    // Prefix (folder) within that disk's bucket. Each target below gets its
    // own sub-folder under this, e.g. backups/app/, backups/media/, backups/crm/.
    'path' => env('BACKUP_PATH', 'backups'),

    // Path to the pg_dump binary. Override if it isn't on PATH.
    'pg_dump_path' => env('PG_DUMP_PATH', 'pg_dump'),

    // When set, every dump is encrypted with AES-256-CBC (openssl) before upload —
    // recommended since these databases contain customer PII. Generate a key with:
    //   openssl rand -base64 32
    'encryption_key' => env('BACKUP_ENCRYPTION_KEY'),

    // Slack webhook (see config/slack.php) used to alert on backup failure/success.
    'notify_on_success' => (bool) env('BACKUP_NOTIFY_ON_SUCCESS', false),

    /*
    |--------------------------------------------------------------------------
    | Backup targets
    |--------------------------------------------------------------------------
    |
    | Each target is a separate Postgres database, dumped and retained
    | independently in its own R2 sub-folder. This app's own DB reuses the
    | existing DB_* connection vars; media/crm need their own *_DB_* vars
    | filled in per environment since their credentials/database names
    | differ between local and production.
    |
    | Retention per target: after upload, the newest backups are kept while
    | the running total stays under that target's max_bytes, always keeping
    | at least keep_min regardless of size. Defaults below sum to ~9GB total
    | across all three targets — under R2's 10GB free tier — but are only a
    | starting point; tune each *_MAX_BYTES once you know real dump sizes.
    |
    | A target only runs if `enabled` is true AND host/database/username are
    | all non-empty — so leaving media/crm unconfigured just skips them
    | (logged as a warning) rather than failing the whole backup run.
    |
    */
    'targets' => [

        'app' => [
            'label' => 'Main App (laravel-api)',
            'enabled' => (bool) env('BACKUP_APP_ENABLED', true),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE'),
            'username' => env('DB_USERNAME'),
            'password' => env('DB_PASSWORD'),
            'max_bytes' => (int) env('BACKUP_APP_MAX_BYTES', 6 * 1000 * 1000 * 1000),
            'keep_min' => (int) env('BACKUP_APP_KEEP_MIN', 1),
        ],

        'media' => [
            'label' => 'Media Service (laravel-media)',
            'enabled' => (bool) env('BACKUP_MEDIA_ENABLED', false),
            'host' => env('MEDIA_DB_HOST'),
            'port' => env('MEDIA_DB_PORT', '5432'),
            'database' => env('MEDIA_DB_DATABASE'),
            'username' => env('MEDIA_DB_USERNAME'),
            'password' => env('MEDIA_DB_PASSWORD'),
            'max_bytes' => (int) env('BACKUP_MEDIA_MAX_BYTES', 2 * 1000 * 1000 * 1000),
            'keep_min' => (int) env('BACKUP_MEDIA_KEEP_MIN', 1),
        ],

        'crm' => [
            'label' => 'CRM',
            'enabled' => (bool) env('BACKUP_CRM_ENABLED', false),
            'host' => env('CRM_DB_HOST'),
            'port' => env('CRM_DB_PORT', '5432'),
            'database' => env('CRM_DB_DATABASE'),
            'username' => env('CRM_DB_USERNAME'),
            'password' => env('CRM_DB_PASSWORD'),
            'max_bytes' => (int) env('BACKUP_CRM_MAX_BYTES', 1 * 1000 * 1000 * 1000),
            'keep_min' => (int) env('BACKUP_CRM_KEEP_MIN', 1),
        ],

    ],

];
