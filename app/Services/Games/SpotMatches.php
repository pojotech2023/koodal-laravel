<?php

namespace App\Services\Games;

use App\Models\GameMatch;
use App\Models\MatchPlayer;
use App\Models\Puzzle;
use App\Models\User;
use App\Services\Notification\NotificationDispatcher;
use Illuminate\Support\Facades\DB;

/**
 * Starting a Spot the Difference match against another *member*.
 *
 * The website's InvitationService invites by email with a signed link and a
 * mail; in the app both players are already signed-in members, so the match is
 * created with both seats filled and the opponent is told in-app + by push.
 * Round mechanics are MatchService's and are used unchanged.
 */
class SpotMatches
{
    public function __construct(
        protected GameAccess $access,
        protected NotificationDispatcher $notifier,
    ) {}

    public function create(User $host, Puzzle $puzzle, User $opponent): GameMatch
    {
        $this->access->assertCanPlay($host, $opponent);

        $match = DB::transaction(function () use ($host, $puzzle, $opponent) {
            $match = GameMatch::create([
                'puzzle_id' => $puzzle->id,
                'host_id' => $host->id,
                'status' => 'waiting',
            ]);

            // The host starts ready, as on the website: one tap from the
            // opponent starts the countdown.
            MatchPlayer::create([
                'match_id' => $match->id,
                'user_id' => $host->id,
                'invited_email' => $host->email,
                'status' => 'ready',
            ]);

            MatchPlayer::create([
                'match_id' => $match->id,
                'user_id' => $opponent->id,
                'invited_email' => $opponent->email,
                'status' => 'invited',
            ]);

            return $match;
        });

        $name = $host->profile?->displayNameFor($opponent) ?? $host->name;
        $this->notifier->send(
            $opponent,
            'game_invite',
            "🕵️ {$name} challenged you to Spot the Difference",
            "Tap to join the match: \"{$puzzle->title}\".",
            [
                'route' => '/games/spot/matches/'.$match->id,
                'match_id' => $match->id,
                'game_type' => 'spot',
                'sender_code' => $host->profile_code,
            ],
        );

        return $match;
    }

    public function rematch(User $me, GameMatch $old): GameMatch
    {
        $opponent = $old->players()->where('user_id', '!=', $me->id)->first()?->user;
        abort_unless($opponent, 409, 'Opponent not available for a rematch.');

        return $this->create($me, $old->puzzle, $opponent);
    }
}
