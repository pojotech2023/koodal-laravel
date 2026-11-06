<?php

namespace App\Services\Games;

use App\Models\GameInvite;
use App\Models\GameRoom;
use App\Models\KoodalSyncAnswer;
use App\Models\KoodalSyncSession;
use App\Models\User;
use App\Services\Notification\NotificationDispatcher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Invites over the API.
 *
 * Differences from GameInviteService (which the website keeps using): the room
 * an invite points at is stored on the invite; a repeat invite for the same
 * game is recognised instead of silently replacing the last one; accepting
 * never logs anybody in, it requires the receiver's own token; and every state
 * change is checked against who is asking.
 */
class GameInvites
{
    public function __construct(
        protected GameRoomRegistry $rooms,
        protected GameAccess $access,
        protected NotificationDispatcher $notifier,
    ) {}

    /**
     * @return array{invite: GameInvite, room: ?array{type: string, code: string}, is_duplicate: bool, joined?: bool}
     */
    public function send(User $sender, User $receiver, string $type, string $mode = 'live', bool $fresh = false): array
    {
        abort_unless($this->rooms->has($type), 422, 'Unknown game.');

        $this->access->assertCanPlay($sender, $receiver);

        $offline = $type === 'sync' && $mode === 'offline';
        // Stored as the website stores it, so either side reads it the same.
        $stored = $offline ? GameInvite::SYNC_OFFLINE : $type;

        // One transaction: if writing the invite fails, the room made for it
        // goes too. Otherwise the sender saw an error and the hub then offered
        // "Resume" for a room nobody was invited to.
        $result = DB::transaction(fn () => $this->open($sender, $receiver, $type, $stored, $offline, $fresh));

        if (! $result['is_duplicate']) {
            $this->notifyReceiver($result['invite'], $sender, $receiver, reminder: false);
        }

        return $result;
    }

