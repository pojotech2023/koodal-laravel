<?php

namespace App\Providers;

use App\Services\Chat\Contracts\RealtimeTransport;
use App\Services\Chat\FirebaseTransport;
use App\Services\Chat\ReverbTransport;
use Illuminate\Support\ServiceProvider;

/**
 * Swapping the realtime backend is one env var. No controller, Action or
 * client feature flag moves.
 */
class ChatServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(RealtimeTransport::class, fn () => match (config('matrimony.chat.transport')) {
            'firebase' => $this->app->make(FirebaseTransport::class),
            default => $this->app->make(ReverbTransport::class),
        });
    }
}
