<?php

namespace App\Services\Games\Rooms;

use App\Models\GameChatMessage;
use App\Models\GameRoom;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Shared behaviour of the two games stored in `game_rooms` (Sync, Find It).
 *
 * Every mutation re-reads the row under `lockForUpdate`. The website does a
 * plain read-modify-write on the JSON answer columns, so two players answering
 * in the same second can overwrite each other; here the second writer waits.
 */
abstract class GameRoomDriver implements RoomDriver
{
    use BuildsEnvelope;

    /** Content columns for a brand-new round (question ids, challenge id). */
    abstract protected function freshContent(User $a, User $b, ?GameRoom $previous = null): array;

    /** @return array<string, mixed> */
    abstract protected function game(GameRoom $room, User $viewer, int $slot): array;

    public function find(string $code): Model
    {
        return GameRoom::where('room_code', $code)
            ->where('game_type', $this->type())
            ->firstOrFail();
    }

    public function code(Model $room): string
    {
        return $room->room_code;
    }

    public function findActiveBetween(User $a, User $b): ?Model
    {
        return GameRoom::query()
            ->where('game_type', $this->type())
            ->whereIn('status', ['lobby', 'playing'])
            ->where(fn ($q) => $q
                ->where(fn ($x) => $x->where('user_a_id', $a->id)->where('user_b_id', $b->id))
                ->orWhere(fn ($x) => $x->where('user_a_id', $b->id)->where('user_b_id', $a->id)))
            ->latest('id')
            ->first();
    }

    public function create(User $host, User $guest): Model
    {
        return GameRoom::create([
            'room_code' => (string) Str::uuid(),
            'user_a_id' => $host->id,
            'user_b_id' => $guest->id,
            'game_type' => $this->type(),
            'status' => 'lobby',
            'user_a_answers' => [],
            'user_b_answers' => [],
            'user_a_score' => 0,
            'user_b_score' => 0,
            'user_a_ready' => false,
            'user_b_ready' => false,
            'current_question_index' => 0,
        ] + $this->freshContent($host, $guest));
    }

    /** 1 or 2; 403 for anyone who is not in the room. */
    protected function slot(GameRoom $room, User $viewer): int
    {
        return match ($viewer->id) {
            $room->user_a_id => 1,
            $room->user_b_id => 2,
            default => abort(403, 'You are not a participant in this game room.'),
        };
    }

    public function partnerOf(Model $room, User $viewer): ?User
    {
        $slot = $this->slot($room, $viewer);

        return User::with('profile.primaryPhoto')->find($slot === 1 ? $room->user_b_id : $room->user_a_id);
    }

    public function state(Model $room, User $viewer): array
    {
        /** @var GameRoom $room */
        $room = $this->settle($room->fresh());
        $slot = $this->slot($room, $viewer);

        return $this->envelope(
            $this->type(),
            $room->room_code,
            $room->status,
            $viewer,
            $this->partnerOf($room, $viewer),
            $slot,
            (bool) ($slot === 1 ? $room->user_a_ready : $room->user_b_ready),
            (bool) ($slot === 1 ? $room->user_b_ready : $room->user_a_ready),
            $this->messages($room, $viewer),
            $this->game($room, $viewer, $slot),
            $room->started_at?->getTimestamp(),
        );
    }

    /** Hook for time-driven transitions (Find It's clock). */
    protected function settle(GameRoom $room): GameRoom
    {
        return $room;
    }

    /** The room as of now: re-read, with any time-driven transition applied. */
    public function settled(GameRoom $room): GameRoom
    {
        return $this->settle($room->fresh());
    }

    /**
     * Give a lobby room its questions / challenge if it was created without
     * them (older website invites made bare rooms). A room already holding
     * content is never re-dealt here: the two players' pages must agree.
     */
    public function ensureContent(GameRoom $room): GameRoom
    {
        if (! empty($room->question_ids) || $room->status !== 'lobby') {
            return $room;
        }

        return $this->locked($room, function (GameRoom $r) {
            if (empty($r->question_ids) && $r->status === 'lobby') {
                $r->fill($this->freshContent(User::findOrFail($r->user_a_id), User::findOrFail($r->user_b_id), $r));
                $r->save();
            }
        });
    }

