<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminNotificationResource;
use App\Models\Notification;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * `GET /admin/notifications` — the delivery log.
 *
 * This answers one operational question: is the notification pipeline
 * working. FCM fails quietly and in bulk — a rotated service account, an
 * expired token store, a quiet-hours bug — and the first symptom is a support
 * queue full of "I never got told". Without this endpoint the only way to see
 * that is to query the database by hand.
 *
 * Read-only, and metadata-only. `AdminNotificationResource` withholds `title`
 * and `body` because message notifications carry a chat preview in the body;
 * the reasoning is in that class and it is not a detail to optimise away.
 *
 * Named `NotificationLogController`, not `NotificationManagementController`,
 * because there is nothing to manage: notifications are produced by listeners
 * and jobs, and the only staff-authored one goes out through
 * `CmsManagementController::broadcast`.
 */
class NotificationLogController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        // Not a Policy: there is no NotificationPolicy and there should not be
        // one — no per-record rule exists to express. The permission is the
        // whole gate, the way `AuditLogController` treats `settings.audit`.
        $this->authorize('notifications.view');

        $request->validate([
            'type' => ['nullable', 'string', 'max:40'],

            // Only two values ever reach this column. `NotificationDispatcher`
            // writes `in_app` on creation and `SendPushNotification` flips it to
            // `push` once a device accepted it — email and SMS do not produce
            // rows in this table, so offering them as filters would be four
            // options where two of them always return nothing.
            'channel' => ['nullable', 'in:in_app,push'],

            'user_id' => ['nullable', 'integer'],
            'state' => ['nullable', 'in:pushed,not_pushed,failed,unread,read'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $notifications = Notification::query()
            ->with('user:id,profile_code,name')

            ->ofType($request->string('type')->toString() ?: null)
            ->when($request->filled('channel'), fn ($q) => $q->where('channel', $request->input('channel')))
            ->when($request->integer('user_id'), fn ($q, $id) => $q->where('user_id', $id))

            // `state` collapses five mutually exclusive filters into one
            // parameter, because they are five answers to the same question and
            // a client that can ask for two at once will.
            //
            // Read `sent_at` as "the push escaped", not "the notification
            // exists" — `SendPushNotification` is the only writer of it. So
            // `not_pushed` is NOT a queue backlog. Four different things land
            // in it: a job still queued, a digest type waiting for the evening
            // batch, a member whose quiet hours or channel settings suppressed
            // the push, and every `announcement` row, because
            // `BroadcastAnnouncement` sends its pushes through
            // `PushSender::sendToTokens` directly and never touches this
            // column. Filter by `type` alongside it or the number means
            // nothing.
            ->when($request->filled('state'), fn ($q) => match ($request->input('state')) {
                'failed' => $q->whereNotNull('failed_at'),
                'pushed' => $q->whereNotNull('sent_at'),
                'not_pushed' => $q->whereNull('sent_at')->whereNull('failed_at'),
                'unread' => $q->unread(),
                default => $q->whereNotNull('read_at'),
            })

            ->when($request->date('from'), fn ($q, $from) => $q->where('created_at', '>=', $from->startOfDay()))
            ->when($request->date('to'), fn ($q, $to) => $q->where('created_at', '<=', $to->endOfDay()))

            // By id, not by created_at. This table takes the heaviest insert
            // rate in the schema — one row per notification per member — so a
            // page boundary in the middle of a same-second batch has to be
            // stable, and `created_at` is not unique enough to make it so.
            ->latest('id')
            ->paginate($request->integer('per_page', 30));

        return ApiResponse::paginated($notifications, AdminNotificationResource::class, 'Notification log retrieved successfully');
    }
}
