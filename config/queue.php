<?php

return [

    'default' => env('QUEUE_CONNECTION', 'redis'),

    'connections' => [

        'sync' => ['driver' => 'sync'],

        'database' => [
            'driver' => 'database',
            'connection' => env('DB_QUEUE_CONNECTION'),
            'table' => env('DB_QUEUE_TABLE', 'jobs'),
            'queue' => env('DB_QUEUE', 'default'),
            'retry_after' => (int) env('DB_QUEUE_RETRY_AFTER', 90),
            'after_commit' => false,
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => env('REDIS_QUEUE_CONNECTION', 'default'),
            'queue' => env('REDIS_QUEUE', 'default'),
            'retry_after' => (int) env('REDIS_QUEUE_RETRY_AFTER', 300),
            'block_for' => null,

            // Jobs dispatched inside a transaction must not run before it
            // commits — otherwise a push notification arrives for a row that
            // is rolled back a millisecond later.
            'after_commit' => true,
        ],

    ],

    /*
    |---------------------------------------------------------------------------
    | Queues by latency budget
    |---------------------------------------------------------------------------
    | Separate workers per queue so a 40-second image resize cannot delay an
    | interest push notification. Worker counts live in the supervisor config.
    */

    'queues' => [
        'high' => 'high',            // push notifications, OTP
        'default' => 'default',      // ordinary domain work
        'media' => 'media',          // image compression, thumbnails
        'reports' => 'reports',      // exports, invoices, analytics
        'matching' => 'matching',    // nightly score recomputation
    ],

    'batching' => [
        'database' => env('DB_CONNECTION', 'mysql'),
        'table' => 'job_batches',
    ],

    'failed' => [
        'driver' => env('QUEUE_FAILED_DRIVER', 'database-uuids'),
        'database' => env('DB_CONNECTION', 'mysql'),
        'table' => 'failed_jobs',
    ],

];
