<?php

namespace App\Actions\Enquiry;

use App\Models\ContactEnquiry;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

/**
 * The three things support does to a contact enquiry.
 *
 *   claim    — take ownership. Two people answering the same enquiry is the
 *              normal failure of a shared inbox, and the whole reason the
 *              `handled_by` column exists.
 *   respond  — record the answer given. Closes the enquiry in the same write:
 *              an answered enquiry that stays open is an enquiry someone
 *              answers twice.
 *   close    — no answer needed. Spam, a duplicate, or someone who rang
 *              instead. Distinct from `responded` because "we replied" and "we
 *              did not have to" are different numbers when judging whether the
 *              support queue is keeping up.
 *
 * **The response is recorded, not delivered.** There is no mail layer in this
 * application — nothing anywhere sends an email; notifications are push and
 * in-app, OTPs are SMS — and an enquirer is usually not a member, so there is
 * no `users` row and no device token to notify. Support replies from their own
 * mail client and pastes what they said here, which makes this column the
 * record of the answer rather than the delivery mechanism for it. Wiring a
 * Mailable would be the first email in the codebase and is a decision for
 * whoever specifies it, not a side effect of this Action. See PROGRESS.md §6.
 */
class HandleEnquiry
{
    /** @var array<int, string> */
    public const ACTIONS = ['claim', 'respond', 'close'];

    public function __construct(protected AuditLogger $audit) {}

    public function __invoke(ContactEnquiry $enquiry, string $action, User $staff, ?string $response = null): ContactEnquiry
    {
        abort_unless(in_array($action, static::ACTIONS, true), 422, 'Unknown enquiry action.');

        // Guards first, and both of these are business state rather than
        // permission — which is why they are here and not in the Policy. A
        // Policy answers "may you", and the answer to both of these is "yes,
        // but there is nothing left to do", which is a 409.
        if ($action === 'claim') {
            abort_if(
                $enquiry->handled_by !== null && $enquiry->handled_by !== $staff->id,
                409,
                'Another staff member is already handling this enquiry.',
            );
        }

        abort_if(
            $action === 'respond' && $enquiry->status === 'responded',
            409,
            'This enquiry has already been answered.',
        );

        return DB::transaction(function () use ($enquiry, $action, $staff, $response) {
            $enquiry->update(match ($action) {
                // Claiming does not overwrite an existing owner — the guard
                // above means it is either unowned or already this person's.
                'claim' => ['status' => 'in_progress', 'handled_by' => $staff->id],

                // `handled_by` is set here too, not only on claim. Answering
                // without claiming first is the common path on a quiet queue,
                // and an answered enquiry with no name against it defeats the
                // column.
                'respond' => ['status' => 'responded', 'handled_by' => $staff->id, 'response' => $response],

                'close' => ['status' => 'closed', 'handled_by' => $staff->id],
            });

            $this->audit->log(
                // Spelled out rather than "enquiry.{$action}ed" — the audit
                // action name is a string operators filter on, and one of the
                // three would come out "closeed".
                match ($action) {
                    'claim' => 'enquiry.claimed',
                    'respond' => 'enquiry.responded',
                    'close' => 'enquiry.closed',
                },
                $enquiry,
                newValues: ['status' => $enquiry->status],

                // The response text itself is deliberately not copied into the
                // audit row. It is already on the enquiry, and duplicating a
                // free-text reply into a table read by more people than any
                // other is how personal detail spreads.
                description: match ($action) {
                    'claim' => 'Claimed enquiry from '.$enquiry->email,
                    'respond' => 'Answered enquiry from '.$enquiry->email,
                    'close' => 'Closed enquiry from '.$enquiry->email.' without a reply',
                },
                actor: $staff,
            );

            return $enquiry->fresh('handler');
        });
    }
}
