<?php

namespace App\Services\Games\Rooms;

use App\Models\FlipGame;
use App\Models\User;
use App\Services\FlipGameService;
use App\Services\Games\GameAccess;
use App\Services\Games\GameAssets;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Flip the Tiles over the API.
 *
 * The rules (partner-chemistry scoring, one-time preview, mismatch window) all
 * live in FlipGameService and are called unchanged, so a mobile player and a
 * web player share a board. What this adds is identity: the service keys a
 * player by token, and the website's own convention for a signed-in member is
 * `user_{id}` — so the authenticated user *is* the token, and the client never
 * supplies one.
 */
class FlipRoom implements RoomDriver
{
    use BuildsEnvelope;

    public function __construct(
        protected FlipGameService $flip,
        protected GameAccess $access,
    ) {}

    public function type(): string
    {
        return 'flip';
    }

    public static function token(User $user): string
    {
        return 'user_'.$user->id;
    }

    public function find(string $code): Model
    {
        return FlipGame::where('code', strtoupper($code))->firstOrFail();
    }

    public function code(Model $room): string
    {
        return $room->code;
    }

    public function findActiveBetween(User $a, User $b): ?Model
    {
        return FlipGame::query()
            ->whereIn('status', ['waiting', 'playing'])
            ->where(fn ($q) => $q
                ->where(fn ($x) => $x->where('user_one_id', $a->id)->where('user_two_id', $b->id))
                ->orWhere(fn ($x) => $x->where('user_one_id', $b->id)->where('user_two_id', $a->id)))
            ->latest('id')
            ->first();
    }

    public function create(User $host, User $guest): Model
    {
        return $this->flip->createGame($host->name, self::token($host), $host->id, $guest->id);
    }

    /** A game anyone holding the code may join — the WhatsApp share-link case. */
    public function createOpen(User $host): FlipGame
    {
        return $this->flip->createGame($host->name, self::token($host), $host->id, null);
    }

    protected function slot(FlipGame $game, User $viewer): int
    {
        return $game->playerNumber(self::token($viewer))
            ?? abort(403, 'You are not a player in this game.');
    }

    public function partnerOf(Model $room, User $viewer): ?User
    {
        $slot = $this->slot($room, $viewer);
        $id = $slot === 1 ? $room->user_two_id : $room->user_one_id;

        if (! $id) {
            $token = $slot === 1 ? $room->p2_token : $room->p1_token;
            $id = $token && str_starts_with($token, 'user_') ? (int) substr($token, 5) : null;
        }

        return $id ? User::with('profile.primaryPhoto')->find($id) : null;
    }

    public function join(Model $room, User $viewer): Model
    {
        /** @var FlipGame $room */
        if ($room->playerNumber(self::token($viewer)) !== null) {
            return $room;
        }

        abort_if(in_array($room->status, ['finished', 'abandoned'], true), 409, 'This game has already ended.');

        // The slot is open only to the member this game was created for, or to
        // anyone with the code when it was created as an open link. The website
        // lets any visitor take it; that is how a stranger ends up in a
        // couple's game.
        $invited = $room->user_two_id === null || $room->user_two_id === $viewer->id;
        abort_unless($invited && $room->p2_token === null, 403, 'This game is not open to you.');

        $host = User::find($room->user_one_id);
        if ($host) {
            $this->access->assertCanPlay($viewer, $host);
        }

        return $this->flip->join($room, $viewer->name, self::token($viewer), $viewer->id);
    }

    public function ready(Model $room, User $viewer): Model
    {
        return $this->flip->toggleReady($room, self::token($viewer));
    }

    public function leave(Model $room, User $viewer): Model
    {
        return DB::transaction(function () use ($room, $viewer) {
            $locked = FlipGame::whereKey($room->id)->lockForUpdate()->firstOrFail();
            $this->slot($locked, $viewer);

            if (! in_array($locked->status, ['finished', 'abandoned'], true)) {
                $locked->status = 'abandoned';
                $locked->save();
            }

            return $locked;
        });
    }

    /** Rematch: a finished game cannot be re-dealt, so deal a new one for the pair. */
    public function reset(Model $room, User $viewer): Model
    {
        /** @var FlipGame $room */
        $this->slot($room, $viewer);
        abort_unless(in_array($room->status, ['finished', 'abandoned'], true), 409, 'The current game is still in progress.');

        $partner = $this->partnerOf($room, $viewer);

        // The partner may have asked first and dealt the new board with this
        // viewer in seat 2 — take that seat rather than deal a third board.
        $existing = $partner ? $this->findActiveBetween($viewer, $partner) : null;
        if ($existing) {
            return $this->join($existing, $viewer);
        }

        return $partner ? $this->create($viewer, $partner) : $this->createOpen($viewer);
    }

