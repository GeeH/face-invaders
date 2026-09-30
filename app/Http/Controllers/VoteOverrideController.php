<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The streamer picks the winning upgrade from their dashboard, since they
 * can't click inside an OBS browser source. The game hears the result over
 * Reverb like any other close.
 */
class VoteOverrideController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $number = (int) $request->validate(['number' => ['required', 'integer', 'min:1']])['number'];
        $session = $request->user()->openVote();

        if (! $session) {
            return back()->with('error', 'That vote has already closed.');
        }

        if (! $session->option($number)) {
            return back()->with('error', "This vote has no option {$number}.");
        }

        $session->pick($number);

        return back()->with('status', "Picked {$session->option($number)['name']}.");
    }
}
