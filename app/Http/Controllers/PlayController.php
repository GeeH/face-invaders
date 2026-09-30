<?php

namespace App\Http\Controllers;

use App\Faces\Face;
use App\Faces\FacePool;
use App\Faces\StockFaces;
use App\Game\GameSettings;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;

/**
 * Serves the game to OBS. The URL's token is the only thing identifying the
 * streamer, since a browser source can't log in.
 */
class PlayController extends Controller
{
    /**
     * The game page.
     */
    public function show(string $token): View
    {
        return view('play', ['streamer' => $this->streamer($token)]);
    }

    /**
     * Everything the game needs to start a run: balance, the streamer's
     * settings and a fresh pool of faces. Fetched at the start of every run,
     * so balance changes apply without anyone reloading OBS.
     */
    public function run(string $token, FacePool $pool, StockFaces $stock): JsonResponse
    {
        $streamer = $this->streamer($token);
        $streamer->markGameSeen();

        return response()->json([
            'streamer' => [
                'name' => $streamer->display_name,
                'voting_window_seconds' => $streamer->voting_window_seconds,
            ],
            'balance' => GameSettings::current()->toArray(),
            'faces' => array_map(fn (Face $face) => [
                'id' => $face->providerUserId,
                'name' => $face->displayName,
                'avatar' => $face->avatarUrl,
                // Twitch occasionally returns avatar URLs that 404, so the game needs a backup.
                'fallback' => $stock->avatarFor($face->providerUserId),
            ], $pool->for($streamer, config('game.faces_per_run'))),
        ])->header('Cache-Control', 'no-store');
    }

    private function streamer(string $token): User
    {
        return User::where('play_token', $token)->firstOrFail();
    }
}
