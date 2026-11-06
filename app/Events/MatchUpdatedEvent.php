<?php

namespace App\Events;

use App\Models\GameMatch;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MatchUpdatedEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public GameMatch $match) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('match.' . $this->match->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'MatchUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->match->id,
            'status' => $this->match->status,
            'winner_id' => $this->match->winner_id,
        ];
    }
}
