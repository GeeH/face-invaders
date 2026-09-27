<?php

use App\Faces\FakeFaceProvider;
use App\Faces\TwitchFaceProvider;

return [

    /*
    |--------------------------------------------------------------------------
    | Face Provider
    |--------------------------------------------------------------------------
    |
    | Where enemy faces come from. "twitch" uses the streamer's followers;
    | "fake" serves generated viewers, which is handy for local development
    | without a real Twitch channel.
    |
    */

    'provider' => env('FACE_PROVIDER', 'twitch'),

    'providers' => [
        'twitch' => TwitchFaceProvider::class,
        'fake' => FakeFaceProvider::class,
    ],

];
