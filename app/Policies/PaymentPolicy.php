<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;
use App\Policies\Concerns\ActsAsStaff;
use Illuminate\Auth\Access\Response;

class PaymentPolicy
{
    use ActsAsStaff;

    public function view(User $user, Payment $payment): Response
    {
        return $payment->user_id === $user->id || $this->staffCan($user, 'payments.view')
            ? Response::allow()
            // 404: payment ids are sequential and confirming that payment 4471
            // exists is information a stranger has no business having.
            : Response::denyAsNotFound();
    }

    /** Only the payer downloads an invoice — it carries a name and an amount. */
    public function invoice(User $user, Payment $payment): Response
    {
        return $payment->user_id === $user->id
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /**
     * The two business rules come first, so a super admin is bound by them —
     * `Gate::before` does not bypass a bare ability, and this is exactly the
     * case that rule exists for. Refunding an unpaid order sends money for a
     * payment that never arrived; refunding twice sends it twice, and Razorpay
     * will happily accept the second call.
     */
    public function refund(User $user, Payment $payment): Response
    {
        if ($payment->status !== 'paid') {
            return Response::deny('Only a paid payment can be refunded.');
        }

        // `status` already excludes a fully refunded payment above; this catches
        // the partial refund, where the row is still `paid` and only
        // `refund_id` records that money has gone back once already.
        if ($payment->refund_id !== null) {
            return Response::deny('This payment has already been refunded.');
        }

        return $this->staffCan($user, 'payments.refund')
            ? Response::allow()
            : Response::deny();
    }

    public function export(User $user): bool
    {
        return $this->staffCan($user, 'payments.export');
    }
}
