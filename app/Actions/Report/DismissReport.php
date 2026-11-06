<?php

namespace App\Actions\Report;

use App\Models\Report;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Notification\NotificationDispatcher;
use Illuminate\Support\Facades\DB;

/**
 * Closing a report without sanction.
 *
 * Distinct from `ResolveReport` with `action_taken = 'none'`, and the
 * difference is not cosmetic: `none` means investigated and found not to
 * warrant action, `dismissed` means the report itself was not actionable —
 * duplicate, retaliatory, or nothing to go on. The counts are read separately
 * when judging whether a category of report is working, and collapsing them
 * makes both numbers meaningless.
 *
 * The reporter is still told, in the same words as any other outcome. A report
 * that vanishes silently teaches people that reporting does nothing.
 */
class DismissReport
{
    public function __construct(
        protected AuditLogger $audit,
        protected NotificationDispatcher $notifications,
    ) {}

    public function __invoke(Report $report, User $moderator, ?string $note = null): Report
    {
        abort_unless($report->isOpen(), 409, 'This report has already been resolved.');

        $report = DB::transaction(function () use ($report, $moderator, $note) {
            $report->update([
                'status' => 'dismissed',
                'action_taken' => 'none',
                'resolution_note' => $note,
                'resolved_by' => $moderator->id,
                'resolved_at' => now(),
            ]);

            $this->audit->log(
                'report.dismissed',
                $report,
                newValues: ['reported_user_id' => $report->reported_user_id],
                description: $note,
                actor: $moderator,
            );

            return $report->fresh();
        });

        if ($reporter = $report->reporter) {
            $this->notifications->send(
                user: $reporter,
                type: 'report_resolved',
                title: 'Your report has been reviewed',
                // Deliberately identical to the sanctioned wording. A reporter
                // who can tell "dismissed" from "acted on" can tell whether the
                // person they reported was punished, and that is what a
                // retaliatory report is fishing for.
                body: 'Thank you. Our team has reviewed your report and taken the appropriate action.',
                data: ['report_id' => $report->id],
                force: true,
            );
        }

        return $report;
    }
}
