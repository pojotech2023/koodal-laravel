<?php

namespace App\Listeners;

use App\Events\ProfileViewed;
use App\Services\Membership\FeatureGate;
use App\Services\Notification\NotificationDispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Two gates before a view is announced, and both of them are about not lying
 * to the user.
 *
 * A viewer who turned off `show_profile_views` browses anonymously — they are
 * already filtered out of GET /profile-views/received, so naming them in a
 * notification would be the leak that endpoint carefully avoids.
 *
 * An owner without `can_see_profile_viewers` gets nothing either. The deep
 * link goes to a list that answers them with a paywall, and a notification
 * whose only destination is an upgrade sheet trains people to ignore the tray.
 * They still see the total on their own profile, which is where the upgrade
 * prompt belongs.
 *
 * `profile_viewed` is a digest type, so the dispatcher writes the record and
 * stops. SendNotificationDigest turns a day of these into one push.
 */
class SendViewNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    public array $backoff = [10, 60, 300];

    public function __construct(
        protected NotificationDispatcher $notifications,
        protected FeatureGate $features,
    ) {}

    public function handle(ProfileViewed $event): void
    {
        $view = $event->view->loadMissing('viewer.privacySettings', 'viewed');

        if (! $view->viewer || ! $view->viewed) {
            return;
        }

        if (! ($view->viewer->privacySettings?->show_profile_views ?? true)) {
            return;
        }

        if (! $this->features->allows($view->viewed, 'can_see_profile_viewers')) {
            return;
        }

        $this->notifications->send(
            user: $view->viewed,
            type: 'profile_viewed',
            title: 'Someone viewed your profile',
            body: "{$view->viewer->name} viewed your profile.",
            data: [
                'viewer_id' => $view->viewer->id,
                'profile_code' => $view->viewer->profile_code,
            ],
        );
    }
}
