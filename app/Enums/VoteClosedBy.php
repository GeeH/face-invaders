<?php

namespace App\Enums;

/**
 * Why a vote session closed.
 */
enum VoteClosedBy: string
{
    /** The voting window ran out. */
    case Timer = 'timer';

    /** The streamer picked the winner from the dashboard (#25). */
    case Override = 'override';

    /** The game opened a new vote first, e.g. after a restart. No winner. */
    case Replaced = 'replaced';
}
