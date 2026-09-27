<?php

namespace App\Providers;

use App\Faces\FaceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Twitch\Provider as TwitchProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(FaceProvider::class, function (Application $app) {
            $name = config('faces.provider');
            $class = config("faces.providers.{$name}")
                ?? throw new InvalidArgumentException("Unknown face provider [{$name}].");

            return $app->make($class);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Each sync makes one Twitch request per 100 followers, so don't let the button be hammered.
        RateLimiter::for('follower-sync', fn (Request $request) => Limit::perMinute(2)->by($request->user()->id)
            ->response(fn () => back()->with('error', 'You just synced. Try again in a minute.')));

        Event::listen(function (SocialiteWasCalled $event) {
            $event->extendSocialite('twitch', TwitchProvider::class);
        });
    }
}
