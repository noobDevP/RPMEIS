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
        // Login throttle: 10 attempts per minute, keyed by username+IP so one user
        // cannot lock out another user on the same shared network (e.g. office LAN).
        // A single login attempt uses /auth/lookup-display-name + /login (2 hits),
        // so 10 allows ~5 real attempts before triggering protection.
        RateLimiter::for('login', function (Request $request) {
            $username = strtolower(trim((string) $request->input('username', '')));
            return Limit::perMinute(10)->by($username . '|' . $request->ip());
        });

        // Display name lookup is a non-destructive username hint — use a lenient
        // limiter so it does not eat into the stricter login attempt budget.
        RateLimiter::for('lookup', function (Request $request) {
            return Limit::perMinute(20)->by($request->ip());
        });

        Paginator::useBootstrapFive();
    }
}
