<?php

namespace App\Services;

use App\Jobs\SendFirebasePushJob;
use App\Models\DeviceToken;
use App\Models\Notification;
use App\Models\NotificationLog;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserFcmToken;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Exception\Messaging\NotFound;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\ApnsConfig;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FcmNotification;

class FirebaseNotificationService
{
    /** Pushes the Android app draws itself (see buildCloudMessage()). */
    public const DATA_ONLY_ANDROID_TYPES = ['game_invite'];

    protected ?Messaging $messaging = null;

    /**
     * Where a tap on this notification leads in the app: the route the caller
     * gave, else the type's route from config('matrimony.notifications.types').
     */
    public function routeFor(string $type, array $data = []): ?string
    {
        if (! empty($data['route'])) {
            return (string) $data['route'];
        }

        if ($type === 'game_invite' && ! empty($data['invite_id'])) {
            return '/games/invites/'.$data['invite_id'];
        }

        return config("matrimony.notifications.types.{$type}.route");
    }

    /**
     * Resolve the Firebase Messaging client using FCM HTTP v1.
     */
    public function messaging(): ?Messaging
    {
        if ($this->messaging !== null) {
            return $this->messaging;
        }

        $credentialsPath = $this->resolveCredentialsPath();

        if (! $credentialsPath || ! file_exists($credentialsPath)) {
            return null;
        }

        try {
            $factory = (new Factory)->withServiceAccount($credentialsPath);
            $projectId = Setting::get('firebase_project_id')
                ?: config('firebase.projects.app.project_id')
                ?: config('services.firebase.project_id');

            if ($projectId) {
                $factory = $factory->withProjectId($projectId);
            }

            $this->messaging = $factory->createMessaging();

            return $this->messaging;
        } catch (\Throwable $e) {
            Log::error('Firebase Messaging initialization failed: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Resolve the absolute path to the Firebase Service Account JSON.
     */
    public function resolveCredentialsPath(): ?string
    {
        $candidates = [
            storage_path('app/private/firebase/service-account.json'),
            storage_path('app/firebase/service-account.json'),
            Setting::get('firebase_credentials_path'),
            config('services.firebase.credentials'),
            config('firebase.projects.app.credentials'),
            env('FIREBASE_CREDENTIALS'),
        ];

        foreach ($candidates as $candidate) {
            if ($candidate && is_string($candidate)) {
                $path = file_exists($candidate) ? $candidate : base_path($candidate);
                if (file_exists($path) && ! is_dir($path)) {
                    return $path;
                }
            }
        }

        return null;
    }

    /**
     * Send push notification to a single user (queued by default).
     */
    public function sendToUser(
        int|User $user,
        string $title,
        string $body,
        array $data = [],
        string $type = 'general',
        bool $queued = true,
        bool $recordInApp = true
    ): bool {
        $userId = $user instanceof User ? $user->id : (int) $user;

        if ($queued) {
            SendFirebasePushJob::dispatchAfterResponse($userId, $title, $body, $data, $type, $recordInApp);

            return true;
        }

        return $this->sendDirectToUser($userId, $title, $body, $data, $type, $recordInApp);
    }

    /**
     * Send push notification to multiple users (queued by default).
     */
    public function sendToMultipleUsers(
        array $userIds,
        string $title,
        string $body,
        array $data = [],
        string $type = 'general',
        bool $queued = true
    ): int {
        $count = 0;
        foreach ($userIds as $userId) {
            $this->sendToUser((int) $userId, $title, $body, $data, $type, $queued);
            $count++;
        }

        return $count;
    }

    /**
     * Synchronous send logic for a specific user.
     */
    public function sendDirectToUser(
        int $userId,
        string $title,
        string $body,
        array $data = [],
        string $type = 'general',
        bool $recordInApp = true
    ): bool {
        // 1. Check global toggle
        if (! $this->isPushEnabledGlobally()) {
            $this->logNotification($userId, $type, $title, $body, $data, 'failed', 'Push notifications are disabled globally in Firebase Settings.');

            return false;
        }

        $user = User::with(['fcmTokens', 'deviceTokens', 'notificationSettings'])->find($userId);

        if (! $user) {
            $this->logNotification($userId, $type, $title, $body, $data, 'failed', 'User not found');

            return false;
        }

        // 2. Check user notification preference
        if (! $this->allowsType($user, $type)) {
            $this->logNotification($userId, $type, $title, $body, $data, 'failed', 'User has disabled notifications for type: '.$type);

            return false;
        }

        // Record in-app notification row so the user sees it in their notification list.
        // Skipped when NotificationDispatcher already wrote the row (its push
        // reaches here through PushSender) — otherwise every notification is
        // listed twice.
        if ($recordInApp) {
            try {
                Notification::create([
                    'user_id' => $userId,
                    'type' => $type,
                    'title' => $title,
                    'body' => $body,
                    'data' => $data + ['route' => $this->routeFor($type, $data)],
                    'channel' => 'in_app',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } catch (\Throwable $e) {
                Log::warning('In-app notification creation skipped: '.$e->getMessage());
            }
        }

        // 3. Collect all unique active tokens
        $tokens = array_unique(array_filter(array_merge(
            $user->fcmTokens->pluck('fcm_token')->all(),
            $user->deviceTokens->pluck('token')->all()
        )));

        if (empty($tokens)) {
            $this->logNotification($userId, $type, $title, $body, $data, 'failed', 'No active FCM tokens found for user');

            return false;
        }

        // 4. Send to all tokens
        $result = $this->sendToTokens($tokens, $title, $body, $data, $type, $userId);

        return $result['sent'] > 0;
    }

    /**
     * Send test push notification directly to a given token string.
     */
    public function sendDirectToToken(
        string $token,
        string $title,
        string $body,
        array $data = [],
        string $type = 'test',
        ?int $userId = null
    ): array {
        if (! $this->isPushEnabledGlobally()) {
            $error = 'Push notifications are disabled globally in Firebase Settings.';
            $this->logNotification($userId, $type, $title, $body, $data, 'failed', $error);

            return ['success' => false, 'error' => $error];
        }

        $messaging = $this->messaging();

        if (! $messaging) {
            $error = 'Firebase credentials not configured or service account JSON missing.';
            $this->logNotification($userId, $type, $title, $body, $data, 'failed', $error);

            return ['success' => false, 'error' => $error];
        }

        $message = $this->buildCloudMessage($token, $title, $body, $data, $type);

        try {
            $messaging->send($message);
            $this->logNotification($userId, $type, $title, $body, $data, 'sent', null);

            return ['success' => true, 'message' => 'Test notification sent successfully!'];
        } catch (\Throwable $e) {
            $errorMsg = $e->getMessage();
            Log::error('Firebase test push send error: '.$errorMsg);
            $this->logNotification($userId, $type, $title, $body, $data, 'failed', $errorMsg);

            return ['success' => false, 'error' => $errorMsg];
        }
    }

    /**
     * Send to multiple tokens, prune invalid ones, and log result.
     */
    public function sendToTokens(
        array $tokens,
        string $title,
        string $body,
        array $data,
        string $type,
        ?int $userId = null
    ): array {
        $messaging = $this->messaging();

        if (! $messaging) {
            $error = 'Firebase credentials not configured or service account JSON missing.';
            Log::warning('FCM sendToTokens skipped: '.$error);
            $this->logNotification($userId, $type, $title, $body, $data, 'failed', $error);

            return ['sent' => 0, 'failed' => count($tokens)];
        }

        $sent = 0;
        $failed = 0;
        $errors = [];
        $invalidTokens = [];

        foreach ($tokens as $token) {
            try {
                $cloudMessage = $this->buildCloudMessage($token, $title, $body, $data, $type);
                $messaging->send($cloudMessage);
                $sent++;
            } catch (\Throwable $e) {
                $failed++;
                $msg = $e->getMessage();
                $errors[] = $msg;

                // Check for unregistered or invalid token
                if (
                    $e instanceof NotFound
                    || str_contains(strtolower($msg), 'unregistered')
                    || str_contains(strtolower($msg), 'not found')
                    || str_contains(strtolower($msg), 'invalid registration')
                ) {
                    $invalidTokens[] = $token;
                }
            }
        }

        // Automatically prune invalid tokens
        if (! empty($invalidTokens)) {
            $this->pruneInvalidTokens($invalidTokens);
        }

        $status = $sent > 0 ? 'sent' : 'failed';
        $errorStr = ! empty($errors) ? implode('; ', array_slice($errors, 0, 3)) : null;

        $this->logNotification($userId, $type, $title, $body, $data, $status, $errorStr);

        return ['sent' => $sent, 'failed' => $failed];
    }

    /**
     * Build the FCM CloudMessage adhering to FCM HTTP v1 specification.
     */
    public function buildCloudMessage(
        string $token,
        string $title,
        string $body,
        array $data,
        string $type
    ): CloudMessage {
        $soundName = Setting::get('firebase_sound_name', 'default') ?: 'default';
        $channelId = Setting::get('firebase_android_channel_id', 'koodal_notifications') ?: 'koodal_notifications';

        // Prepare stringified data map
        $referenceId = isset($data['reference_id']) ? (string) $data['reference_id'] : '';
        $clickAction = $data['click_action'] ?? 'FLUTTER_NOTIFICATION_CLICK';

        // title/body/route ride in the data too: the app draws data-only
        // pushes itself (the game invite, with its Accept/Decline buttons) and
        // opens every tapped push by its route.
        $payloadData = [
            'type' => (string) $type,
            'reference_id' => $referenceId,
            'click_action' => (string) $clickAction,
            'title' => $title,
            'body' => $body,
            'route' => (string) ($this->routeFor($type, $data) ?? ''),
        ];

        foreach ($data as $k => $v) {
            if (! isset($payloadData[$k]) && $v !== null) {
                $payloadData[$k] = is_scalar($v) ? (string) $v : json_encode($v);
            }
        }

        $apnsSound = $soundName === 'default' ? 'default' : (str_ends_with($soundName, '.wav') ? $soundName : "{$soundName}.wav");

        // A game invite goes to Android data-only. Android only shows action
        // buttons the app adds itself, so with a `notification` block the OS
        // would draw a plain notice and the "Accept to Join" / "Decline"
        // buttons would never appear. iOS keeps its alert (APNs block below).
        $dataOnlyOnAndroid = in_array($type, self::DATA_ONLY_ANDROID_TYPES, true);

        $message = CloudMessage::withTarget('token', $token)->withData($payloadData);

        if (! $dataOnlyOnAndroid) {
            $message = $message->withNotification(FcmNotification::create($title, $body));
        }

        return $message
            ->withAndroidConfig(AndroidConfig::fromArray($dataOnlyOnAndroid
                ? ['priority' => 'high']
                : [
                    'priority' => 'high',
                    // No click_action: Android would look for an activity
                    // that declares that intent action, and a tap on a push
                    // with none to receive it opens nothing at all.
                    'notification' => array_filter([
                        'channel_id' => $channelId,
                        'sound' => $soundName,
                        'default_sound' => $soundName === 'default',
                        'tag' => isset($data['collapse_key']) ? (string) $data['collapse_key'] : null,
                    ], fn ($v) => $v !== null),
                ]))
            ->withApnsConfig(ApnsConfig::fromArray([
                'headers' => ['apns-priority' => '10'],
                'payload' => [
                    'aps' => [
                        'alert' => ['title' => $title, 'body' => $body],
                        'sound' => $apnsSound,
                    ],
                ],
            ]));
    }

    /**
     * Prune unregistered / invalid tokens from database.
     */
    protected function pruneInvalidTokens(array $tokens): void
    {
        try {
            UserFcmToken::whereIn('fcm_token', $tokens)->delete();
            DeviceToken::whereIn('token', $tokens)->delete();
        } catch (\Throwable $e) {
            Log::warning('Failed pruning invalid tokens: '.$e->getMessage());
        }
    }

    /**
     * Check if global push is enabled in Settings.
     */
    public function isPushEnabledGlobally(): bool
    {
        $enabled = Setting::get('firebase_push_enabled', true);

        if (is_string($enabled)) {
            return filter_var($enabled, FILTER_VALIDATE_BOOLEAN);
        }

        return (bool) $enabled;
    }

    /**
     * Check user notification preferences.
     */
    protected function allowsType(User $user, string $type): bool
    {
        // Essential moderation & system actions cannot be muted
        $forcedTypes = [
            'profile_approved',
            'profile_rejected',
            'photo_approved',
            'photo_rejected',
            'jadhagam_approved',
            'jadhagam_rejected',
            'account_warning',
            'account_suspended',
            'test',
        ];

        if (in_array($type, $forcedTypes, true)) {
            return true;
        }

        $settings = $user->notificationSettings;

        if ($settings && method_exists($settings, 'allows')) {
            return $settings->allows($type);
        }

        return true;
    }

    /**
     * Log to notification_logs table.
     */
    public function logNotification(
        ?int $userId,
        string $type,
        string $title,
        ?string $body,
        array $data,
        string $status,
        ?string $error = null
    ): void {
        try {
            NotificationLog::create([
                'user_id' => $userId,
                'type' => $type,
                'title' => $title,
                'body' => $body,
                'data' => $data,
                'status' => $status,
                'error' => $error,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to write to notification_logs: '.$e->getMessage());
        }
    }
}
