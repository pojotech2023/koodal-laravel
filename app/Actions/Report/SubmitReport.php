<?php

namespace App\Actions\Report;

use App\Actions\Block\BlockUser;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Files a report against another member.
 *
 * Three decisions worth stating.
 *
 * **Evidence is snapshotted, not referenced.** Message ids are copied onto the
 * report at submission time, because the first thing an abusive user does
 * after being told off is delete the thread. Screenshots go to the `private`
 * disk — never `public` — since a report attachment is by definition the most
 * sensitive image on the platform.
 *
 * **Message ids are verified before they are stored.** Only messages from a
 * conversation containing both of these people survive the filter. Without it,
 * anyone can attach any id in the table and a moderator reads a stranger's
 * chat while investigating.
 *
 * **One open report per pair.** A second report while the first is still being
 * looked at adds nothing to the queue but noise, and a moderator working a
 * backlog of forty duplicates is a moderator not reading the original. Once
 * resolved, the same person can be reported again.
 */
class SubmitReport
{
    public function __construct(protected BlockUser $blockUser) {}

    /**
     * @param  array<int, UploadedFile>  $evidence
     * @param  array<int, int>  $messageIds
     */
    public function __invoke(
        User $reporter,
        User $target,
        string $category,
        ?string $description = null,
        array $evidence = [],
        array $messageIds = [],
        bool $block = false,
    ): Report {
        if ($reporter->is($target)) {
            throw new \InvalidArgumentException('You cannot report your own profile.');
        }

        abort_if($this->hasOpenReport($reporter, $target), 409,
            'You have already reported this profile. Our team is reviewing it.');

        // Uploaded outside the transaction: a disk write cannot be rolled back,
        // and holding a transaction open across an S3 round trip locks rows for
        // as long as the network feels like taking.
        $paths = $this->storeEvidence($reporter, $evidence);

        $report = DB::transaction(fn () => Report::create([
            'reporter_id' => $reporter->id,
            'reported_user_id' => $target->id,
            'category' => $category,
            'description' => $description,
            'evidence_paths' => $paths ?: null,
            'evidence_message_ids' => $this->verifiedMessageIds($reporter, $target, $messageIds) ?: null,
            'status' => 'open',
        ]));

        // After the report is safely written. Blocking is the user's immediate
        // relief; the report is the part that must not be lost if it fails.
        if ($block) {
            ($this->blockUser)($reporter, $target, 'Reported: '.$category);
        }

        return $report;
    }

    protected function hasOpenReport(User $reporter, User $target): bool
    {
        return Report::query()
            ->where('reporter_id', $reporter->id)
            ->where('reported_user_id', $target->id)
            ->open()
            ->exists();
    }

    /**
     * @param  array<int, UploadedFile>  $evidence
     * @return array<int, string>
     */
    protected function storeEvidence(User $reporter, array $evidence): array
    {
        return array_values(array_filter(array_map(
            fn (UploadedFile $file) => $file->store("reports/{$reporter->id}", 'private') ?: null,
            $evidence,
        )));
    }

    /**
     * @param  array<int, int>  $messageIds
     * @return array<int, int>
     */
    protected function verifiedMessageIds(User $reporter, User $target, array $messageIds): array
    {
        if ($messageIds === []) {
            return [];
        }

        [$one, $two] = [min($reporter->id, $target->id), max($reporter->id, $target->id)];

        $conversationId = Conversation::where('user_one_id', $one)
            ->where('user_two_id', $two)
            ->value('id');

        if (! $conversationId) {
            return [];
        }

        return Message::whereIn('id', $messageIds)
            ->where('conversation_id', $conversationId)
            // Soft-deleted messages are exactly the ones worth keeping: a
            // message deleted after the report was started is the evidence.
            ->withTrashed()
            ->pluck('id')
            ->all();
    }
}
