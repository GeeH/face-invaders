<?php

namespace App\Faces;

use App\Models\User;
use App\Twitch\Helix;
use Carbon\CarbonImmutable;
use Generator;
use Illuminate\Support\Arr;

/**
 * Faces from a streamer's Twitch followers.
 *
 * Followers come 100 at a time from Get Channel Followers, then their avatars
 * from Get Users, which also takes 100 IDs per call. Pages are yielded as
 * they arrive, so channels with huge follower counts are never held in memory
 * all at once.
 */
class TwitchFaceProvider implements FaceProvider
{
    private const PAGE_SIZE = 100;

    /**
     * Twitch's placeholder avatars (a silhouette on a coloured background) live under this path.
     */
    private const DEFAULT_AVATAR_PATH = '/user-default-pictures';

    public function __construct(private Helix $helix) {}

    public function name(): string
    {
        return 'twitch';
    }

    /**
     * @return Generator<int, Face>
     */
    public function fetchFaces(User $streamer): Generator
    {
        $cursor = null;

        do {
            $page = $this->helix->as($streamer)->get('channels/followers', array_filter([
                'broadcaster_id' => $streamer->twitch_id,
                'first' => self::PAGE_SIZE,
                'after' => $cursor,
            ]))->json();

            $followers = $page['data'] ?? [];
            $avatars = $this->avatarsFor($streamer, array_column($followers, 'user_id'));

            foreach ($followers as $follower) {
                yield new Face(
                    providerUserId: $follower['user_id'],
                    username: $follower['user_login'],
                    displayName: $follower['user_name'],
                    avatarUrl: $avatars[$follower['user_id']] ?? null,
                    followedAt: CarbonImmutable::parse($follower['followed_at']),
                );
            }

            $cursor = Arr::get($page, 'pagination.cursor');
        } while ($cursor !== null && $followers !== []);
    }

    /**
     * Avatar URLs keyed by user ID. Users who have since deleted their account
     * are missing from the response, and users still on Twitch's placeholder
     * avatar are left out on purpose, so both get a stock alien instead.
     *
     * @param  list<string>  $userIds
     * @return array<string, string>
     */
    private function avatarsFor(User $streamer, array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        // Get Users wants repeated ?id=1&id=2 parameters, not id[]=1&id[]=2.
        $query = implode('&', array_map(fn (string $id) => 'id='.urlencode($id), $userIds));

        $users = $this->helix->as($streamer)->get("users?{$query}")->json('data', []);

        return array_filter(
            array_column($users, 'profile_image_url', 'id'),
            fn (?string $url) => $url && ! str_contains($url, self::DEFAULT_AVATAR_PATH),
        );
    }
}
