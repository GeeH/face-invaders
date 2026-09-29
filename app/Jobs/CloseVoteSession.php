<?php

namespace App\Jobs;

use App\Models\VoteSession;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\DeleteWhenMissingModels;

/**
 * Closes a vote when its window runs out. Dispatched with a delay when the
 * vote opens; does nothing if an override already closed it.
 */
#[DeleteWhenMissingModels]
class CloseVoteSession implements ShouldQueue
{
    use Queueable;

    public function __construct(public VoteSession $session) {}

    public function handle(): void
    {
        $this->session->close();
    }
}
