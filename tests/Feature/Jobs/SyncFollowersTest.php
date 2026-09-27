<?php

use App\Faces\Face;
use App\Faces\FaceProvider;
use App\Faces\FakeFaceProvider;
use App\Jobs\SyncFollowers;
use App\Models\Follower;
use App\Models\User;
use App\Twitch\TwitchReauthorizationRequired;
use Carbon\CarbonImmutable;

function face(string $id, string $name = 'viewer', ?string $avatar = 'https://cdn.example/a.png'): Face
{
    return new Face($id, strtolower($name), $name, $avatar, CarbonImmutable::parse('2024-01-02 03:04:05'));
}

function useFaces(array $faces): void
{
    app()->instance(FaceProvider::class, new FakeFaceProvider($faces));
}

beforeEach(function () {
    $this->streamer = User::factory()->create();
});

it('stores new followers', function () {
    useFaces([face('1', 'Alice'), face('2', 'Bob', avatar: null)]);

    SyncFollowers::dispatchSync($this->streamer);

    $followers = $this->streamer->followers()->orderBy('provider_user_id')->get();
    expect($followers)->toHaveCount(2)
        ->and($followers[0])
        ->provider->toBe('fake')
        ->provider_user_id->toBe('1')
        ->username->toBe('alice')
        ->display_name->toBe('Alice')
        ->avatar_url->toBe('https://cdn.example/a.png')
        ->and($followers[0]->followed_at->toDateTimeString())->toBe('2024-01-02 03:04:05')
        ->and($followers[1]->avatar_url)->toBeNull();
});

it('updates followers who changed their name or avatar', function () {
    Follower::factory()->for($this->streamer, 'streamer')->create([
        'provider_user_id' => '1',
        'display_name' => 'OldName',
        'avatar_url' => 'old.png',
    ]);
    useFaces([face('1', 'NewName', avatar: 'new.png')]);

    SyncFollowers::dispatchSync($this->streamer);

    expect($this->streamer->followers()->sole())
        ->display_name->toBe('NewName')
        ->avatar_url->toBe('new.png');
});

it('removes people who have unfollowed', function () {
    $this->travel(-6)->hours();
    Follower::factory()->for($this->streamer, 'streamer')->create(['provider_user_id' => '1']);
    Follower::factory()->for($this->streamer, 'streamer')->create(['provider_user_id' => 'gone']);
    $this->travelBack();

    useFaces([face('1')]);

    SyncFollowers::dispatchSync($this->streamer);

    expect($this->streamer->followers()->pluck('provider_user_id')->all())->toBe(['1']);
});

it('keeps when a follower was last active', function () {
    $lastActive = now()->subMinutes(5)->startOfSecond();
    Follower::factory()->for($this->streamer, 'streamer')->create([
        'provider_user_id' => '1',
        'last_active_at' => $lastActive,
    ]);
    useFaces([face('1')]);

    SyncFollowers::dispatchSync($this->streamer);

    expect($this->streamer->followers()->sole()->last_active_at->equalTo($lastActive))->toBeTrue();
});

it('only touches the streamer being synced', function () {
    $this->travel(-6)->hours();
    $other = Follower::factory()->create(['provider_user_id' => 'someone-elses']);
    $this->travelBack();

    useFaces([face('1')]);

    SyncFollowers::dispatchSync($this->streamer);

    expect($other->fresh())->not->toBeNull();
});

it('stores followers in chunks without losing any', function () {
    app()->instance(FaceProvider::class, new FakeFaceProvider(count: 1234));

    SyncFollowers::dispatchSync($this->streamer);

    expect($this->streamer->followers()->count())->toBe(1234);
});

it('records when the streamer was last synced', function () {
    $this->freezeTime();
    useFaces([face('1')]);

    SyncFollowers::dispatchSync($this->streamer);

    expect($this->streamer->fresh()->followers_synced_at->equalTo(now()->startOfSecond()))->toBeTrue();
});

it('removes nobody when the sync fails part way through', function () {
    $this->travel(-6)->hours();
    Follower::factory()->for($this->streamer, 'streamer')->count(3)->create();
    $this->travelBack();

    app()->instance(FaceProvider::class, new class implements FaceProvider
    {
        public function name(): string
        {
            return 'fake';
        }

        public function fetchFaces(User $streamer): iterable
        {
            yield face('1');
            throw new RuntimeException('Twitch fell over');
        }
    });

    expect(fn () => SyncFollowers::dispatchSync($this->streamer))->toThrow(RuntimeException::class);

    expect($this->streamer->followers()->count())->toBe(3)
        ->and($this->streamer->fresh()->followers_synced_at)->toBeNull();
});

it('gives up without retrying when the streamer needs to log in again', function () {
    Follower::factory()->for($this->streamer, 'streamer')->create();

    app()->instance(FaceProvider::class, new class implements FaceProvider
    {
        public function name(): string
        {
            return 'fake';
        }

        public function fetchFaces(User $streamer): iterable
        {
            throw new TwitchReauthorizationRequired($streamer);
        }
    });

    $job = (new SyncFollowers($this->streamer))->withFakeQueueInteractions();
    $job->handle(app(FaceProvider::class));

    $job->assertFailed();
    expect($this->streamer->followers()->count())->toBe(1);
});
