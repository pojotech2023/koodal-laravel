<?php

namespace App\Policies;

use App\Models\Report;
use App\Models\User;
use App\Policies\Concerns\ActsAsStaff;
use Illuminate\Auth\Access\Response;

class ReportPolicy
{
    use ActsAsStaff;

    public function viewAny(User $user): bool
    {
        return $this->staffCan($user, 'reports.view');
    }

    /**
     * The reporter sees their own report — the status is the only feedback they
     * get that filing it did something. The *reported* user never sees it: a
     * report they can read is a report they can retaliate for, which is how a
     * safety feature stops being used.
     */
    public function view(User $user, Report $report): Response
    {
        return $report->reporter_id === $user->id || $this->staffCan($user, 'reports.view')
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function assign(User $user, Report $report): Response
    {
        return $this->staffCan($user, 'reports.resolve')
            ? Response::allow()
            : Response::deny();
    }

    /**
     * Business rule first, so a super admin is bound by it: a report is
     * resolved once. Re-resolving overwrites who decided what and when, which
     * is the entire evidentiary value of the row — and the moderator who
     * suspended someone yesterday is exactly who a complaint next month is
     * about.
     */
    public function resolve(User $user, Report $report): Response
    {
        if (! $report->isOpen()) {
            return Response::deny('This report has already been resolved.');
        }

        return $this->staffCan($user, 'reports.resolve')
            ? Response::allow()
            : Response::deny();
    }
}
