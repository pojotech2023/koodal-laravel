<?php

return [

    'default' => env('FILESYSTEM_DISK', 'local'),

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => rtrim(env('APP_URL', ''), '/').'/storage',
            'visibility' => 'public',
            'throw' => false,
        ],

        /*
        |-----------------------------------------------------------------------
        | s3 — approved profile photos
        |-----------------------------------------------------------------------
        | Public-read, CDN-fronted. Only moderated photos ever land here.
        */

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'ap-south-1'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('CDN_URL', env('AWS_URL')),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => (bool) env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'visibility' => 'public',
            'throw' => false,
        ],

        /*
        |-----------------------------------------------------------------------
        | private — verification documents and chat images
        |-----------------------------------------------------------------------
        | Never public-read, never CDN-fronted, never listed. Access is only
        | ever a short-lived signed URL minted by an audited endpoint. A
        | government ID with a permanent public URL is the single worst leak
        | this system could produce.
        */

        'private' => [
            'driver' => env('AWS_ACCESS_KEY_ID') ? 's3' : 'local',
            'root' => storage_path('app/private'),
            'url' => rtrim(env('APP_URL', ''), '/').'/storage/private',
            'serve' => true,
            'permissions' => [
                'file' => [
                    'public' => 0644,
                    'private' => 0644,
                ],
                'dir' => [
                    'public' => 0755,
                    'private' => 0755,
                ],
            ],
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'ap-south-1'),
            'bucket' => env('AWS_PRIVATE_BUCKET'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => (bool) env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'visibility' => 'private',
            'throw' => false,
        ],

    ],

    'links' => [
        public_path('storage') => storage_path('app/public'),
        public_path('storage/private') => storage_path('app/private'),
    ],

];