    /** 1 or 2 for a participant; 403 for anyone else. */
    public function seat(GameRoom $room, User $viewer): int
    {
        return $this->slot($room, $viewer);
    }

    public function join(Model $room, User $viewer): Model
    {
        $this->slot($room, $viewer);

        return $room;
    }

    public function ready(Model $room, User $viewer): Model
    {
        return $this->locked($room, function (GameRoom $r) use ($viewer) {
            $slot = $this->slot($r, $viewer);

            // A toggle only means something in the lobby; once the round is
            // running it must not un-start it.
            if ($r->status !== 'lobby') {
                return;
            }

            if ($slot === 1) {
                $r->user_a_ready = ! $r->user_a_ready;
            } else {
                $r->user_b_ready = ! $r->user_b_ready;
            }

            if ($r->user_a_ready && $r->user_b_ready) {
                $r->status = 'playing';
                $r->started_at = now();
                $this->system($r, $viewer, 'Both players are ready! Game starting...');
            }

            $r->save();
        });
    }

    public function leave(Model $room, User $viewer): Model
    {
        return $this->locked($room, function (GameRoom $r) use ($viewer) {
            $this->slot($r, $viewer);

            if (in_array($r->status, ['completed', 'abandoned'], true)) {
                return;
            }

            $r->status = 'abandoned';
            $r->save();
            $this->system($r, $viewer, $viewer->name.' left the room.');
        });
    }

    public function reset(Model $room, User $viewer): Model
    {
        return $this->locked($room, function (GameRoom $r) use ($viewer) {
            $this->slot($r, $viewer);

            $r->fill($this->freshContent(
                User::findOrFail($r->user_a_id),
                User::findOrFail($r->user_b_id),
                $r,
            ) + [
                'status' => 'lobby',
                'current_question_index' => 0,
                'user_a_answers' => [],
                'user_b_answers' => [],
                'user_a_score' => 0,
                'user_b_score' => 0,
                'user_a_ready' => false,
                'user_b_ready' => false,
                'started_at' => null,
                'ended_at' => null,
            ]);
            $r->save();

            $this->system($r, $viewer, $viewer->name.' restarted the game! Press Ready to play again 🔄');
        });
    }

    public function sendMessage(Model $room, User $viewer, string $text): void
    {
        $this->slot($room, $viewer);

        GameChatMessage::create([
            'room_id' => $room->id,
            'sender_id' => $viewer->id,
            'message' => $text,
            'type' => 'text',
        ]);
    }

    public function markRead(Model $room, User $viewer): void
    {
        $this->slot($room, $viewer);

        GameChatMessage::where('room_id', $room->id)
            ->where('sender_id', '!=', $viewer->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * Run $mutate on the locked row and return the refreshed model.
     *
     * @param  callable(GameRoom): void  $mutate
     */
    protected function locked(Model $room, callable $mutate): GameRoom
    {
        return DB::transaction(function () use ($room, $mutate) {
            $locked = GameRoom::whereKey($room->id)->lockForUpdate()->firstOrFail();
            $mutate($locked);

            return $locked->refresh();
        });
    }

    protected function system(GameRoom $room, User $actor, string $text): void
    {
        GameChatMessage::create([
            'room_id' => $room->id,
            'sender_id' => $actor->id,
            'message' => $text,
            'type' => 'system',
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    protected function messages(GameRoom $room, User $viewer): array
    {
        // Last 50, oldest first — the same window the website shows.
        return GameChatMessage::where('room_id', $room->id)
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->reverse()
            ->map(fn (GameChatMessage $m) => [
                'id' => $m->id,
                'sender' => $m->type === 'system' ? 'system' : ($m->sender_id === $viewer->id ? 'me' : 'partner'),
                'text' => $m->message,
                'type' => $m->type,
                'at' => $m->created_at?->toIso8601String(),
                'read' => $m->read_at !== null,
            ])
            ->values()
            ->all();
    }
}
