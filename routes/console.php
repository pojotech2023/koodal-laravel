<?php

use App\Jobs\ExpireSubscriptions;
use App\Jobs\PruneStaleRecords;
use App\Jobs\RecomputeMatchScores;
use App\Jobs\SendExpiryReminders;
use App\Jobs\SendNotificationDigest;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Schedule
|--------------------------------------------------------------------------
| Run on exactly ONE host. Two schedulers means every member gets two expiry
| notifications and the digest goes out twice — withoutOverlapping() protects
| against a slow run colliding with the next tick, not against a second host.
*/

// Hourly rather than daily: a membership that expired at 09:00 should not keep
// working until midnight.
Schedule::job(new ExpireSubscriptions)->hourly()->withoutOverlapping();

Schedule::job(new SendExpiryReminders)->dailyAt('10:00');

// Off-peak. Scoring the full active user base is the heaviest job we run.
Schedule::job(new RecomputeMatchScores)->dailyAt('02:30')->withoutOverlapping(120);

// Batches profile_viewed and shortlisted into one message — sending those
// individually is the fastest way to get push notifications disabled.
Schedule::job(new SendNotificationDigest)->dailyAt(config('matrimony.notifications.digest_hour').':00');

Schedule::job(new PruneStaleRecords)->weeklyOn(1, '03:00');

// public/sitemap.xml is a file nginx serves without entering PHP, so it goes
// stale on its own — this is what un-stales it. Daily rather than hourly
// because CMS content changes a few times a month; deploy.sh runs it too, so a
// release that adds a page does not wait until the next morning.
Schedule::command('matrimony:sitemap')->dailyAt('04:00');

Schedule::command('queue:prune-failed --hours=336')->weekly();
Schedule::command('sanctum:prune-expired --hours=24')->daily();

// The queue worker. This host has no supervisor, so the scheduler (cPanel cron:
// `* * * * * cd /home/koodalmatrimony/public_html && php artisan schedule:run`)
// starts a short-lived worker every minute. Without it nothing on the queue ever
// runs — push notifications, photo processing, chat threads for new matches.
// Listed last so it never delays the jobs above; withoutOverlapping keeps it to
// one worker at a time.
Schedule::command('queue:work --queue=high,default,media,matching,reports,notifications --stop-when-empty --max-time=50 --tries=3 --sleep=1')
    ->everyMinute()
    ->withoutOverlapping(5);
