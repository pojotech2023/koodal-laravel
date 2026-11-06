<?php

namespace App\Events;

use App\Models\Conversation;
use App\Models\Interest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A connection was made.
 *
 * The conversation travels with the event rather than being looked up by the
 * listeners. Two of them need it, and re-querying by the canonical pair in
 * each one would be the same read twice for a row the producer already had in
 * hand.
 */
class InterestAccepted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Interest $interest,
        public Conversation $conversation,
    ) {}
}
