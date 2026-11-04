<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Login throttle: Configurable via AUTH_THROTTLE_LOGIN_PER_MINUTE (default 10).
        // Can be increased during penetration testing / VAPT assessments in .env
        // (e.g. AUTH_THROTTLE_LOGIN_PER_MINUTE=120) to prevent 429 scanner lockouts.
        RateLimiter::for('login', function (Request $request) {
            $limit = (int) env('AUTH_THROTTLE_LOGIN_PER_MINUTE', 10);
            $username = strtolower(trim((string) $request->input('username', '')));
            return Limit::perMinute($limit)->by($username . '|' . $request->ip());
        });

        // Display name lookup throttle: Configurable via AUTH_THROTTLE_LOOKUP_PER_MINUTE (default 20).
        RateLimiter::for('lookup', function (Request $request) {
            $limit = (int) env('AUTH_THROTTLE_LOOKUP_PER_MINUTE', 20);
            return Limit::perMinute($limit)->by($request->ip());
        });

        Paginator::useBootstrapFive();
    }
}
