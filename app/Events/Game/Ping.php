<?php

namespace App\Events\Game;

use App\Models\User;

/**
 * A test message that shows as a banner in the game, to check a streamer's
 * browser source is connected (`php artisan game:ping`).
 */
class Ping extends GameEvent
{
    public function __construct(User $streamer, public string $message)
    {
        parent::__construct($streamer);
    }

    public function broadcastAs(): string
    {
        return 'ping';
    }

    /**
     * @return array{message: string}
     */
    public function broadcastWith(): array
    {
        return ['message' => $this->message];
    }
}
