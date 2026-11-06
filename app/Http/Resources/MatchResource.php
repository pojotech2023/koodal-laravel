<?php

namespace App\Http\Resources;

use App\Models\GameMatch;
use App\Services\Games\GameAccess;
use App\Services\Games\GameAssets;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One Spot the Difference match as the player sees it — shared by the website's
 * polling script and the mobile app.
 *
 * Hotspot coordinates are the answer key, so the puzzle section carries only a
 * *count* of differences and `claims` lists only the ones already found. A
 * client never receives the location of a difference nobody has tapped.
 *
 * @mixin GameMatch
 */
class MatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var GameMatch $match */
        $match = $this->resource;
        $viewer = $request->user();
        $access = app(GameAccess::class);

        $match->loadMissing(['puzzle.differences', 'players.user.profile.primaryPhoto', 'claims']);
        $puzzle = $match->puzzle;

        $secondsLeft = $match->status === 'playing' && $match->ends_at
            ? max(0, (int) round(now()->diffInSeconds($match->ends_at, false)))
            : 0;

        $startsIn = $match->status === 'playing' && $match->started_at && $match->started_at->isFuture()
            ? (int) ceil(now()->diffInSeconds($match->started_at, false))
            : 0;

        $differences = $puzzle->differences->keyBy('id');
        $found = $match->claims->where('is_correct', true)->values();

        return [
            'id' => $match->id,
            'status' => $match->status,
            'host_id' => $match->host_id,
            'winner_id' => $match->winner_id,
            'seconds_left' => $secondsLeft,
            'starts_in' => $startsIn,
            'server_time' => microtime(true),
            'started_at' => $match->started_at?->toIso8601String(),
            'ends_at' => $match->ends_at?->toIso8601String(),

            'players' => $match->players->map(function ($player) use ($viewer, $access) {
                $card = $player->user ? $access->card($viewer ?? $player->user, $player->user) : null;

                return [
                    'id' => $player->id,
                    'user_id' => $player->user_id,
                    'name' => $card['name'] ?? $player->user?->name,
                    'profile_code' => $player->user?->profile_code,
                    'avatar' => $card['avatar'] ?? null,
                    'status' => $player->status,
                    'score' => $player->score,
                    'correct_count' => $player->correct_count,
                    'wrong_count' => $player->wrong_count,
                    'is_me' => $viewer && $player->user_id === $viewer->id,
                ];
            })->values(),

            'puzzle' => [
                'id' => $puzzle->id,
                'title' => $puzzle->title,
                'difficulty' => $puzzle->difficulty,
                'width' => $puzzle->width,
                'height' => $puzzle->height,
                'time_limit_seconds' => $puzzle->time_limit_seconds,
                'original_image_url' => GameAssets::url($puzzle->original_image),
                'modified_image_url' => GameAssets::url($puzzle->modified_image),
                'total_differences' => $differences->count(),
                'found_differences_count' => $found->count(),
            ],

            'claims' => $found->map(function ($claim) use ($viewer, $differences) {
                $diff = $differences->get($claim->difference_id);

                return [
                    'id' => $claim->id,
                    'user_id' => $claim->user_id,
                    'is_me' => $viewer && $claim->user_id === $viewer->id,
                    'x_pct' => (float) $claim->x_pct,
                    'y_pct' => (float) $claim->y_pct,
                    'points' => $claim->points,
                    'difference_id' => $claim->difference_id,
                    'label' => $diff?->label,
                    'animation' => $diff ? [
                        'type' => $diff->animation_type,
                        'animate_on' => $diff->animate_on,
                        'sprite_url' => GameAssets::url($diff->sprite_path),
                        'lottie_url' => GameAssets::url($diff->lottie_path),
                        'sprite_bbox' => [
                            'x_pct' => $diff->sprite_x_pct ?? ($diff->x_pct - $diff->radius_pct),
                            'y_pct' => $diff->sprite_y_pct ?? ($diff->y_pct - $diff->radius_pct),
                            'w_pct' => $diff->sprite_w_pct ?? ($diff->radius_pct * 2),
                            'h_pct' => $diff->sprite_h_pct ?? ($diff->radius_pct * 2),
                        ],
                    ] : null,
                ];
            })->values(),
        ];
    }
}
