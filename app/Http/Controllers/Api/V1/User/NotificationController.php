<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Notification\UpdateNotificationSettingsRequest;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use App\Models\NotificationSetting;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()->notifications()
            ->ofType($request->query('type'))
            ->when($request->boolean('unread'), fn ($query) => $query->unread())
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return ApiResponse::paginated($notifications, NotificationResource::class, 'Notifications retrieved successfully');
    }

    /**
     * The badge. Polled on every app foreground, so it stays a COUNT against
     * the (user_id, read_at, created_at) index and never loads a row.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        return ApiResponse::success(
            ['unread_count' => $request->user()->notifications()->unread()->count()],
            'Unread count retrieved successfully',
        );
    }

    public function read(Notification $notification, Request $request): JsonResponse
    {
        $this->authoriseOwnership($notification, $request->user());

        // Guarded so re-reading does not keep moving read_at forward — the
        // timestamp records when they first saw it.
        if (! $notification->isRead()) {
            $notification->update(['read_at' => now()]);
        }

        return ApiResponse::success(new NotificationResource($notification), 'Notification marked as read');
    }

    public function readAll(Request $request): JsonResponse
    {
        $marked = $request->user()->notifications()->unread()->update(['read_at' => now()]);

        return ApiResponse::success(['marked_read' => $marked], 'All notifications marked as read');
    }

    public function destroy(Notification $notification, Request $request): JsonResponse
    {
        $this->authoriseOwnership($notification, $request->user());

        $notification->delete();

        return ApiResponse::noContent();
    }

    public function settings(Request $request): JsonResponse
    {
        return ApiResponse::success(
            $this->settingsPayload($request->user()->notificationSettings),
            'Notification settings retrieved successfully',
        );
    }

    public function updateSettings(UpdateNotificationSettingsRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->safe()->except('types');

        // `types` is merged rather than replaced: the client sends the one
        // channel the user just toggled, and a replace would silently re-enable
        // every channel not present in that payload.
        if ($request->has('types')) {
            $data['types'] = array_merge(
                $user->notificationSettings?->types ?? [],
                $request->input('types'),
            );
        }

        $settings = NotificationSetting::updateOrCreate(['user_id' => $user->id], $data);

        return ApiResponse::success($this->settingsPayload($settings), 'Notification settings updated');
    }

    // --- Internals ---------------------------------------------------------

    /**
     * 404, not 403. A notification that is not yours is one you have no way of
     * knowing exists, and answering "forbidden" to a guessed id confirms it
     * does.
     */
    protected function authoriseOwnership(Notification $notification, User $user): void
    {
        abort_unless($notification->user_id === $user->id, 404);
    }

    /**
     * Fallbacks mirror the column defaults in the migration. An account created
     * before notification settings existed has no row, and a GET must not write
     * one — so the defaults are rendered rather than persisted.
     */
    protected function settingsPayload(?NotificationSetting $settings): array
    {
        return [
            'push_enabled' => (bool) ($settings?->push_enabled ?? true),
            'email_enabled' => (bool) ($settings?->email_enabled ?? true),
            'sms_enabled' => (bool) ($settings?->sms_enabled ?? false),

            // Every known channel is returned with its effective value, so the
            // settings screen can be rendered from this response alone. A
            // channel absent from the stored map is on — new channels default
            // to enabled, matching NotificationSetting::allows().
            'types' => collect(UpdateNotificationSettingsRequest::channels())
                ->mapWithKeys(fn (string $channel) => [
                    $channel => (bool) (($settings?->types ?? [])[$channel] ?? true),
                ])
                ->all(),

            'quiet_hours' => [
                'enabled' => (bool) ($settings?->quiet_hours_enabled ?? false),
                'start' => $settings?->quiet_hours_start,
                'end' => $settings?->quiet_hours_end,
                // Quiet hours are a local-time concept and the columns carry no
                // offset, so they are evaluated in the application timezone.
                'timezone' => config('app.timezone'),
            ],
        ];
    }
}
