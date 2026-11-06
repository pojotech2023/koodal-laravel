<?php

return [

    'name' => env('APP_NAME', 'Matrimony'),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),

    // Asia/Kolkata, not UTC. Every scheduled job in this product is expressed
    // in the user's local reality — a "10:00 expiry reminder" means 10:00 IST,
    // and a digest at 19:00 UTC would land at 00:30 IST.
    'timezone' => env('APP_TIMEZONE', 'Asia/Kolkata'),

    'locale' => env('APP_LOCALE', 'en'),
    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),
    'faker_locale' => env('APP_FAKER_LOCALE', 'en_IN'),

    'cipher' => 'AES-256-CBC',
    'key' => env('APP_KEY'),
    'previous_keys' => array_filter(explode(',', (string) env('APP_PREVIOUS_KEYS', ''))),

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

    // Public URLs the API links back to in mail, notifications and invoices.
    'frontend_url' => env('FRONTEND_URL', 'http://localhost:5173'),
    'admin_url' => env('ADMIN_URL', 'http://localhost:5174'),

];
