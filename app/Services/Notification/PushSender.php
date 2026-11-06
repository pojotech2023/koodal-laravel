<?php

namespace App\Services\Notification;

use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Exception\Messaging\NotFound;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\ApnsConfig;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FcmNotification;

/**
 * FCM delivery.
 *
 * Two behaviours that matter more than the send itself:
 *
 *   • A token FCM reports as UNREGISTERED is deleted immediately. Retrying a
 *     dead token forever is how a project's FCM quota gets consumed by users
 *     who uninstalled months ago.
 *   • A failure never propagates. A push is a courtesy; an interest that was
 *     saved must not appear to have failed because Google was slow.
 */
class PushSender
{
    protected ?Messaging $messaging = null;

    protected function messaging(): Messaging
    {
        $credentials = (string) config('services.firebase.credentials');
        $path = file_exists($credentials) ? $credentials : base_path($credentials);

        return $this->messaging ??= (new Factory)
            ->withServiceAccount($path)
            ->createMessaging();
    }

    /** @return array{sent: int, failed: int} */
    public function sendToUser(User $user, string $title, string $body, array $data = []): array
    {
        try {
            $fcmService = app(\App\Services\FirebaseNotificationService::class);
            $type = (string) ($data['type'] ?? 'general');
            // false: NotificationDispatcher wrote the in-app row before queueing
            // this push; writing another listed every notification twice.
            $sent = $fcmService->sendDirectToUser($user->id, $title, $body, $data, $type, false);
            return $sent ? ['sent' => 1, 'failed' => 0] : ['sent' => 0, 'failed' => 0];
        } catch (\Throwable $e) {
            Log::warning('PushSender FirebaseNotificationService dispatch error: '.$e->getMessage());
        }

        $tokens = $user->deviceTokens()->pluck('token')->all();

        return $tokens === [] ? ['sent' => 0, 'failed' => 0] : $this->sendToTokens($tokens, $title, $body, $data);
    }

    public function sendToTokens(array $tokens, string $title, string $body, array $data = []): array
    {
        $credentials = (string) config('services.firebase.credentials');
        $hasCredentials = $credentials !== '' && (file_exists($credentials) || file_exists(base_path($credentials)));

        if (config('services.firebase.driver') === 'log' || ! $hasCredentials) {
            Log::info('FCM (skipped - log driver or missing credentials)', compact('tokens', 'title', 'body', 'data'));

            return ['sent' => count($tokens), 'failed' => 0];
        }

        $message = $this->message($title, $body, $data);

        $sent = 0;
        $failed = 0;

        foreach (array_chunk($tokens, config('matrimony.notifications.fcm_batch_size')) as $chunk) {
            try {
                $report = $this->messaging()->sendMulticast($message, $chunk);

                $sent += $report->successes()->count();
                $failed += $report->failures()->count();

                $this->pruneUnknownTokens($report->invalidTokens(), $report->unknownTokens());
            } catch (\Throwable $e) {
                $failed += count($chunk);

                Log::error('FCM send failed', ['error' => $e->getMessage(), 'tokens' => count($chunk)]);
            }
        }

        return ['sent' => $sent, 'failed' => $failed];
    }

    /** Notification types the app draws itself, with action buttons. */
    public const ACTIONABLE_TYPES = ['game_invite'];

    public function message(string $title, string $body, array $data = []): CloudMessage
    {
        $collapseKey = $data['collapse_key'] ?? null;

        // FCM data values must be strings. Passing an int here fails the whole
        // multicast with an unhelpful error.
        $data = array_map(fn ($v) => is_scalar($v) ? (string) $v : json_encode($v), $data);

        if (in_array($data['type'] ?? null, self::ACTIONABLE_TYPES, true)) {
            return $this->actionable($title, $body, $data);
        }

        $message = CloudMessage::new()
            ->withNotification(FcmNotification::create($title, $body))
            ->withData($data)
            ->withDefaultSounds();

        if ($collapseKey) {
            $message = $this->withCollapsing($message, (string) $collapseKey);
        }

        return $message;
    }

    /**
     * A game invite carries "Accept to Join" / "Decline" buttons. Android only
     * shows buttons the app adds itself, and it hands a message with a
     * `notification` block straight to the tray without waking the app — so on
     * Android this one is data-only and high priority, with the text in the
     * data, and the app's background handler draws it. iOS gets a normal
     * alert; tapping it opens the invite screen with the same two buttons.
     *
     * @param  array<string, string>  $data
     */
    protected function actionable(string $title, string $body, array $data): CloudMessage
    {
        return CloudMessage::new()
            ->withData($data + ['title' => $title, 'body' => $body])
            ->withAndroidConfig(AndroidConfig::fromArray(['priority' => 'high']))
            ->withApnsConfig(ApnsConfig::fromArray([
                'headers' => ['apns-priority' => '10'],
                'payload' => ['aps' => [
                    'alert' => ['title' => $title, 'body' => $body],
                    'sound' => 'default',
                ]],
            ]));
    }

    /**
     * Coalescing, so twelve unread messages in one thread replace each other in
     * the tray instead of stacking into twelve rows a user clears by disabling
     * notifications.
     *
     * A `collapse_key` in the data payload is NOT enough on its own — that is
     * just a string the app can read. The platforms only collapse on their own
     * fields, which is why this exists: `collapse_key` in AndroidConfig and
     * `apns-collapse-id` in the APNs headers. Both are capped by the platform
     * (APNs at 64 bytes), and the keys this application sends —
     * `conversation-{id}` — are comfortably inside that.
     *
     * Priority is raised alongside it: the only things worth collapsing here are
     * chat messages, and a coalesced message that arrives in a batched window is
     * a message that arrives after the conversation moved on.
     */
    protected function withCollapsing(CloudMessage $message, string $collapseKey): CloudMessage
    {
        return $message
            ->withAndroidConfig(AndroidConfig::fromArray([
                'collapse_key' => $collapseKey,
                'priority' => 'high',
            ]))
            ->withApnsConfig(ApnsConfig::fromArray([
                'headers' => [
                    'apns-collapse-id' => $collapseKey,
                    'apns-priority' => '10',
                ],
            ]));
    }

    /** @param  array<int, string>  ...$tokenLists */
    protected function pruneUnknownTokens(array ...$tokenLists): void
    {
        $dead = array_merge(...$tokenLists);

        if ($dead !== []) {
            DeviceToken::whereIn('token', $dead)->delete();
        }
    }

    /** Topic broadcast, used only by the admin announcement endpoint. */
    public function sendToTopic(string $topic, string $title, string $body, array $data = []): bool
    {
        if (config('services.firebase.driver') === 'log') {
            Log::info('FCM topic (log driver)', compact('topic', 'title', 'body', 'data'));

            return true;
        }

        try {
            $this->messaging()->send(
                CloudMessage::withTarget('topic', $topic)
                    ->withNotification(FcmNotification::create($title, $body))
                    ->withData(array_map(fn ($v) => (string) $v, $data))
            );

            return true;
        } catch (NotFound|\Throwable $e) {
            Log::error('FCM topic send failed', ['topic' => $topic, 'error' => $e->getMessage()]);

            return false;
        }
    }
}
