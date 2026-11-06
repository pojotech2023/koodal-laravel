<?php

namespace App\Actions\Report;

use App\Models\Report;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

/**
 * Claiming a report for investigation.
 *
 * Assignment moves the row to `investigating`, which is the only signal the
 * queue has that someone is already on it — two moderators independently
 * suspending the same account produce two audit trails and one very confused
 * appeal.
 */
class AssignReport
{
    public function __construct(protected AuditLogger $audit) {}

    public function __invoke(Report $report, User $assignee, User $actor): Report
    {
        abort_unless($report->isOpen(), 409, 'This report has already been resolved.');

        // Assigning to someone who cannot act on it parks the report where
        // nobody is looking. Better to refuse than to lose it in a queue.
        abort_unless(
            $assignee->isSuperAdmin() || $assignee->hasPermission('reports.resolve'),
            422,
            'That staff member cannot resolve reports.',
        );

        return DB::transaction(function () use ($report, $assignee, $actor) {
            $report->update([
                'assigned_to' => $assignee->id,
                'status' => 'investigating',
            ]);

            $this->audit->logChanges(
                'report.assigned',
                $report,
                "Report #{$report->id} assigned to {$assignee->name}",
                $actor,
            );

            return $report->fresh();
        });
    }
}
