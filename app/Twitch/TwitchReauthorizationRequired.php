<?php

namespace App\Twitch;

use App\Models\User;
use RuntimeException;
use Throwable;

/**
 * The streamer's Twitch tokens are gone or revoked, so they need to log in again.
 */
class TwitchReauthorizationRequired extends RuntimeException
{
    public function __construct(public readonly User $user, ?Throwable $previous = null)
    {
        parent::__construct("Twitch user {$user->twitch_id} needs to log in again.", previous: $previous);
    }
}
