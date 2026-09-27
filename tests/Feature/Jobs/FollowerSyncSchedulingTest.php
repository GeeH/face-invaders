<?php

use App\Jobs\SyncFollowers;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Queue;

it('queues a sync for every streamer', function () {
    Queue::fake();
    User::factory()->count(3)->create();

    $this->artisan('followers:sync')
        ->expectsOutput('Queued follower sync for 3 streamer(s).')
        ->assertSuccessful();

    Queue::assertPushed(SyncFollowers::class, 3);
});

it('queues a sync for one streamer', function () {
    Queue::fake();
    [$streamer] = User::factory()->count(2)->create();

    $this->artisan('followers:sync', ['user' => $streamer->id])->assertSuccessful();

    Queue::assertPushed(SyncFollowers::class, 1);
    Queue::assertPushed(SyncFollowers::class, fn (SyncFollowers $job) => $job->streamer->is($streamer));
});

it('syncs followers every six hours', function () {
    $event = collect(app(Schedule::class)->events())
        ->first(fn ($event) => str_contains($event->command, 'followers:sync'));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('0 */6 * * *');
});
