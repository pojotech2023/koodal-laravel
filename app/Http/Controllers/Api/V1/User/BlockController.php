<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Actions\Block\BlockUser;
use App\Actions\Block\UnblockUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Block\BlockRequest;
use App\Http\Resources\BlockResource;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BlockController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $blocks = $request->user()->blocks()
            // ProfileResource is not used here — see BlockResource — so this is
            // the whole eager-load: the name, the code and one thumbnail.
            ->with(['blockedUser.profile.primaryPhoto'])
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return ApiResponse::paginated($blocks, BlockResource::class, 'Blocked profiles retrieved successfully');
    }

    public function store(BlockRequest $request, BlockUser $block): JsonResponse
    {
        $target = User::findOrFail($request->integer('user_id'));

        $row = $block($request->user(), $target, $request->input('reason'));

        return ApiResponse::created(
            new BlockResource($row->load('blockedUser.profile.primaryPhoto')),
            'Profile blocked. They can no longer see your profile or contact you.',
        );
    }

    /**
     * Keyed on the blocked user, not the row id — the unblock button lives on
     * a profile card, which knows the person and not the block record.
     */
    public function destroy(User $user, Request $request, UnblockUser $unblock): JsonResponse
    {
        $unblock($request->user(), $user);

        // 204 whether or not a row went away. A repeated unblock is idempotent,
        // and a 404 here would confirm the block never existed.
        return ApiResponse::noContent();
    }
}
