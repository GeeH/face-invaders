<?php

use App\Jobs\SyncFollowers;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('followers:sync {user? : The ID of one streamer to sync}', function (?string $user = null) {
    $streamers = User::query()->when($user, fn ($query) => $query->whereKey($user));

    $count = 0;
    $streamers->lazyById()->each(function (User $streamer) use (&$count) {
        SyncFollowers::dispatch($streamer);
        $count++;
    });

    $this->info("Queued follower sync for {$count} streamer(s).");
})->purpose("Queue a sync of streamers' followers from their face provider");

Schedule::command('followers:sync')->everySixHours()->withoutOverlapping();
