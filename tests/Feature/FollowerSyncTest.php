<?php

use App\Enums\FollowerSyncStatus;
use App\Faces\FaceProvider;
use App\Faces\FakeFaceProvider;
use App\Jobs\SyncFollowers;
use App\Models\Follower;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->streamer = User::factory()->create();
});

it('queues a sync from the dashboard button', function () {
    Queue::fake();

    $this->actingAs($this->streamer)
        ->post(route('followers.sync'))
        ->assertRedirect(route('dashboard'));

    Queue::assertPushed(SyncFollowers::class, fn (SyncFollowers $job) => $job->streamer->is($this->streamer));
    expect($this->streamer->fresh()->followers_sync_status)->toBe(FollowerSyncStatus::Queued);
});

it('does not queue another sync while one is in progress', function () {
    Queue::fake();
    $this->streamer->update(['followers_sync_status' => FollowerSyncStatus::Running]);

    $this->actingAs($this->streamer)->post(route('followers.sync'));

    Queue::assertNothingPushed();
});

it('limits how often the button can be pressed', function () {
    Queue::fake();

    $this->actingAs($this->streamer);
    $this->post(route('followers.sync'));
    $this->streamer->update(['followers_sync_status' => FollowerSyncStatus::Succeeded]);
    $this->post(route('followers.sync'));
    $this->streamer->update(['followers_sync_status' => FollowerSyncStatus::Succeeded]);

    $this->from(route('dashboard'))
        ->post(route('followers.sync'))
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('error', 'You just synced. Try again in a minute.');

    expect($this->streamer->fresh()->followers_sync_status)->toBe(FollowerSyncStatus::Succeeded);
});

it('requires login to sync', function () {
    $this->post(route('followers.sync'))->assertRedirect(route('login'));
    $this->getJson(route('followers.sync.show'))->assertUnauthorized();
});

it('reports the sync status for the dashboard to poll', function () {
    $this->freezeTime();
    Follower::factory()->for($this->streamer, 'streamer')->count(3)->create();
    $this->streamer->update([
        'followers_sync_status' => FollowerSyncStatus::Running,
        'followers_synced_at' => now()->subHour(),
    ]);

    $this->actingAs($this->streamer)
        ->getJson(route('followers.sync.show'))
        ->assertOk()
        ->assertExactJson([
            'status' => 'running',
            'in_progress' => true,
            'message' => 'Syncing your followers…',
            'synced_at' => now()->subHour()->toIso8601String(),
            'follower_count' => 3,
        ]);
});

it('marks the sync as running and then succeeded', function () {
    $seen = [];
    app()->instance(FaceProvider::class, new class($seen) extends FakeFaceProvider
    {
        public function __construct(private array &$seen)
        {
            parent::__construct(count: 1);
        }

        public function fetchFaces(User $streamer): array
        {
            $this->seen[] = $streamer->fresh()->followers_sync_status;

            return parent::fetchFaces($streamer);
        }
    });

    $this->streamer->syncFollowers();

    expect($seen)->toBe([FollowerSyncStatus::Running])
        ->and($this->streamer->fresh()->followers_sync_status)->toBe(FollowerSyncStatus::Succeeded);
});

it('marks the sync as failed once retries run out', function () {
    (new SyncFollowers($this->streamer))->failed(new RuntimeException('boom'));

    expect($this->streamer->fresh()->followers_sync_status)->toBe(FollowerSyncStatus::Failed);
});

it('shows followers and sync status on the dashboard', function () {
    Follower::factory()->for($this->streamer, 'streamer')->count(2)->create();
    $this->streamer->update([
        'followers_sync_status' => FollowerSyncStatus::Succeeded,
        'followers_synced_at' => now()->subMinutes(5),
    ]);

    $this->actingAs($this->streamer)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSeeInOrder(['2', 'followers ready to become enemies', 'Last synced', '5 minutes ago'])
        ->assertSee('Sync now')
        ->assertSee('Followers are up to date.')
        ->assertSee('data-in-progress="false"', escape: false);
});

it('shows a syncing state on the dashboard while a sync runs', function () {
    $this->streamer->update(['followers_sync_status' => FollowerSyncStatus::Queued]);

    $this->actingAs($this->streamer)
        ->get(route('dashboard'))
        ->assertSee('Syncing…')
        ->assertSee('Sync queued…')
        ->assertSee('data-in-progress="true"', escape: false);
});

it('tells the streamer to log in again when Twitch access has expired', function () {
    $this->streamer->update(['followers_sync_status' => FollowerSyncStatus::NeedsLogin]);

    $this->actingAs($this->streamer)
        ->get(route('dashboard'))
        ->assertSee('Log out and back in');
});
