<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Staging directory
    |--------------------------------------------------------------------------
    | Where dumps are built before being handed to a destination driver.
    */
    'staging_path' => env('BACKUP_STAGING_PATH', storage_path('app/backup-staging')),

    /*
    |--------------------------------------------------------------------------
    | Database dump
    |--------------------------------------------------------------------------
    | "mysqldump_path" may be a full path (e.g. C:\xampp\mysql\bin\mysqldump.exe).
    | When the binary cannot be found the built-in PHP dumper is used instead,
    | which produces an equivalent dump (tables, data, indexes, FKs, views,
    | routines, triggers) without needing any external tool.
    */
    'database_connection' => env('BACKUP_DB_CONNECTION', null), // null = default connection
    'mysqldump_path'      => env('BACKUP_MYSQLDUMP_PATH', null),
    'mysql_path'          => env('BACKUP_MYSQL_PATH', null),
    'dump_driver'         => env('BACKUP_DUMP_DRIVER', 'auto'), // auto | mysqldump | php

    // Tables never dumped nor restored: the Backup Manager's own state and
    // volatile framework tables (restoring those would corrupt a live restore).
    'excluded_tables' => [
        'backup_settings', 'backup_destinations', 'backups', 'backup_logs',
        'jobs', 'job_batches', 'failed_jobs', 'cache', 'cache_locks', 'sessions',
    ],

    'filename_prefix' => env('BACKUP_FILENAME_PREFIX', 'inventory_backup'),

    /*
    |--------------------------------------------------------------------------
    | Reliability
    |--------------------------------------------------------------------------
    */
    'retry_delays'          => [60, 300, 900],   // seconds; 3 retries, increasing delay
    'stale_after_minutes'   => 20,               // "running" with no heartbeat => interrupted
    'job_timeout'           => 7200,             // seconds a single job may run
    'min_free_space_mb'     => 100,              // absolute floor before a backup is attempted
    'space_safety_factor'   => 1.25,             // required = estimated size * factor
    'low_storage_percent'   => 10,               // warn when free space drops below this %
    'staging_keep_hours'    => 48,

    /*
    |--------------------------------------------------------------------------
    | Google Drive (OAuth 2.0 - web server flow)
    |--------------------------------------------------------------------------
    | Create an OAuth client in Google Cloud Console and add the redirect URI
    | shown on the Backup Settings page. Secrets live in .env only.
    | Scope "drive.file" limits access to files/folders this app creates.
    */
    'google' => [
        'client_id'     => env('BACKUP_GOOGLE_CLIENT_ID'),
        'client_secret' => env('BACKUP_GOOGLE_CLIENT_SECRET'),
        'scope'         => 'https://www.googleapis.com/auth/drive.file',
        'chunk_size'    => 8 * 1024 * 1024, // must be a multiple of 256 KiB
        'timeout'       => 120,
    ],

    'frequencies' => [
        'manual'         => 'Manual only',
        'every_hour'     => 'Every hour',
        'every_6_hours'  => 'Every 6 hours',
        'every_12_hours' => 'Every 12 hours',
        'daily'          => 'Daily',
        'weekly'         => 'Weekly',
        'monthly'        => 'Monthly',
    ],
];
