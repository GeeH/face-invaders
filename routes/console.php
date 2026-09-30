<?php

use App\Chat\ChatListener;
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

Artisan::command('chat:listen', function (ChatListener $listener) {
    $this->info('Listening to chat for streamers with an active game. Ctrl+C to stop.');

    $listener->run(fn (string $line) => $this->line('['.now()->format('H:i:s')."] {$line}"));
})->purpose('Read active streamers\' chat and count !vote (long-running)');

Artisan::command('admin:grant {login : The streamer\'s Twitch login} {--revoke : Take admin away instead}', function (string $login) {
    $user = User::where('twitch_login', $login)->first();

    if (! $user) {
        $this->error("No streamer has logged in as \"{$login}\" yet.");

        return 1;
    }

    $user->forceFill(['is_admin' => ! $this->option('revoke')])->save();

    $this->info($user->is_admin ? "{$user->display_name} can now edit the game balance." : "{$user->display_name} is no longer an admin.");
})->purpose('Let a streamer edit the global game balance (/admin/balance)');

Schedule::command('followers:sync')->everySixHours()->withoutOverlapping();