    public function sendMessage(Model $room, User $viewer, string $text): void
    {
        $this->flip->sendMessage($room, self::token($viewer), $text);
    }

    public function markRead(Model $room, User $viewer): void
    {
        // Flip keeps its chat in a JSON column with no read receipts.
        $this->slot($room, $viewer);
    }

    /** @return array<string, mixed> */
    public function flipTile(FlipGame $room, User $viewer, int $tile): array
    {
        return $this->flip->flip($room, $tile, self::token($viewer));
    }

    public function preview(FlipGame $room, User $viewer): void
    {
        $this->flip->usePreview($room, self::token($viewer));
    }

    public function state(Model $room, User $viewer): array
    {
        /** @var FlipGame $room */
        // The caller may hold a model from before the move it just made, and
        // the service reads timers straight off whatever it is handed.
        $room = $room->fresh();
        $you = $this->slot($room, $viewer);
        $s = $this->flip->state($room, self::token($viewer));
        $room = $room->fresh();

        $who = fn ($slot) => (int) $slot === $you ? 'me' : 'partner';
        $card = fn (?string $path) => GameAssets::url($path);

        $winner = $s['winner'];

        return $this->envelope(
            'flip',
            $room->code,
            $this->status($room->status),
            $viewer,
            $this->partnerOf($room, $viewer),
            $you,
            (bool) ($s['ready'][(string) $you] ?? false),
            (bool) ($s['ready'][(string) ($you === 1 ? 2 : 1)] ?? false),
            $this->messages($s['messages'], $you),
            [
                'partner_joined' => (bool) $s['p2_joined'],
                'turn' => $who($s['turn']),
                'my_turn' => (int) $s['turn'] === $you,
                'scores' => [
                    'me' => $s['scores'][(string) $you],
                    'partner' => $s['scores'][(string) ($you === 1 ? 2 : 1)],
                ],
                'matched' => array_map(fn ($m) => [
                    'index' => $m['index'],
                    'image' => $card($m['image']),
                    'by' => $who($m['by']),
                ], $s['matched']),
                'flipped' => array_map(fn ($f) => [
                    'index' => $f['index'],
                    'image' => $card($f['image']),
                ], $s['flipped']),
                'mismatch_until' => $room->mismatch_until?->toIso8601String(),
                'preview' => [
                    'active' => $s['preview']['active'],
                    'until' => $s['preview']['until'],
                    'images' => $s['preview']['images']
                        ? array_map(fn ($p) => ['index' => $p['index'], 'image' => $card($p['image'])], $s['preview']['images'])
                        : null,
                ],
                'preview_used' => $s['preview_used'],
                'winner' => $winner === null ? null : ($winner === 'draw' ? 'draw' : $who($winner)),
                'chemistry_score' => $s['chemistry_score'],
                'card_back' => $card($s['card_back']),
                'tiles' => 16,
                'total_pairs' => 8,
                'invite_code' => $room->code,
                'rematch_code' => $this->rematchCode($room, $viewer),
            ],
            $this->startedAt($room),
        );
    }

    /**
     * When the board went live, for the app's rules-and-3-2-1 lead-in. Flip
     * has no start column; `last_move_at` is stamped as the game starts and
     * again on every move, so it only means "started" while nobody has moved.
     */
    protected function startedAt(FlipGame $room): ?int
    {
        $untouched = empty($room->matched) && $room->first_flip === null
            && $room->second_flip === null && ! $room->preview_used;

        return $untouched ? $room->last_move_at?->getTimestamp() : null;
    }

    /** Waiting/playing/finished/abandoned → the shared vocabulary. */
    protected function status(string $status): string
    {
        return match ($status) {
            'waiting' => 'lobby',
            'finished' => 'completed',
            default => $status,
        };
    }

    protected function rematchCode(FlipGame $room, User $viewer): ?string
    {
        if (! in_array($room->status, ['finished', 'abandoned'], true)) {
            return null;
        }

        $partner = $this->partnerOf($room, $viewer);
        $next = $partner ? $this->findActiveBetween($viewer, $partner) : null;

        return $next && $next->id > $room->id ? $next->code : null;
    }

    /**
     * @param  array<int, array<string, mixed>>  $raw
     * @return array<int, array<string, mixed>>
     */
    protected function messages(array $raw, int $you): array
    {
        return collect($raw)->values()->map(fn ($m, $i) => [
            'id' => $i + 1,
            'sender' => (int) $m['sender'] === $you ? 'me' : 'partner',
            'text' => $m['text'],
            'type' => 'text',
            'at' => $m['time'] ?? null,
            'read' => false,
        ])->all();
    }
}
