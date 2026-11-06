<?php

namespace App\Services\Notification;

use App\Jobs\SendPushNotification;
use App\Models\Notification;
use App\Models\User;

/**
 * The one way a notification is created.
 *
 * Three gates run before anything is written, in this order:
 *
 *   1. the type must exist in config — an unknown type cannot be dispatched,
 *      so a typo fails at the call site rather than sending gibberish;
 *   2. the user's per-channel setting must allow it;
 *   3. quiet hours defer the *push*, not the record. The in-app row is written
 *      immediately so the badge is correct the moment they open the app.
 *
 * Digest types are never pushed individually — SendNotificationDigest batches
 * them. Sending "someone viewed your profile" eleven times in an evening is the
 * fastest way to get push permission revoked, and revoked permission does not
 * come back.
 */
class NotificationDispatcher
{
    /**
     * @param  bool  $force  bypasses the user's channel preference, and only
     *                       that — quiet hours and digesting still apply.
     *                       Reserved for notices a member does not get to opt
     *                       out of: a warning, a suspension, the outcome of a
     *                       report they filed. Everything else is a courtesy
     *                       and a muted channel means muted.
     */
    public function send(User $user, string $type, string $title, ?string $body = null, array $data = [], bool $force = false): ?Notification
    {
        $definition = config("matrimony.notifications.types.{$type}");

        if (! $definition) {
            throw new \InvalidArgumentException("Unknown notification type [{$type}].");
        }

        $settings = $user->notificationSettings;

        if (! $force && $settings && ! $settings->allows($type)) {
            return null;
        }

        $notification = Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => $data + ['route' => $definition['route'] ?? null],
            'channel' => 'in_app',
        ]);

        // Digest types accumulate and go out in the evening batch.
        if ($definition['digest'] ?? false) {
            return $notification;
        }

        try {
            if ($this->shouldPush($user, $settings)) {
                // After the response, in this same process: no queue worker is needed
                // (the host has none unless the cron is set up).
                SendPushNotification::dispatchAfterResponse($notification->id);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Push notification dispatch failed: '.$e->getMessage(), [
                'notification_id' => $notification->id,
                'user_id' => $user->id,
            ]);
        }

        return $notification;
    }

    /** Bulk path for the admin announcement — one insert, not N. */
    public function broadcast(iterable $users, string $title, string $body, array $data = []): int
    {
        $rows = [];
        $now = now();

        foreach ($users as $user) {
            $rows[] = [
                'user_id' => is_object($user) ? $user->id : $user,
                'type' => 'announcement',
                'title' => $title,
                'body' => $body,
                'data' => json_encode($data + ['route' => '/notifications']),
                'channel' => 'in_app',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($rows, 1000) as $chunk) {
            Notification::insert($chunk);
        }

        return count($rows);
    }

    protected function shouldPush(User $user, $settings): bool
    {
        if ($settings && ! $settings->push_enabled) {
            return false;
        }

        // Deferred, not dropped: the record already exists, so the badge is
        // right and the digest will carry it.
        return ! ($settings?->inQuietHours() ?? false);
    }
}
