<?php

use App\Faces\FakeFaceProvider;

return [

    /*
    |--------------------------------------------------------------------------
    | Face Provider
    |--------------------------------------------------------------------------
    |
    | Where enemy faces come from. "fake" serves generated viewers, which is
    | handy for local development without a real Twitch channel.
    |
    */

    'provider' => env('FACE_PROVIDER', 'fake'),

    'providers' => [
        'fake' => FakeFaceProvider::class,
    ],

];
