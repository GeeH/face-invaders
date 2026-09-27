<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\View\View;

/**
 * Serves the game to OBS. The URL's token is the only thing identifying the
 * streamer, since a browser source can't log in.
 */
class PlayController extends Controller
{
    public function __invoke(string $token): View
    {
        $streamer = User::where('play_token', $token)->firstOrFail();

        return view('play', ['streamer' => $streamer]);
    }
}
