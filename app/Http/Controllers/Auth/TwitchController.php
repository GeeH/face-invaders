<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

class TwitchController extends Controller
{
    /**
     * Send the streamer to Twitch to approve the login.
     */
    public function redirect(): SymfonyRedirectResponse
    {
        return Socialite::driver('twitch')
            ->setScopes(config('services.twitch.scopes'))
            ->redirect();
    }

    /**
     * Handle Twitch sending the streamer back, creating or updating their account.
     */
    public function callback(Request $request): RedirectResponse
    {
        if ($request->has('error')) {
            return to_route('home')->with('error', 'Twitch login was cancelled.');
        }

        try {
            $twitchUser = Socialite::driver('twitch')->user();
        } catch (InvalidStateException) {
            return to_route('home')->with('error', 'Your Twitch login expired, please try again.');
        }

        $user = User::updateOrCreate(
            ['twitch_id' => $twitchUser->getId()],
            [
                'twitch_login' => $twitchUser->getRaw()['login'],
                'display_name' => $twitchUser->getNickname(),
                'avatar_url' => $twitchUser->getAvatar(),
                'twitch_access_token' => $twitchUser->token,
                'twitch_refresh_token' => $twitchUser->refreshToken,
                'twitch_token_expires_at' => now()->addSeconds($twitchUser->expiresIn),
                'twitch_scopes' => $twitchUser->approvedScopes,
            ],
        );

        $user->syncFollowers();

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Log the streamer out.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('home');
    }
}
