<?php

namespace App\Services\Games\Rooms;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * One implementation per live game. The four games store their rooms in three
 * different shapes (GameRoom, FlipGame, MemorySnapGame); the API presents all
 * of them through this contract so the controllers, the invite flow and the
 * mobile client never have to know which.
 *
 * Every method that takes a $viewer must refuse a non-participant — that check
 * lives in the driver, not the controller, so no route can forget it.
 */
interface RoomDriver
{
    /** sync | find_it | flip | memory_snap */
    public function type(): string;

    /** 404 when the code is unknown or belongs to another game type. */
    public function find(string $code): Model;

    public function code(Model $room): string;

    /** Latest unfinished room between the pair, or null. */
    public function findActiveBetween(User $a, User $b): ?Model;

    /** Create a fresh room for the pair; $host is slot 1. */
    public function create(User $host, User $guest): Model;

    /** The other participant, or null while a Flip link game is still open. */
    public function partnerOf(Model $room, User $viewer): ?User;

    /**
     * Full state for this viewer, in the shared envelope.
     *
     * @return array<string, mixed>
     */
    public function state(Model $room, User $viewer): array;

    /** Idempotent for an existing participant. */
    public function join(Model $room, User $viewer): Model;

    public function ready(Model $room, User $viewer): Model;

    public function leave(Model $room, User $viewer): Model;

    /** Rematch: returns the room to play next (same room, or a new one). */
    public function reset(Model $room, User $viewer): Model;

    public function sendMessage(Model $room, User $viewer, string $text): void;

    public function markRead(Model $room, User $viewer): void;
}
