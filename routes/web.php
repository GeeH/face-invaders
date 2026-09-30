<?php

use App\Http\Controllers\Admin\BalanceController;
use App\Http\Controllers\Auth\TwitchController;
use App\Http\Controllers\FollowerSyncController;
use App\Http\Controllers\PlayController;
use App\Http\Controllers\VoteController;
use App\Http\Controllers\VoteOverrideController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

// The game, loaded by OBS as a browser source. The token identifies the streamer.
Route::get('/play/{token}', [PlayController::class, 'show'])->name('play');
Route::get('/play/{token}/run', [PlayController::class, 'run'])
    ->middleware('throttle:game-run')
    ->name('play.run');
Route::post('/play/{token}/votes', [VoteController::class, 'store'])
    ->middleware('throttle:game-run')
    ->name('play.votes');
Route::post('/play/{token}/votes/{session}/pick', [VoteController::class, 'pick'])
    ->middleware('throttle:game-run')
    ->name('play.votes.pick');
// Stream Deck buttons: pick option N of the open vote. GET for Stream Deck's built-in Website action.
Route::match(['get', 'post'], '/play/{token}/pick/{number}', [VoteController::class, 'pickNumber'])
    ->whereNumber('number')
    ->middleware('throttle:game-run')
    ->name('play.pick');

Route::middleware('guest')->group(function () {
    Route::get('/auth/twitch/redirect', [TwitchController::class, 'redirect'])->name('login');
    Route::get('/auth/twitch/callback', [TwitchController::class, 'callback'])->name('auth.twitch.callback');
});

Route::middleware('auth')->group(function () {
    Route::view('/dashboard', 'dashboard')->name('dashboard');
    Route::get('/followers/sync', [FollowerSyncController::class, 'show'])->name('followers.sync.show');
    Route::post('/followers/sync', [FollowerSyncController::class, 'store'])
        ->middleware('throttle:follower-sync')
        ->name('followers.sync');
    Route::post('/vote/pick', [VoteOverrideController::class, 'store'])->name('vote.pick');
    Route::post('/logout', [TwitchController::class, 'logout'])->name('logout');
});

Route::middleware(['auth', 'can:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/balance', [BalanceController::class, 'edit'])->name('balance');
    Route::put('/balance', [BalanceController::class, 'update'])->name('balance.update');
    Route::delete('/balance', [BalanceController::class, 'destroy'])->name('balance.reset');
});
