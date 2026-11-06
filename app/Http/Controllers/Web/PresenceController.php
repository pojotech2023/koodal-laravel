<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\BlockedUser;
use App\Models\User;
use App\Services\UserPresenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PresenceController extends Controller
{
    public function __construct(protected UserPresenceService $presenceService) {}

    /**
     * Heartbeat ping endpoint called by frontend JS every 30s.
     */
    public function ping(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user) {
            $this->presenceService->touchPresence($user);
        }

        return response()->json([
            'success'   => true,
            'is_online' => true,
        ]);
    }

    /**
     * Get presence status for a specific user.
     */
    public function status(Request $request, User $user): JsonResponse
    {
        // A block in either direction hides presence as well as the profile.
        if (BlockedUser::between($request->user()->id, $user->id)->exists()) {
            return response()->json($this->presenceService->formatStatus(null));
        }

        $status = $this->presenceService->formatStatus($user);

        return response()->json($status);
    }
}
