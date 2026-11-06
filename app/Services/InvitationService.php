<?php

namespace App\Services;

use App\Mail\MatchInvitationMail;
use App\Models\GameMatch;
use App\Models\MatchInvitation;
use App\Models\MatchPlayer;
use App\Models\Puzzle;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class InvitationService
{
    /**
     * Create match and send invitation email.
     */
    public function createMatchAndInvite(User $host, Puzzle $puzzle, string $invitedEmail): GameMatch
    {
        $match = GameMatch::create([
            'puzzle_id' => $puzzle->id,
            'host_id' => $host->id,
            'status' => 'waiting',
        ]);

        // Add Host as player
        MatchPlayer::create([
            'match_id' => $match->id,
            'user_id' => $host->id,
            'invited_email' => $host->email,
            'status' => 'ready', // Host starts ready
        ]);

        $token = Str::random(64);
        $invitation = MatchInvitation::create([
            'match_id' => $match->id,
            'email' => $invitedEmail,
            'token' => $token,
            'expires_at' => now()->addHours(24),
        ]);

        // Build temporary signed URL valid for 24h
        $signedUrl = URL::temporarySignedRoute(
            'spot.invitation.accept',
            now()->addHours(24),
            ['token' => $token]
        );

        try {
            Mail::to($invitedEmail)->send(new MatchInvitationMail($match, $host, $signedUrl));
        } catch (\Throwable $e) {
            // Log mail exception if mail driver fails, continue
        }

        return $match;
    }

    /**
     * Accept invitation.
     */
    public function acceptInvitation(MatchInvitation $invitation, User $user): GameMatch
    {
        $match = $invitation->match;

        if ($user->email !== $invitation->email) {
            abort(403, 'This invitation was issued for a different email address (' . $invitation->email . ').');
        }

        if ($invitation->expires_at->isPast()) {
            abort(410, 'This invitation link has expired.');
        }

        // Add or update invitee in match_players
        MatchPlayer::updateOrCreate(
            [
                'match_id' => $match->id,
                'user_id' => $user->id,
            ],
            [
                'invited_email' => $invitation->email,
                'status' => 'joined',
            ]
        );

        $invitation->update(['accepted_at' => now()]);

        return $match;
    }
}
