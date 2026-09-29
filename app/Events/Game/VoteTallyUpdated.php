<?php

namespace App\Events\Game;

/**
 * Someone voted: the live count for every option.
 */
class VoteTallyUpdated extends VoteEvent
{
    public function broadcastAs(): string
    {
        return 'vote.tally';
    }

    /**
     * @return array{id: int, tally: array<int, int>}
     */
    public function broadcastWith(): array
    {
        return ['id' => $this->session->id, 'tally' => $this->session->tally()];
    }
}
