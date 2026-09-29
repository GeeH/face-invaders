<?php

use App\Events\Game\Ping;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('followers:sync {user? : The ID of one streamer to sync}', function (?string $user = null) {
    $streamers = User::query()->when($user, fn ($query) => $query->whereKey($user));

    $count = 0;
    $streamers->lazyById()->each(function (User $streamer) use (&$count) {
        $streamer->syncFollowers();
        $count++;
    });

    $this->info("Queued follower sync for {$count} streamer(s).");
})->purpose("Queue a sync of streamers' followers from their face provider");

Artisan::command('game:ping {user : The ID of the streamer} {message=Hello from Face Invaders!}', function (string $user, string $message) {
    $streamer = User::findOrFail($user);

    Ping::dispatch($streamer, $message);

    $this->info("Sent \"{$message}\" to {$streamer->display_name}'s game.");
})->purpose("Show a test message in a streamer's game, to check it's connected to Reverb");

Schedule::command('followers:sync')->everySixHours()->withoutOverlapping();
