<?php

namespace App\Services\Games;

use App\Services\Games\Rooms\FindItRoom;
use App\Services\Games\Rooms\FlipRoom;
use App\Services\Games\Rooms\MemorySnapRoom;
use App\Services\Games\Rooms\RoomDriver;
use App\Services\Games\Rooms\SyncRoom;

/**
 * Maps the `{type}` URL segment to its driver. An unknown type is a 404, the
 * same as an unknown code — the client cannot tell which half was wrong.
 */
class GameRoomRegistry
{
    /** @var array<string, class-string<RoomDriver>> */
    public const DRIVERS = [
        'sync' => SyncRoom::class,
        'find_it' => FindItRoom::class,
        'flip' => FlipRoom::class,
        'memory_snap' => MemorySnapRoom::class,
    ];

    public function driver(string $type): RoomDriver
    {
        $class = self::DRIVERS[$type] ?? null;
        abort_unless($class, 404, 'Unknown game.');

        return app($class);
    }

    /** @return array<int, string> */
    public function types(): array
    {
        return array_keys(self::DRIVERS);
    }

    public function has(string $type): bool
    {
        return isset(self::DRIVERS[$type]);
    }
}
