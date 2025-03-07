<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Production Server Configuration
    |--------------------------------------------------------------------------
    |
    | These values will be used when no command line options are provided.
    | Configure your production server details here or in your .env file.
    |
    */
    'production' => [
        'host' => env('PROD_SSH_HOST', ''),
        'user' => env('PROD_SSH_USER', ''),
        'path' => env('PROD_SSH_PATH', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Sync Settings
    |--------------------------------------------------------------------------
    |
    | Configure database sync settings including dump location and tables to exclude.
    |
    */
    'database' => [
        'dump_path' => storage_path('dumps'),
        'tables' => [
            'exclude' => [
                // Tables to exclude from sync, e.g.:
                // 'password_reset_tokens',
                // 'failed_jobs',
                // 'jobs',
                // 'sessions',
                // 'personal_access_tokens',
                // 'visitor',
                // 'activity_log',
                // 'audit_logs',
                // 'cache',
                // 'notifications',
                // 'telescope_entries',
                // 'telescope_entries_tags',
                // 'telescope_monitoring',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | File Sync Settings
    |--------------------------------------------------------------------------
    |
    | Configure which paths to sync from production and patterns to exclude.
    | The 'paths' array should contain absolute paths to directories you want to sync.
    | The 'exclude' array contains patterns for files/directories to exclude from sync.
    |
    */
    'files' => [
        'paths' => [
            storage_path('app'),
            // Add more paths to sync, e.g.:
            // public_path('uploads'),
        ],
        'exclude' => [
            '*.log',
            '.git',
            'node_modules',
            'vendor',
            '.env',
            '.env.*',
        ],
    ],
]; 