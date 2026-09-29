<?php

namespace App\Events\Game;

/**
 * Voting is over: reveal the winner, apply it and start the next wave.
 */
class VoteClosed extends VoteEvent
{
    public function broadcastAs(): string
    {
        return 'vote.closed';
    }

    /**
     * @return array{id: int, winner: string, tally: array<int, int>}
     */
    public function broadcastWith(): array
    {
        return ['id' => $this->session->id, 'winner' => $this->session->winner, 'tally' => $this->session->tally()];
    }
}
