<?php

namespace App\Jobs;

use App\Models\DeviceToken;
use App\Models\NotificationSetting;
use App\Models\User;
use App\Services\Notification\NotificationDispatcher;
use App\Services\Notification\PushSender;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * The admin announcement, fanned out.
 *
 * Two deliveries with different rules, on purpose:
 *
 *   • **The in-app row always lands.** `NotificationDispatcher::broadcast`
 *     bulk-inserts a thousand at a time — one insert, not N — so the badge and
 *     the notification list are correct for everybody, including people who
 *     have push switched off.
 *   • **The push is filtered.** Announcements are the channel members mute
 *     first, and pushing past a mute is how an app loses push permission
 *     permanently. Quiet hours are honoured too, and by dropping the push
 *     rather than deferring it: a deferred announcement arrives at 7am
 *     alongside everyone else's, which is a thundering herd for a message that
 *     was never urgent.
 *
 * `sendToTokens` handles FCM's 500-per-multicast limit itself, so this job
 * chunks users for memory and lets the sender chunk tokens for the API.
 */
class BroadcastAnnouncement implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 1800;

    /** @param  array<string, mixed>  $audience */
    public function __construct(
        public string $title,
        public string $body,
        public array $audience = [],
        public ?string $route = null,
    ) {
        $this->onQueue(config('queue.queues.default'));
    }

    public function handle(NotificationDispatcher $dispatcher, PushSender $push): void
    {
        $data = array_filter(['route' => $this->route]);
        $delivered = 0;
        $pushed = 0;

        $this->audienceQuery()->chunkById(1000, function ($users) use ($dispatcher, $push, $data, &$delivered, &$pushed) {
            $ids = $users->pluck('id')->all();

            $delivered += $dispatcher->broadcast($ids, $this->title, $this->body, $data);

            if ($tokens = $this->pushableTokens($ids)) {
                $push->sendToTokens($tokens, $this->title, $this->body, $data);
                $pushed += count($tokens);
            }
        });

        // Not an audit row: nothing was mutated and the *decision* to broadcast
        // is already audited at the controller, before the fan-out started.
        // This is the delivery record, and it belongs with the other
        // notification telemetry.
        Log::info('Announcement broadcast', [
            'title' => $this->title,
            'in_app' => $delivered,
            'push_tokens' => $pushed,
        ]);
    }

    // --- Internals ---------------------------------------------------------

    protected function audienceQuery()
    {
        return User::query()
            ->members()
            ->select('id')
            ->where('status', $this->audience['status'] ?? 'active')
            ->when($this->audience['gender'] ?? null, fn ($q, $v) => $q->where('gender', $v))
            ->when($this->audience['registration_source'] ?? null, fn ($q, $v) => $q->where('registration_source', $v))
            ->when($this->audience['plan_id'] ?? null, fn ($q, $v) => $q->whereHas(
                'activeSubscription',
                fn ($s) => $s->where('membership_plan_id', $v),
            ))
            // `paid_only` is a different question from a specific plan: it
            // means "everyone currently subscribed to anything that costs
            // money", which is the segment most campaigns actually want.
            ->when($this->audience['paid_only'] ?? false, fn ($q) => $q->whereHas(
                'activeSubscription.plan',
                fn ($p) => $p->where('price', '>', 0),
            ));
    }

    /**
     * Tokens belonging to users who allow the announcements channel and are
     * not inside their quiet hours.
     *
     * A user with no settings row has never opened the notification screen,
     * which is consent by default — the same reading `NotificationDispatcher`
     * applies when `$settings` is null.
     *
     * @param  array<int, int>  $userIds
     * @return array<int, string>
     */
    protected function pushableTokens(array $userIds): array
    {
        $settings = NotificationSetting::whereIn('user_id', $userIds)->get()->keyBy('user_id');

        $allowed = array_filter($userIds, function (int $id) use ($settings) {
            $row = $settings->get($id);

            if (! $row) {
                return true;
            }

            return $row->push_enabled && $row->allows('announcement') && ! $row->inQuietHours();
        });

        return $allowed
            ? DeviceToken::whereIn('user_id', $allowed)->pluck('token')->all()
            : [];
    }
}
