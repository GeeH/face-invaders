<?php

use App\Http\Controllers\Auth\TwitchController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/auth/twitch/redirect', [TwitchController::class, 'redirect'])->name('login');
    Route::get('/auth/twitch/callback', [TwitchController::class, 'callback'])->name('auth.twitch.callback');
});

Route::middleware('auth')->group(function () {
    Route::view('/dashboard', 'dashboard')->name('dashboard');
    Route::post('/logout', [TwitchController::class, 'logout'])->name('logout');
});
