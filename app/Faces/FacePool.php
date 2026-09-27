<?php

namespace App\Faces;

use App\Models\Follower;
use App\Models\User;

/**
 * Picks the faces for one run of a streamer's game: their followers in a
 * random order, topped up with stock faces when there aren't enough.
 */
class FacePool
{
    /**
     * Stock faces not yet handed out in this pool, refilled and reshuffled when empty.
     *
     * @var list<Face>
     */
    private array $deck = [];

    private int $round = 0;

    public function __construct(private StockFaces $stock) {}

    /**
     * @return list<Face>
     */
    public function for(User $streamer, int $size): array
    {
        $this->deck = [];
        $this->round = 0;

        $faces = $streamer->followers()
            ->inRandomOrder()
            ->limit($size)
            ->get()
            ->map(fn (Follower $follower) => new Face(
                providerUserId: $follower->provider_user_id,
                username: $follower->username,
                displayName: $follower->display_name,
                // Followers without an avatar keep their name but borrow an alien, dealt
                // from the same deck as the stock faces so a run's aliens stay varied.
                avatarUrl: $follower->avatar_url ?? $this->deal()->avatarUrl,
                followedAt: $follower->followed_at?->toImmutable(),
            ))
            ->all();

        $stock = [];
        while (count($faces) + count($stock) < $size) {
            $stock[] = $this->deal();
        }

        return [...$faces, ...$stock];
    }

    /**
     * The next stock face from a shuffled deck, so every alien is used once
     * before any repeats. Repeats get a suffixed ID so IDs stay unique.
     */
    private function deal(): Face
    {
        if ($this->deck === []) {
            $this->deck = $this->stock->all();
            shuffle($this->deck);
            $this->round++;
        }

        $face = array_pop($this->deck);

        return $this->round === 1 ? $face : new Face(
            providerUserId: "{$face->providerUserId}-{$this->round}",
            username: $face->username,
            displayName: $face->displayName,
            avatarUrl: $face->avatarUrl,
            followedAt: null,
        );
    }
}
