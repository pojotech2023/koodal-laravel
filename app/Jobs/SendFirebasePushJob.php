<?php

namespace App\Jobs;

use App\Services\FirebaseNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendFirebasePushJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 10;

    public function __construct(
        public int $userId,
        public string $title,
        public string $body,
        public array $data = [],
        public string $type = 'general',
        public bool $recordInApp = true,
    ) {
        // The same queue as every other push (SendPushNotification). A queue of
        // its own ('notifications') is one no worker is started for, so these
        // jobs sat in the table and were never sent.
        $this->onQueue(config('queue.queues.high', 'high'));
    }

    public function handle(FirebaseNotificationService $service): void
    {
        $service->sendDirectToUser(
            $this->userId,
            $this->title,
            $this->body,
            $this->data,
            $this->type,
            $this->recordInApp,
        );
    }
}
