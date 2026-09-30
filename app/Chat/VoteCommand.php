<?php

namespace App\Chat;

/**
 * Reads `!vote N` out of a chat message.
 */
final class VoteCommand
{
    /**
     * The option number voted for, or null if the message isn't a vote.
     * Case doesn't matter and anything after the number is ignored, so
     * "!VOTE 2 lets gooo" counts as a vote for 2.
     */
    public static function parse(string $message): ?int
    {
        if (! preg_match('/^\s*!vote\s+(\d{1,2})(?!\d)/i', $message, $match)) {
            return null;
        }

        return (int) $match[1];
    }
}
