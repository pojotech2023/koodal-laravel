<?php

namespace App\Events;

use App\Models\Interest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Raised after the interest row exists and the quota has been consumed.
 *
 * Nothing here is broadcast. The recipient learns about an interest through a
 * notification, not a WebSocket frame — they are almost never holding the
 * profile screen open when it arrives, and a realtime channel nobody is
 * listening on is complexity with no delivery.
 */
class InterestSent
{
    use Dispatchable, SerializesModels;

    public function __construct(public Interest $interest) {}
}
