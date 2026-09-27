<?php

namespace App\Faces;

use App\Models\User;

/**
 * A source of viewer faces for a streamer's enemies. Twitch is the only
 * provider in v1, but the game never talks to Twitch directly, so a
 * YouTube provider can be added later without touching it.
 */
interface FaceProvider
{
    /**
     * A short, stable name for the provider, e.g. "twitch". Stored with each
     * face so faces from different providers never collide.
     */
    public function name(): string;

    /**
     * Every face available for the streamer's enemies (for Twitch, their followers).
     *
     * @return iterable<Face>
     */
    public function fetchFaces(User $streamer): iterable;
}
