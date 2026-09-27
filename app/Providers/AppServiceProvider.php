<?php

namespace App\Providers;

use App\Faces\FaceProvider;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Event;
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
        Event::listen(function (SocialiteWasCalled $event) {
            $event->extendSocialite('twitch', TwitchProvider::class);
        });
    }
}
