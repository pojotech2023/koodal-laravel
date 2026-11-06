<?php

namespace App\Actions\Admin;

use App\Actions\Concerns\SuspendsAccounts;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Notification\NotificationDispatcher;
use Illuminate\Support\Facades\DB;

/**
 * The five direct member actions from the Users screen.
 *
 * `block` and `suspend` do the same thing to the account, and that is
 * deliberate rather than lazy. The schema has no `banned` status, and adding
 * one in code that the login path does not check produces a ban that does not
 * ban. What differs is the audit action name, the copy the member receives,
 * and — the reason the distinction is worth keeping at all — that a moderator
 * reading the trail can tell "temporarily off while we look into this" from
 * "this account is not coming back". `activate` and `unblock` are the same
 * lift, for the same reason.
 *
 * `verify` is the odd one out: it grants the badge on the *profile* and does
 * not touch the account at all. It is here because the Users screen is where a
 * support agent is standing when they establish, out of band, that a member is
 * who they say they are.
 */
class UpdateUserStatus
{
    use SuspendsAccounts;

    /** @var array<int, string> */
    public const ACTIONS = ['suspend', 'activate', 'block', 'unblock', 'verify'];

    public function __construct(
        protected AuditLogger $audit,
        protected NotificationDispatcher $notifications,
    ) {}

    public function __invoke(User $target, string $action, User $actor, ?string $reason = null): User
    {
        abort_unless(in_array($action, static::ACTIONS, true), 422, 'Unknown account action.');

        // A sanction with no written reason is one nobody can defend on appeal
        // three months later, when the moderator who applied it has left. Same
        // rule as ResolveReport, for the same reason.
        abort_if(
            in_array($action, ['suspend', 'block'], true) && blank($reason),
            422,
            'A reason is required when suspending or blocking an account.',
        );

        $target = DB::transaction(function () use ($target, $action, $actor, $reason) {
            match ($action) {
                'suspend', 'block' => $this->applySuspension($target, $reason),
                'activate', 'unblock' => $this->liftSuspension($target),
                'verify' => $this->verify($target),
            };

            $this->audit->log(
                "user.{$action}",
                $target,
                newValues: ['status' => $target->status],
                description: $reason,
                actor: $actor,
            );

            return $target->fresh();
        });

        // After commit. A push that fails must not roll back a suspension —
        // the sanction is the part that has to survive.
        $this->notify($target, $action, $reason);

        return $target;
    }

    // --- Internals ---------------------------------------------------------

    /**
     * The badge, and the timestamp behind it. Nothing here reads
     * `verification_documents`: this is the manual path, taken when a support
     * agent has confirmed identity by some other means, and recording it as
     * though a document had been reviewed would put a lie in the audit trail.
     */
    protected function verify(User $target): void
    {
        abort_unless($target->profile, 422, 'This member has no profile to verify.');

        $target->profile->update([
            'is_verified' => true,
            'verified_at' => now(),
        ]);
    }

    protected function notify(User $target, string $action, ?string $reason): void
    {
        [$type, $title, $body] = match ($action) {
            'suspend' => [
                'account_suspended',
                'Your account has been suspended',
                'Your account has been suspended following a review. Contact support if you believe this is a mistake.',
            ],
            'block' => [
                'account_suspended',
                'Your account has been closed',
                'Your account has been closed following a review of your activity. Contact support if you believe this is a mistake.',
            ],
            'activate', 'unblock' => [
                'account_reinstated',
                'Your account is active again',
                'Your account has been reviewed and reinstated. Welcome back.',
            ],
            // Verification is announced by the badge itself the next time they
            // open the app; a push saying "you are now verified" for something
            // they did not just do reads as a phishing message.
            default => [null, null, null],
        };

        if ($type === null) {
            return;
        }

        // `force`: a member cannot mute the notice that their account was
        // suspended, and a reinstatement they never see is a member lost for
        // no reason.
        $this->notifications->send(
            user: $target,
            type: $type,
            title: $title,
            body: $body,
            force: true,
        );
    }
}
