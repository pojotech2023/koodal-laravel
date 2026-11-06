<?php

namespace App\Actions\Moderation;

use App\Models\User;
use App\Models\VerificationDocument;
use App\Services\AuditLogger;
use App\Services\Notification\NotificationDispatcher;
use Illuminate\Support\Facades\DB;

/**
 * The only place the verified badge is granted.
 *
 * `SubmitVerification` deliberately does not touch `is_verified` — otherwise
 * the badge would mean "uploaded a file". It means "a human looked at a
 * government ID and it matched", and this is that human's write.
 *
 * Three outcomes, and the third is the one worth having:
 *
 *   approve  — badge granted, `verified_at` stamped.
 *   reject   — the document is not acceptable and re-uploading the same thing
 *              will not help. The member's *next* submission starts fresh.
 *   resubmit — the document is fine in kind but unusable in practice: blurred,
 *              cropped, glare across the number. Without this outcome a
 *              reviewer has to reject, which reads to the member as "your ID is
 *              not valid" and produces a support ticket instead of a better
 *              photograph.
 *
 * Rejecting never revokes an existing badge. A member can be verified from an
 * earlier document, and a later bad upload is not evidence against the first
 * one — revoking there would punish someone for trying to add a second ID.
 */
class ReviewVerification
{
    /** @var array<int, string> */
    public const DECISIONS = ['approve', 'reject', 'resubmit'];

    public function __construct(
        protected AuditLogger $audit,
        protected NotificationDispatcher $notifications,
    ) {}

    public function __invoke(VerificationDocument $document, string $decision, User $reviewer, ?string $remarks = null): VerificationDocument
    {
        abort_unless(in_array($decision, static::DECISIONS, true), 422, 'Unknown review decision.');

        // A member cannot act on "we could not read it" without being told
        // what was wrong with it, and "not accepted" with no reason is the
        // single most common cause of a repeat upload of the same file.
        abort_if($decision !== 'approve' && blank($remarks), 422, 'Remarks are required when a document is not approved.');

        $document = DB::transaction(function () use ($document, $decision, $reviewer, $remarks) {
            $document->update([
                'status' => match ($decision) {
                    'approve' => 'approved',
                    'reject' => 'rejected',
                    'resubmit' => 'resubmit',
                },
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'remarks' => $remarks,
            ]);

            if ($decision === 'approve') {
                $document->user?->profile?->update([
                    'is_verified' => true,
                    'verified_at' => now(),
                ]);
            }

            // No old/new values: every column on this row is either the
            // decision itself or points at a government ID, and AuditLogger
            // redacts `document_number` by name but not `path`. The audit
            // question here is who decided what, and that is in the action and
            // the description.
            $this->audit->log(
                "verification.{$decision}d",
                $document,
                newValues: ['status' => $document->status],
                description: $remarks,
                actor: $reviewer,
            );

            return $document->fresh();
        });

        $this->notify($document, $decision, $remarks);

        return $document;
    }

    protected function notify(VerificationDocument $document, string $decision, ?string $remarks): void
    {
        if (! $user = $document->user) {
            return;
        }

        if ($decision === 'approve') {
            $this->notifications->send(
                user: $user,
                type: 'verification_approved',
                title: 'You are verified',
                body: 'Your ID has been verified. The verified badge now appears on your profile.',
            );

            return;
        }

        // `resubmit` and `reject` share a notification type on purpose: the
        // route and the channel are identical, so a second config entry would
        // buy a longer config file and nothing else. The copy is what carries
        // the difference, because the copy is what the member acts on.
        [$title, $body] = $decision === 'resubmit'
            ? ['We need a clearer copy of your ID', $remarks.' Please upload the document again.']
            : ['Your ID could not be verified', $remarks];

        $this->notifications->send(
            user: $user,
            type: 'verification_rejected',
            title: $title,
            body: $body,
            force: true,
        );
    }
}
