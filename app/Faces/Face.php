<?php

namespace App\Faces;

use Carbon\CarbonImmutable;

/**
 * One viewer whose face can appear on an enemy, independent of where it came from.
 */
final readonly class Face
{
    public function __construct(
        /** The viewer's ID on the provider's platform (e.g. their Twitch user ID). */
        public string $providerUserId,
        /** The viewer's unique login name, e.g. "geeh". */
        public string $username,
        /** The name shown under the enemy, e.g. "GeeH". */
        public string $displayName,
        /** Null when the viewer has no avatar; the game shows a stock one instead. */
        public ?string $avatarUrl,
        /** When they followed, kept for the future boss feature. */
        public ?CarbonImmutable $followedAt,
    ) {}
}
