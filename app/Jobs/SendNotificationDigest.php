<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\Notification\PushSender;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * The evening summary for the notification types that would otherwise buzz all
 * day: "12 people viewed your profile and 3 shortlisted you."
 *
 * Which types those are is read from config — a type carrying `digest => true`
 * is written to the in-app list immediately and never pushed individually by
 * NotificationDispatcher. Sending "someone viewed your profile" eleven times in
 * an evening is the fastest way to get push permission revoked, and revoked
 * permission does not come back.
 *
 * Fan-out through the same class rather than a second one: dispatched with no
 * argument it enumerates who has a day's worth of digest rows and re-dispatches
 * itself per user, exactly as RecomputeMatchScores does. One user's dead FCM
 * token then fails that user's job instead of ending the run for everyone after
 * them, and the sends spread across every worker rather than pinning one for an
 * hour.
 *
 * No new Notification row is written. The individual records already exist and
 * are already in the user's list — the digest is a delivery strategy, not a
 * notification in its own right.
 */
class SendNotificationDigest implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected const CHUNK = 500;

    public int $tries = 2;

    /** Long enough to cover a slow run, short enough to free before tomorrow. */
    public int $uniqueFor = 3600;

    public function __construct(public ?int $userId = null)
    {
        $this->onQueue(config('queue.queues.default'));
    }

    public function uniqueId(): string
    {
        return 'digest:'.($this->userId ?? 'fan-out');
    }

    public function handle(PushSender $push): void
    {
        $this->userId === null ? $this->fanOut() : $this->deliver($push);
    }

    // --- Fan-out -----------------------------------------------------------

    protected function fanOut(): void
    {
        $queued = 0;

        $this->recipients()->chunkById(self::CHUNK, function ($users) use (&$queued) {
            foreach ($users as $user) {
                static::dispatch($user->id);
                $queued++;
            }
        });

        Log::info('Notification digest queued', ['users' => $queued]);
    }

    protected function recipients(): Builder
    {
        return User::query()
            ->select('users.id')
            // A suspended account is not owed a summary of who looked at it.
            ->where('users.status', 'active')
            ->whereHas('notifications', fn (Builder $q) => $this->pending($q));
    }

    // --- Per-user delivery -------------------------------------------------

    protected function deliver(PushSender $push): void
    {
        $user = User::with('notificationSettings')->find($this->userId);

        if (! $user) {
            return;
        }

        // Grouped in SQL. The digest needs counts, and pulling a day of rows
        // into PHP to count them there would be the one query in this job that
        // does not scale with the platform.
        $counts = $this->pending($user->notifications())
            ->selectRaw('type, COUNT(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        if ($counts->isEmpty()) {
            return;
        }

        $settings = $user->notificationSettings;

        // Quiet hours at the digest hour is unusual but legal — someone on a
        // night shift. The rows are still marked sent: holding them over means
        // tomorrow's digest claims today's viewers as new.
        if (($settings?->push_enabled ?? true) && ! ($settings?->inQuietHours() ?? false)) {
            $push->sendToUser(
                $user,
                'Your daily update',
                $this->summary($counts->all()),
                ['type' => 'digest', 'route' => '/notifications'],
            );
        }

        $this->pending($user->notifications())->update(['sent_at' => now()]);
    }

    /**
     * @param  array<string, int>  $counts
     */
    protected function summary(array $counts): string
    {
        $phrases = [];

        foreach ($counts as $type => $total) {
            $phrases[] = match ($type) {
                'profile_viewed' => $total.' '.($total === 1 ? 'person' : 'people').' viewed your profile',
                'shortlisted' => $total.' '.($total === 1 ? 'person' : 'people').' shortlisted you',
                default => $total.' new '.str_replace('_', ' ', $type),
            };
        }

        // "A and B", or "A, B and C" — read aloud by a screen reader as often
        // as it is read on a lock screen.
        $last = array_pop($phrases);

        return ($phrases === [] ? $last : implode(', ', $phrases).' and '.$last).' today.';
    }

    // --- Shared query ------------------------------------------------------

    /**
     * The window is the last 24 hours rather than "since midnight": the job is
     * scheduled once a day, and a calendar-day filter silently drops everything
     * that arrived between the previous run and midnight.
     */
    protected function pending(Builder|HasMany $query): Builder|HasMany
    {
        return $query
            ->whereIn('type', static::digestTypes())
            ->whereNull('sent_at')
            ->whereNull('failed_at')
            ->where('created_at', '>=', now()->subDay());
    }

    /**
     * Derived from config, never listed here — a type gains or loses digest
     * behaviour by one flag, and this job follows without being edited.
     *
     * @return array<int, string>
     */
    public static function digestTypes(): array
    {
        return array_keys(array_filter(
            config('matrimony.notifications.types'),
            fn (array $definition) => $definition['digest'] ?? false,
        ));
    }
}
