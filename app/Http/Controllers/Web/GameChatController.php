<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\GameChatMessage;
use App\Models\GameRoom;
use App\Models\User;
use App\Services\Games\GameAccess;
use App\Services\Games\GameChatPolicy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GameChatController extends Controller
{
    public function __construct(
        protected GameChatPolicy $policy,
        protected GameAccess $access,
    ) {}

    /**
     * Send a text message or quick reply chip in live room chat.
     */
    public function send(Request $request, string $roomCode): JsonResponse
    {
        $user = $request->user();
        $room = GameRoom::where('room_code', $roomCode)->firstOrFail();

        if ($refusal = $this->refuse($room, $user)) {
            return $refusal;
        }

        // `system` is the room's own voice ("Both answered…"); a member
        // posting it skipped the free-plan filter and could impersonate it.
        $request->validate([
            'message' => 'required|string|max:500',
            'type' => 'nullable|in:text,quick_reply',
        ]);

        // One rule for web and app: free members send the chips, paid members
        // free text minus the blocked words (403 / 422 as JSON otherwise).
        $messageText = $this->policy->authorize($user, (string) $request->input('message'));

        $msg = GameChatMessage::create([
            'room_id' => $room->id,
            'sender_id' => $user->id,
            'message' => $messageText,
            'type' => $request->input('type', 'text'),
        ]);

        return response()->json([
            'success' => true,
            'message' => [
                'id' => $msg->id,
                'sender_id' => $msg->sender_id,
                'sender_name' => $user->name,
                'message' => $msg->message,
                'type' => $msg->type,
                'created_at' => $msg->created_at->format('g:i A'),
            ],
        ]);
    }

    /**
     * Mark unread messages in room as read.
     */
    public function markRead(Request $request, string $roomCode): JsonResponse
    {
        $user = $request->user();
        $room = GameRoom::where('room_code', $roomCode)->firstOrFail();

        if (! in_array($user->id, [$room->user_a_id, $room->user_b_id], true)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        GameChatMessage::where('room_id', $room->id)
            ->where('sender_id', '!=', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }

    /** Only the two players may post, and only while neither has blocked the other. */
    protected function refuse(GameRoom $room, User $user): ?JsonResponse
    {
        if (! in_array($user->id, [$room->user_a_id, $room->user_b_id], true)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $partner = User::find($user->id === $room->user_a_id ? $room->user_b_id : $room->user_a_id);

        if (! $partner || $partner->status !== 'active' || $this->access->blocked($user, $partner)) {
            return response()->json(['success' => false, 'message' => 'You can no longer chat with this member.'], 403);
        }

        return null;
    }
}
