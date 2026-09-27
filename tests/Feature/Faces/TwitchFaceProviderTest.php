<?php

use App\Faces\Face;
use App\Faces\FaceProvider;
use App\Faces\TwitchFaceProvider;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;

beforeEach(function () {
    $this->streamer = User::factory()->create([
        'twitch_id' => '1000',
        'twitch_access_token' => 'streamer-token',
    ]);
});

function follower(string $id, string $login, string $followedAt = '2024-05-01T12:00:00Z'): array
{
    return ['user_id' => $id, 'user_login' => $login, 'user_name' => ucfirst($login), 'followed_at' => $followedAt];
}

function followersPage(array $followers, ?string $cursor = null): array
{
    return ['data' => $followers, 'pagination' => $cursor ? ['cursor' => $cursor] : [], 'total' => count($followers)];
}

function twitchUsers(array $avatarsById): array
{
    return ['data' => collect($avatarsById)->map(fn ($url, $id) => [
        'id' => (string) $id,
        'profile_image_url' => $url,
    ])->values()->all()];
}

function fetchFaces(User $streamer): array
{
    return iterator_to_array(app(TwitchFaceProvider::class)->fetchFaces($streamer), preserve_keys: false);
}

it('turns followers into faces with their avatars', function () {
    Http::fake([
        'api.twitch.tv/helix/channels/followers*' => Http::response(followersPage([
            follower('1', 'alice', '2021-03-04T05:06:07Z'),
            follower('2', 'bob'),
        ])),
        'api.twitch.tv/helix/users*' => Http::response(twitchUsers([
            '1' => 'https://cdn.example/alice.png',
            '2' => 'https://cdn.example/bob.png',
        ])),
    ]);

    $faces = fetchFaces($this->streamer);

    expect($faces)->toHaveCount(2)->each->toBeInstanceOf(Face::class)
        ->and($faces[0])
        ->providerUserId->toBe('1')
        ->username->toBe('alice')
        ->displayName->toBe('Alice')
        ->avatarUrl->toBe('https://cdn.example/alice.png')
        ->and($faces[0]->followedAt->toIso8601ZuluString())->toBe('2021-03-04T05:06:07Z')
        ->and($faces[1]->avatarUrl)->toBe('https://cdn.example/bob.png');
});

it('authenticates as the streamer and asks for their followers', function () {
    Http::fake([
        'api.twitch.tv/helix/channels/followers*' => Http::response(followersPage([follower('1', 'alice')])),
        'api.twitch.tv/helix/users*' => Http::response(twitchUsers(['1' => 'https://cdn.example/alice.png'])),
    ]);

    fetchFaces($this->streamer);

    Http::assertSent(fn (Request $request) => str_contains($request->url(), '/channels/followers')
        && $request->data() === ['broadcaster_id' => '1000', 'first' => 100]
        && $request->hasHeader('Authorization', 'Bearer streamer-token')
        && $request->hasHeader('Client-Id', 'test-client-id'));
});

it('pages through every follower using the cursor', function () {
    Http::fake([
        'api.twitch.tv/helix/channels/followers*' => Http::sequence()
            ->push(followersPage([follower('1', 'alice'), follower('2', 'bob')], cursor: 'page-2'))
            ->push(followersPage([follower('3', 'carol')])),
        'api.twitch.tv/helix/users*' => Http::sequence()
            ->push(twitchUsers(['1' => 'a.png', '2' => 'b.png']))
            ->push(twitchUsers(['3' => 'c.png'])),
    ]);

    $faces = fetchFaces($this->streamer);

    expect(array_column($faces, 'username'))->toBe(['alice', 'bob', 'carol'])
        ->and(array_column($faces, 'avatarUrl'))->toBe(['a.png', 'b.png', 'c.png']);

    Http::assertSent(fn (Request $request) => str_contains($request->url(), '/channels/followers')
        && ($request['after'] ?? null) === 'page-2');
    Http::assertSentCount(4);
});

it('fetches avatars for a whole page in one request with repeated id parameters', function () {
    Http::fake([
        'api.twitch.tv/helix/channels/followers*' => Http::response(followersPage([
            follower('1', 'alice'),
            follower('2', 'bob'),
        ])),
        'api.twitch.tv/helix/users*' => Http::response(twitchUsers([])),
    ]);

    fetchFaces($this->streamer);

    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/users?id=1&id=2'));
});

it('leaves the avatar empty for followers Twitch no longer returns', function () {
    Http::fake([
        'api.twitch.tv/helix/channels/followers*' => Http::response(followersPage([
            follower('1', 'alice'),
            follower('2', 'deleted'),
        ])),
        'api.twitch.tv/helix/users*' => Http::response(twitchUsers(['1' => 'a.png'])),
    ]);

    $faces = fetchFaces($this->streamer);

    expect($faces[1]->avatarUrl)->toBeNull();
});

it('treats Twitch\'s placeholder avatar as no avatar', function () {
    Http::fake([
        'api.twitch.tv/helix/channels/followers*' => Http::response(followersPage([
            follower('1', 'alice'),
            follower('2', 'nopic'),
        ])),
        'api.twitch.tv/helix/users*' => Http::response(twitchUsers([
            '1' => 'https://static-cdn.jtvnw.net/jtv_user_pictures/alice-profile_image-300x300.png',
            '2' => 'https://static-cdn.jtvnw.net/user-default-pictures-uv/215b7342-def9-11e9-9a66-784f43822e80-profile_image-300x300.png',
        ])),
    ]);

    $faces = fetchFaces($this->streamer);

    expect($faces[0]->avatarUrl)->toContain('alice-profile_image')
        ->and($faces[1]->avatarUrl)->toBeNull();
});

it('returns no faces for a channel with no followers', function () {
    Http::fake([
        'api.twitch.tv/helix/channels/followers*' => Http::response(followersPage([])),
    ]);

    expect(fetchFaces($this->streamer))->toBe([]);

    Http::assertSentCount(1);
});

it('waits for the rate limit to reset and tries again', function () {
    Sleep::fake();
    $this->freezeTime();

    Http::fake([
        'api.twitch.tv/helix/channels/followers*' => Http::sequence()
            ->push([], 429, ['Ratelimit-Reset' => (string) (now()->timestamp + 5)])
            ->push(followersPage([follower('1', 'alice')])),
        'api.twitch.tv/helix/users*' => Http::response(twitchUsers(['1' => 'a.png'])),
    ]);

    expect(fetchFaces($this->streamer))->toHaveCount(1);

    Sleep::assertSleptTimes(1);
    Sleep::assertSequence([Sleep::for(5250)->milliseconds()]);
});

it('gives up when Twitch keeps rate limiting', function () {
    Sleep::fake();

    Http::fake([
        'api.twitch.tv/helix/channels/followers*' => Http::response([], 429),
    ]);

    fetchFaces($this->streamer);
})->throws(RequestException::class);

it('does not retry other errors', function () {
    Http::fake([
        'api.twitch.tv/helix/channels/followers*' => Http::response(['message' => 'Missing scope'], 401),
    ]);

    try {
        fetchFaces($this->streamer);
    } finally {
        Http::assertSentCount(1);
    }
})->throws(RequestException::class);

it('is the provider used when faces come from twitch', function () {
    config(['faces.provider' => 'twitch']);

    expect(app(FaceProvider::class))->toBeInstanceOf(TwitchFaceProvider::class)
        ->and(app(FaceProvider::class)->name())->toBe('twitch');
});
