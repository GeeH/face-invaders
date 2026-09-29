<?php

namespace App\Events\Game;

/**
 * Chat can vote: show the numbered options and the countdown.
 */
class VoteOpened extends VoteEvent
{
    public function broadcastAs(): string
    {
        return 'vote.opened';
    }

    /**
     * @return array{id: int, options: list<array{number: int, id: string, name: string, description: string}>, closes_at: string}
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->session->id,
            'options' => $this->session->options,
            'closes_at' => $this->session->closes_at->toIso8601String(),
        ];
    }
}
