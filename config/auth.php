<?php

use App\Models\User;

return [

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'sanctum'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
    ],

    /*
    |---------------------------------------------------------------------------
    | Guards
    |---------------------------------------------------------------------------
    | One guard, one user table. Staff and members are separated by token
    | *abilities* (user:* vs admin:*) rather than by separate guards or tables —
    | a member who is later promoted to moderator keeps their id, their audit
    | history and their foreign keys.
    */

    'guards' => [
        'web' => ['driver' => 'session', 'provider' => 'users'],
        'sanctum' => ['driver' => 'sanctum', 'provider' => 'users'],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => User::class,
        ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => 10800,

];
