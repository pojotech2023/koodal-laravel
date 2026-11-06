<?php

namespace App\Actions\Report;

use App\Actions\Concerns\SuspendsAccounts;
use App\Models\Report;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Notification\NotificationDispatcher;
use Illuminate\Support\Facades\DB;

/**
 * The end of a report, and the only place a moderation sanction is applied.
 *
 * Four outcomes, from the schema's `action_taken` enum:
 *
 *   warned    — a notice on the record. The next report against the same
 *               account is read in the light of it.
 *   suspended — the account stops working now: status flips, every API token
 *               is revoked, and they are told why.
 *   blocked   — the same mechanics, without an end date. There is no separate
 *               `banned` status in the schema, and inventing one in code that
 *               the login path does not check would be a ban that does not ban.
 *   none      — investigated, no sanction, but a real record that it was.
 *
 * Both parties are notified, and both notifications are forced past the
 * member's channel preferences. A sanction someone muted is a sanction they can
 * truthfully say they never received, and a reporter who hears nothing back
 * concludes reporting does not work and stops doing it — which is how a safety
 * feature dies.
 */
class ResolveReport
{
    use SuspendsAccounts;

    /** @var array<int, string> */
    public const ACTIONS = ['warned', 'suspended', 'blocked', 'none'];

    public function __construct(
        protected AuditLogger $audit,
        protected NotificationDispatcher $notifications,
    ) {}

    public function __invoke(Report $report, User $moderator, string $actionTaken, ?string $note = null): Report
    {
        abort_unless($report->isOpen(), 409, 'This report has already been resolved.');

        abort_unless(in_array($actionTaken, static::ACTIONS, true), 422, 'Unknown moderation action.');

        // A sanction with no written reason is one nobody can defend on appeal
        // three months later, when the moderator who applied it has left.
        abort_if(
            $actionTaken !== 'none' && blank($note),
            422,
            'A resolution note is required when action is taken.',
        );

        $reported = $report->reportedUser;

        $report = DB::transaction(function () use ($report, $reported, $moderator, $actionTaken, $note) {
            $report->update([
                'status' => 'action_taken',
                'action_taken' => $actionTaken,
                'resolution_note' => $note,
                'resolved_by' => $moderator->id,
                'resolved_at' => now(),
            ]);

            if (in_array($actionTaken, ['suspended', 'blocked'], true) && $reported) {
                $this->applySuspension($reported, $note);
            }

            $this->audit->log(
                'report.resolved',
                $report,
                newValues: ['action_taken' => $actionTaken, 'reported_user_id' => $report->reported_user_id],
                description: $note,
                actor: $moderator,
            );

            return $report->fresh();
        });

        // After commit. A push that fails must not roll back a suspension —
        // the sanction is the part that has to survive.
        $this->notify($report, $reported, $actionTaken);

        return $report;
    }

    // --- Internals ---------------------------------------------------------

    protected function notify(Report $report, ?User $reported, string $actionTaken): void
    {
        if ($reported && $actionTaken !== 'none') {
            [$type, $title, $body] = match ($actionTaken) {
                'warned' => [
                    'account_warning',
                    'A warning about your account',
                    'Your recent activity was reported and reviewed. Please read our community guidelines — further reports may lead to suspension.',
                ],
                default => [
                    'account_suspended',
                    'Your account has been suspended',
                    'Your account has been suspended following a review. Contact support if you believe this is a mistake.',
                ],
            };

            $this->notifications->send(
                user: $reported,
                type: $type,
                title: $title,
                body: $body,
                force: true,
            );
        }

        // The reporter is never told what was done, only that it was reviewed.
        // Naming the outcome tells them whether the person they reported was
        // sanctioned, which is exactly the information a retaliatory report is
        // fishing for — and it identifies the reporter to anyone who compares
        // notes.
        if ($reporter = $report->reporter) {
            $this->notifications->send(
                user: $reporter,
                type: 'report_resolved',
                title: 'Your report has been reviewed',
                body: 'Thank you. Our team has reviewed your report and taken the appropriate action.',
                data: ['report_id' => $report->id],
                force: true,
            );
        }
    }
}
