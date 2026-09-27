<?php

use App\Faces\Face;
use App\Faces\FacePool;
use App\Faces\StockFaces;
use App\Http\Controllers\Auth\TwitchController;
use App\Http\Controllers\FollowerSyncController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

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

    // Spike for #10: the streamer's follower faces drawn by Phaser. Replaced by the real game in M2.
    Route::get('/spike/faces', fn (FacePool $pool, StockFaces $stock) => view('spike.faces', [
        'faces' => array_map(fn (Face $face) => [
            'name' => $face->displayName,
            'avatar' => $face->avatarUrl,
            // Twitch occasionally returns avatar URLs that 404, so the game needs a backup.
            'fallback' => $stock->avatarFor($face->providerUserId),
        ], $pool->for(auth()->user(), 40)),
    ]))->name('spike.faces');
    Route::post('/logout', [TwitchController::class, 'logout'])->name('logout');
});
