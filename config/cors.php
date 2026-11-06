<?php

/*
|-------------------------------------------------------------------------------
| CORS
|-------------------------------------------------------------------------------
| Explicit origins, never '*'. The mobile app sends no Origin header at all, so
| a wildcard buys nothing and costs the ability to use credentialed requests
| from the two browser clients.
*/

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'broadcasting/auth'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_filter([
        env('FRONTEND_URL'),
        env('ADMIN_URL'),
    ])),

    'allowed_origins_patterns' => array_filter(explode(',', (string) env('CORS_ORIGIN_PATTERNS', ''))),

    'allowed_headers' => ['*'],

    'exposed_headers' => ['X-Request-Id', 'Retry-After'],

    'max_age' => 86400,

    'supports_credentials' => true,

];
