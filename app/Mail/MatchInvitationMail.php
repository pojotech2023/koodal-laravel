<?php

namespace App\Mail;

use App\Models\GameMatch;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MatchInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public GameMatch $match,
        public User $host,
        public string $signedUrl
    ) {}

    public function build()
    {
        return $this->subject("{$this->host->name} invited you to Spot the Difference 1v1!")
                    ->markdown('emails.match_invitation');
    }
}
