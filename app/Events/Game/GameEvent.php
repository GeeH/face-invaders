<?php

namespace App\Events\Game;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Something the streamer's game needs to hear about, pushed over Reverb.
 * Sent straight away rather than queued, since votes are time-sensitive.
 *
 * Subclasses name themselves with broadcastAs() and choose exactly what
 * the game receives with broadcastWith(). See docs/broadcasting.md.
 */
abstract class GameEvent implements ShouldBroadcastNow
{
    use Dispatchable;

    public function __construct(protected User $streamer) {}

    public function broadcastOn(): Channel
    {
        return new Channel($this->streamer->gameChannel());
    }
}
