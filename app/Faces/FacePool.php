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
    public function __construct(private StockFaces $stock) {}

    /**
     * @return list<Face>
     */
    public function for(User $streamer, int $size): array
    {
        $faces = $streamer->followers()
            ->inRandomOrder()
            ->limit($size)
            ->get()
            ->map(fn (Follower $follower) => new Face(
                providerUserId: $follower->provider_user_id,
                username: $follower->username,
                displayName: $follower->display_name,
                avatarUrl: $follower->avatar_url ?? $this->stock->avatarFor($follower->provider_user_id),
                followedAt: $follower->followed_at?->toImmutable(),
            ))
            ->all();

        return [...$faces, ...$this->stockFaces($size - count($faces))];
    }

    /**
     * Shuffled stock faces, going round again if more are needed than exist.
     *
     * @return list<Face>
     */
    private function stockFaces(int $count): array
    {
        $faces = [];

        for ($round = 1; count($faces) < $count; $round++) {
            $stock = $this->stock->all();
            shuffle($stock);

            foreach (array_slice($stock, 0, $count - count($faces)) as $face) {
                $faces[] = $round === 1 ? $face : new Face(
                    providerUserId: "{$face->providerUserId}-{$round}",
                    username: $face->username,
                    displayName: $face->displayName,
                    avatarUrl: $face->avatarUrl,
                    followedAt: null,
                );
            }
        }

        return $faces;
    }
}