    /**
     * @return array{invite: GameInvite, room: ?array{type: string, code: string}, is_duplicate: bool, joined?: bool}
     */
    protected function open(User $sender, User $receiver, string $type, string $stored, bool $offline, bool $fresh): array
    {
        $open = $this->waiting($sender, $receiver, $stored);

        if ($open && ! $fresh) {
            return ['invite' => $open, 'room' => $this->roomRef($open), 'is_duplicate' => true];
        }

        // Both pressed "Next Game" at once: the partner's invite to this game
        // is already waiting, so it is taken rather than leaving the room it
        // opened (a fresh invite leaves the pair's active room).
        $theirs = ($fresh && ! $offline) ? $this->waiting($receiver, $sender, $stored) : null;
        if ($theirs) {
            $taken = $this->take($sender, $receiver, $theirs);

            if ($taken['room']) {
                return ['invite' => $theirs, 'room' => $taken['room'], 'is_duplicate' => true, 'joined' => true];
            }
        }

        // A fresh invite replaces the open one rather than stacking beside it.
        GameInvite::where('sender_id', $sender->id)
            ->where('receiver_id', $receiver->id)
            ->where('game_type', $stored)
            ->where('status', 'pending')
            ->update(['status' => 'expired']);

        $code = null;
        if (! $offline) {
            $driver = $this->rooms->driver($type);
            $room = $driver->findActiveBetween($sender, $receiver);

            if ($room && $fresh) {
                $driver->leave($room, $sender);
                $room = null;
            }

            $room ??= $driver->create($sender, $receiver);
            $code = $driver->code($room);
        }

        $invite = GameInvite::create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'game_type' => $stored,
            'room_code' => $code,
            'status' => 'pending',
            'expires_at' => now()->addHours((int) config('game.timing.invite_ttl_hours', 24)),
        ]);

        return ['invite' => $invite, 'room' => $this->roomRef($invite), 'is_duplicate' => false];
    }

    /**
     * @return array{room: ?array{type: string, code: string}, offline_sync: ?array{partner_code: string}}
     */
    public function accept(User $user, GameInvite $invite): array
    {
        abort_unless($invite->receiver_id === $user->id, 404, 'Invite not found.');
        $this->assertOpen($invite);

        $sender = User::with('profile.primaryPhoto')->findOrFail($invite->sender_id);
        $this->access->assertCanPlay($user, $sender);

        // As in send(): an invite is never left "accepted" without its room.
        $result = DB::transaction(fn () => $this->take($user, $sender, $invite));
        $this->notifySender($invite, $user, $sender);

        return $result;
    }

    /**
     * @return array{room: ?array{type: string, code: string}, offline_sync: ?array{partner_code: string}}
     */
    protected function take(User $user, User $sender, GameInvite $invite): array
    {
        $invite->update(['status' => 'accepted']);

        $type = $invite->baseGameType();
        $driver = $this->rooms->driver($type);
        $room = $invite->room_code
            ? $this->findOrNull($driver, $invite->room_code)
            : $this->websiteSyncRoom($invite);

        // Sync played one-at-a-time has no room; the receiver answers on their
        // own and the results unlock when both are done.
        if ($type === 'sync' && $this->playsOffline($invite, $room)) {
            return ['room' => null, 'offline_sync' => ['partner_code' => $sender->profile_code]];
        }

        $room = ($room && $this->isOpen($room)) ? $room : $driver->findActiveBetween($sender, $user);
        $room ??= $driver->create($sender, $user);

        // Flip and Memory Snap need the guest seated; the others are already
        // addressed to both users by id.
        $driver->join($room, $user);

        $invite->update(['room_code' => $driver->code($room)]);

        return ['room' => ['type' => $type, 'code' => $driver->code($room)], 'offline_sync' => null];
    }

    public function decline(User $user, GameInvite $invite): void
    {
        abort_unless($invite->receiver_id === $user->id, 404, 'Invite not found.');
        $this->assertOpen($invite);

        $invite->update(['status' => 'declined']);
    }

    public function remind(User $user, GameInvite $invite): GameInvite
    {
        abort_unless($invite->sender_id === $user->id, 404, 'Invite not found.');
        $this->assertOpen($invite);

        abort_if(
            $invite->reminders_sent >= (int) config('game.timing.invite_max_reminders', 3),
            429,
            'You have already sent the maximum number of reminders.',
        );

        $receiver = User::findOrFail($invite->receiver_id);
        $this->access->assertCanPlay($user, $receiver);

        $invite->increment('reminders_sent');
        $invite->update(['last_reminder_at' => now()]);

        $this->notifyReceiver($invite, $user, $receiver, reminder: true);

        return $invite->refresh();
    }

    /**
     * What the app polls: invites waiting on me, my own open invites, and
     * invites I sent that were accepted since I last looked (returned once).
     *
     * @return array{incoming: \Illuminate\Support\Collection, outgoing: \Illuminate\Support\Collection, accepted: array<int, array<string, mixed>>}
     */
    public function pending(User $user): array
    {
        $incoming = GameInvite::with('sender.profile.primaryPhoto')
            ->where('receiver_id', $user->id)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->get();

        $outgoing = GameInvite::with('receiver.profile.primaryPhoto')
            ->where('sender_id', $user->id)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->get();

        $accepted = [];

        GameInvite::with('receiver')
            ->where('sender_id', $user->id)
            ->where('status', 'accepted')
            ->where('updated_at', '>=', now()->subMinutes(15))
            ->get()
            ->each(function (GameInvite $invite) use (&$accepted) {
                $ref = $this->roomRef($invite);

                // Marked consumed on first delivery so the sender is walked
                // into the room once, not every time the app polls.
                $invite->update(['status' => 'expired']);

                if ($ref) {
                    $accepted[] = ['invite_id' => $invite->id, 'room' => $ref,
                        'partner_code' => $invite->receiver?->profile_code];
                }
            });

        return compact('incoming', 'outgoing', 'accepted');
    }

    protected function waiting(User $sender, User $receiver, string $stored): ?GameInvite
    {
        return GameInvite::query()
            ->where('sender_id', $sender->id)
            ->where('receiver_id', $receiver->id)
            ->where('game_type', $stored)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();
    }

    /** @return array{type: string, code: string}|null */
    public function roomRef(GameInvite $invite): ?array
    {
        if (! $invite->room_code) {
            return null;
        }

        return ['type' => $invite->baseGameType(), 'code' => $invite->room_code];
    }

    /**
     * True when the sender has answered questions *with this receiver* that
     * the receiver has not answered yet — the offline "waiting for your
     * answers" case. The website asks the same question about the sender's
     * answers with anyone, which turns a live invite from anyone who has ever
     * played Sync into an offline one.
     *
     * For an invite that names a live room, only answers given after it was
     * sent count: an older offline round must not pull the receiver away from
     * the room the sender is waiting in.
     */
    public function senderAlreadyPlayed(GameInvite $invite): bool
    {
        $pairSessions = KoodalSyncSession::query()
            ->where('user_one_id', min($invite->sender_id, $invite->receiver_id))
            ->where('user_two_id', max($invite->sender_id, $invite->receiver_id))
            ->when($invite->room_code, fn ($q) => $q->where('created_at', '>=', $invite->created_at))
            ->select('id');

        return KoodalSyncAnswer::query()
            ->where('user_id', $invite->sender_id)
            ->whereIn('session_id', $pairSessions)
            ->whereNotIn('question_id', KoodalSyncAnswer::where('user_id', $invite->receiver_id)->select('question_id'))
            ->exists();
    }

    /**
     * Whether accepting a Sync invite means answering on your own: the sender
     * already did, or the invite has no live room to go to.
     */
    protected function playsOffline(GameInvite $invite, ?Model $room): bool
    {
        return $invite->isOfflineSync()
            || $this->senderAlreadyPlayed($invite)
            || ($invite->room_code === null && $room === null);
    }

    /**
     * The room behind a live Sync invite sent from the website. The website
     * does not record the room on the invite; it puts the pair's room back in
     * the lobby moments before writing the invite, so that is how it is found.
     * Null for an offline invite, which touches no room.
     */
    protected function websiteSyncRoom(GameInvite $invite): ?GameRoom
    {
        if ($invite->game_type !== 'sync' || $invite->room_code !== null) {
            return null;
        }

        return GameRoom::query()
            ->where('game_type', 'sync')
            ->where('status', 'lobby')
            ->where(fn ($q) => $q
                ->where(fn ($x) => $x->where('user_a_id', $invite->sender_id)->where('user_b_id', $invite->receiver_id))
                ->orWhere(fn ($x) => $x->where('user_a_id', $invite->receiver_id)->where('user_b_id', $invite->sender_id)))
            ->where('updated_at', '>=', $invite->created_at->copy()->subMinute())
            ->latest('updated_at')
            ->first();
    }

    protected function assertOpen(GameInvite $invite): void
    {
        abort_unless($invite->status === 'pending', 409, 'This invite is no longer pending.');
        abort_if($invite->expires_at && $invite->expires_at->isPast(), 410, 'This invite has expired.');
    }

    protected function findOrNull($driver, string $code)
    {
        try {
            return $driver->find($code);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return null;
        }
    }

    protected function isOpen($room): bool
    {
        return ! in_array($room->status, ['completed', 'finished', 'abandoned'], true);
    }

    /**
     * The invite is what matters; the notification is a courtesy (the app and
     * the website also poll for pending invites). A failure to write or queue
     * it is logged, never turned into an error for an invite that was saved.
     */
    protected function notifyReceiver(GameInvite $invite, User $sender, User $receiver, bool $reminder): void
    {
        $this->quietly(fn () => $this->sendReceiverNotice($invite, $sender, $receiver, $reminder));
    }

    protected function sendReceiverNotice(GameInvite $invite, User $sender, User $receiver, bool $reminder): void
    {
        $name = $sender->profile?->displayNameFor($receiver) ?? $sender->name;
        $game = $this->title($invite->baseGameType());

        $title = $reminder ? "⏰ {$name} is waiting to play {$game}" : "🎮 {$name} invited you to play {$game}";
        $body = $reminder ? 'Tap to join the game.' : 'Tap to accept and play together.';

        $this->notifier->send(
            $receiver,
            'game_invite',
            $title,
            $body,
            [
                'route' => '/games/invites/'.$invite->id,
                'reference_id' => (string) $invite->id,
                'invite_id' => $invite->id,
                'game_type' => $invite->baseGameType(),
                'sender_code' => $sender->profile_code,
            ],
        );

        // The push goes out from the notifier above (NotificationDispatcher →
        // SendPushNotification → PushSender → FirebaseNotificationService).
        // A second, direct push here delivered every invite twice.
    }

    protected function notifySender(GameInvite $invite, User $receiver, User $sender): void
    {
        $this->quietly(fn () => $this->sendSenderNotice($invite, $receiver, $sender));
    }

    protected function sendSenderNotice(GameInvite $invite, User $receiver, User $sender): void
    {
        $name = $receiver->profile?->displayNameFor($sender) ?? $receiver->name;
        $ref = $this->roomRef($invite);

        $title = "✅ {$name} accepted your {$this->title($invite->baseGameType())} invite";
        $body = $ref ? 'Tap to join the game room.' : 'They will answer on their own — you will be told when results are ready.';

        $this->notifier->send(
            $sender,
            'game_invite_accepted',
            $title,
            $body,
            [
                // Offline Sync has no room: the hub with this partner is where
                // the result will show up.
                'route' => $ref
                    ? "/games/rooms/{$ref['type']}/{$ref['code']}"
                    : '/games/partners/'.$receiver->profile_code,
                'reference_id' => (string) $invite->id,
                'invite_id' => $invite->id,
                'game_type' => $invite->baseGameType(),
            ],
        );

        // Pushed by the notifier above; see sendReceiverNotice().
    }

    protected function quietly(callable $notify): void
    {
        try {
            $notify();
        } catch (\Throwable $e) {
            Log::error('Game invite notification failed: '.$e->getMessage());
        }
    }

    protected function title(string $type): string
    {
        return collect(app(GameCatalog::class)->all())->firstWhere('type', $type)['title'] ?? 'a game';
    }
}
