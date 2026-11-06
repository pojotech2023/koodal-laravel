<?php

use App\Providers\AppServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\ChatServiceProvider;
use App\Providers\EventServiceProvider;

return [
    AppServiceProvider::class,
    ChatServiceProvider::class,
    AuthServiceProvider::class,
    EventServiceProvider::class,
];
