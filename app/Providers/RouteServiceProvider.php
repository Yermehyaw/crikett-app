<?php

namespace App\Providers;

use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5)->by(
                $request->ip() . '|' . $request->input('email')
            );
        });

        RateLimiter::for('password', function (Request $request) {
            return Limit::perMinute(3)->by(
                $request->ip() . '|' . $request->input('email')
            );
        });

        RateLimiter::for('verification', function (Request $request) {
            return Limit::perMinute(2)->by(
                optional($request->user())->id ?: $request->ip()
            );
        });
    }
}
