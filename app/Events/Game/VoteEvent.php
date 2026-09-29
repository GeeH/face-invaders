<?php

namespace App\Events\Game;

use App\Models\VoteSession;

/**
 * Something happened to a vote session; sent to that streamer's game.
 */
abstract class VoteEvent extends GameEvent
{
    public function __construct(public VoteSession $session)
    {
        parent::__construct($session->streamer);
    }
}
