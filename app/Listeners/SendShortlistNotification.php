<?php

namespace App\Listeners;

use App\Events\ProfileShortlisted;
use App\Services\Notification\NotificationDispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Goes to every member, not only those who can open GET /shortlists/me: the
 * tap opens the profile of the person who shortlisted them, which needs no
 * paid feature.
 */
class SendShortlistNotification
{
    use InteractsWithQueue;

    public int $tries = 3;

    public array $backoff = [10, 60, 300];

    public function __construct(protected NotificationDispatcher $notifications) {}

    public function handle(ProfileShortlisted $event): void
    {
        $shortlist = $event->shortlist->loadMissing('user', 'shortlistedUser');

        $actor = $shortlist->user;
        $target = $shortlist->shortlistedUser;

        if (! $actor || ! $target) {
            return;
        }

        $this->notifications->send(
            user: $target,
            type: 'shortlisted',
            title: 'You were shortlisted',
            body: "{$actor->name} added you to their shortlist.",
            data: [
                'user_id' => $actor->id,
                'profile_code' => $actor->profile_code,
            ],
        );

        try {
            app(\App\Services\FirebaseNotificationService::class)->sendToUser(
                $target->id,
                'Profile Shortlisted',
                "{$actor->name} added you to their shortlist.",
                [
                    'type' => 'profile_shortlisted',
                    'reference_id' => (string) $actor->id,
                    'profile_id' => (string) $actor->id,
                    'profile_code' => (string) $actor->profile_code,
                ],
                'profile_shortlisted',
                true,
                // The dispatcher already wrote this one's in-app row (it is a
                // digest type, so it sends no push of its own).
                false
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Shortlist push notification failed: '.$e->getMessage());
        }
    }
}
