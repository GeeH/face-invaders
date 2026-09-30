<?php

namespace App\Http\Controllers;

use App\Enums\VoteClosedBy;
use App\Models\User;
use App\Models\VoteSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The game opens chat's upgrade vote when a wave is cleared. The result
 * comes back over Reverb (docs/broadcasting.md).
 */
class VoteController extends Controller
{
    public function store(Request $request, string $token): JsonResponse
    {
        $streamer = $this->streamer($token);

        $validated = $request->validate([
            'wave' => ['required', 'integer', 'min:1'],
            // Chat types `!vote N`, so keep N to a single digit.
            'options' => ['required', 'array', 'min:1', 'max:9'],
            'options.*.id' => ['required', 'string', 'max:64', 'distinct'],
            'options.*.name' => ['required', 'string', 'max:64'],
            'options.*.description' => ['required', 'string', 'max:255'],
        ]);

        $session = VoteSession::open($streamer, $validated['wave'], $validated['options']);

        return response()->json([
            'id' => $session->id,
            'options' => $session->options,
            'closes_at' => $session->closes_at->toIso8601String(),
        ], 201);
    }

    /**
     * The streamer picked the upgrade in the game (with the number keys,
     * outside OBS): close chat's vote with that winner, so what's recorded
     * matches what was applied.
     */
    public function pick(Request $request, string $token, int $session): JsonResponse
    {
        $session = $this->streamer($token)->voteSessions()->findOrFail($session);

        $validated = $request->validate([
            'id' => ['required', 'string', Rule::in(array_column($session->options, 'id'))],
        ]);

        return response()->json(['winner' => $session->close(VoteClosedBy::Override, $validated['id'])]);
    }

    /**
     * The streamer picks option N of whatever vote is open, from a secret URL
     * they can put on a Stream Deck button. GET works too, because Stream
     * Deck's built-in Website action can only make GET requests.
     */
    public function pickNumber(string $token, int $number): JsonResponse
    {
        $session = $this->streamer($token)->openVote();

        if (! $session) {
            return response()->json(['message' => 'No vote is open right now.'], 409);
        }

        $option = $session->option($number);

        if (! $option) {
            return response()->json(['message' => "This vote has no option {$number}."], 422);
        }

        $winner = $session->pick($number);

        return response()->json(['winner' => $winner, 'name' => $session->option($number)['name']]);
    }

    private function streamer(string $token): User
    {
        return User::where('play_token', $token)->firstOrFail();
    }
}
