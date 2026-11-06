<?php

namespace App\Events;

use App\Models\Interest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A decline is told to the sender, because the alternative is worse.
 *
 * Silence reads as "still pending" and produces a second interest, a support
 * ticket, or a message asking why there was no reply. The notifications config
 * carries an `interest_declined` type precisely so this closes cleanly; the
 * resend cooldown on Interest::canResend() is what stops the answer being used
 * as a prompt to try again immediately.
 */
class InterestDeclined
{
    use Dispatchable, SerializesModels;

    public function __construct(public Interest $interest) {}
}
