<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\Media\ImageProcessor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * The retention sweep, weekly.
 *
 * Data this system holds and no longer needs is not neutral — it is the
 * material of the next breach, and on a matrimonial platform that material is
 * photographs, government IDs and who looked at whom. Every window here comes
 * from `config('matrimony.retention')` rather than a literal, so a legal answer
 * about retention is a config file and not a code review.
 *
 * Deletion order matters and is not alphabetical: the account purge runs last,
 * because it hard-deletes users and every cascade above it would otherwise be
 * doing the same work twice.
 */
class PruneStaleRecords implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Deleted in batches so one statement never locks a large table for minutes. */
    protected const CHUNK = 1000;

    public function __construct()
    {
        $this->onQueue(config('queue.queues.default'));
    }

    public function handle(ImageProcessor $images): void
    {
        $retention = config('matrimony.retention');

        $pruned = [
            'otp_verifications' => $this->pruneByAge('otp_verifications', 'created_at', $retention['otp']),
            'profile_views' => $this->pruneByAge('profile_views', 'viewed_at', $retention['profile_views']),
            'audit_logs' => $this->pruneByAge('audit_logs', 'created_at', $retention['audit_logs']),
            'notifications' => $this->pruneReadNotifications($retention['profile_views']),
            'exports' => $this->pruneExports($retention['exports']),
            'accounts' => $this->purgeDeletedAccounts($images, $retention['soft_deleted_accounts']),
        ];

        Log::info('PruneStaleRecords completed', $pruned);
    }

    // --- Internals ---------------------------------------------------------

    /**
     * Chunked `DELETE … LIMIT`, not one unbounded statement. `profile_views` is
     * the second-largest table in the schema and a single DELETE covering six
     * months of it holds row locks long enough for the read path to notice.
     */
    protected function pruneByAge(string $table, string $column, int $days): int
    {
        $cutoff = now()->subDays($days);
        $deleted = 0;

        do {
            $batch = DB::table($table)->where($column, '<', $cutoff)->limit(static::CHUNK)->delete();
            $deleted += $batch;
        } while ($batch === static::CHUNK);

        return $deleted;
    }

    /**
     * Only *read* notifications age out, and only after the same window as
     * profile views. An unread notification is still the badge on someone's
     * app icon, and deleting it silently decrements a count they never saw.
     */
    protected function pruneReadNotifications(int $days): int
    {
        $cutoff = now()->subDays($days);
        $deleted = 0;

        do {
            $batch = DB::table('notifications')
                ->whereNotNull('read_at')
                ->where('read_at', '<', $cutoff)
                ->limit(static::CHUNK)
                ->delete();

            $deleted += $batch;
        } while ($batch === static::CHUNK);

        return $deleted;
    }

    /**
     * Generated CSVs age out of `private/exports/`.
     *
     * Nothing else deletes them, so before this ran every member export ever
     * generated sat on the disk indefinitely — a growing pile of files, each one
     * the whole member base with mobile numbers, kept for no reason beyond the
     * absence of a sweep. That is precisely the material this class exists to
     * get rid of, and the shortest window in the config is the right one for it:
     * an export is downloaded within minutes of the notification or not at all,
     * and `ExportController` answers 410 with "run it again", which costs one
     * click.
     *
     * The notification rows are deliberately left alone. They are audit-adjacent
     * — the trail of who extracted what — and `ExportController::index` reports
     * them as `available: false` once the file is gone.
     *
     * Listed rather than globbed: `allFiles` on an S3 prefix is one paginated
     * API call and returns the keys directly, which is what `lastModified`
     * needs anyway.
     */
    protected function pruneExports(int $days): int
    {
        $disk = Storage::disk('private');
        $cutoff = now()->subDays($days)->getTimestamp();
        $deleted = 0;

        foreach ($disk->allFiles('exports') as $path) {
            try {
                if ($disk->lastModified($path) < $cutoff) {
                    $disk->delete($path);
                    $deleted++;
                }
            } catch (\Throwable $e) {
                // A file that vanished between the listing and the delete, or an
                // unreadable key. Neither is a reason to abandon the sweep.
                Log::warning('PruneStaleRecords: export prune failed', [
                    'path' => $path,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $deleted;
    }

    /**
     * The purge `RequestAccountDeletion` promises.
     *
     * The soft delete is the grace period — for someone who deleted in anger,
     * for an account deleted by whoever else had the phone, and for support
     * answering a fraud report about a profile that has just vanished. Once it
     * expires the row goes for real, and the foreign keys cascade the rest.
     *
     * Files are removed **before** the row, because the row is the only record
     * of where they are. Deleting it first orphans photographs and government
     * IDs on disk with nothing left pointing at them — the exact opposite of
     * what a deletion request asked for. A failed file delete therefore leaves
     * the account intact for next week's run rather than losing the pointer.
     */
    protected function purgeDeletedAccounts(ImageProcessor $images, int $days): int
    {
        $cutoff = now()->subDays($days);
        $purged = 0;

        User::onlyTrashed()
            ->where('deleted_at', '<', $cutoff)
            ->with(['photos', 'verificationDocuments'])
            ->chunkById(100, function ($users) use ($images, &$purged) {
                foreach ($users as $user) {
                    try {
                        $this->deleteFilesFor($images, $user);
                        $user->forceDelete();
                        $purged++;
                    } catch (\Throwable $e) {
                        // One unreachable file must not abandon the rest of the
                        // sweep — and this account simply waits for next week.
                        Log::error('PruneStaleRecords: account purge failed', [
                            'user_id' => $user->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

        return $purged;
    }

    protected function deleteFilesFor(ImageProcessor $images, User $user): void
    {
        foreach ($user->photos as $photo) {
            // allPaths(), not the three columns by hand — the model already owns
            // the list of derivatives, and a fourth variant added there must not
            // start leaking storage because this job did not hear about it.
            $images->delete($photo->disk, $photo->allPaths());
        }

        foreach ($user->verificationDocuments as $document) {
            $images->delete($document->disk, [$document->path]);
        }

        // Chat attachments and report evidence are keyed by conversation and by
        // reporter, so they are not reachable from the user's own relations.
        // Directories, not files: whatever is under them belongs to this
        // account and nothing else will ever reference it again.
        Storage::disk('private')->deleteDirectory("reports/{$user->id}");
        Storage::disk('private')->deleteDirectory("verification/{$user->id}");
    }
}
