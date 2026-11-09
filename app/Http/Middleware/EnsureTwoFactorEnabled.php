<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\StaffUser;
use App\Models\AdminCredential;

class EnsureTwoFactorEnabled
{
    public function handle(Request $request, Closure $next)
    {
        $allowedRoutes = [
            '2fa.challenge', '2fa.verify', '2fa.settings', '2fa.enable', '2fa.disable',
            '2fa.recovery-codes', '2fa.recovery-codes.regenerate', '2fa.recovery', '2fa.recovery.verify',
            'logout', 'login', 'login.store', 'admin.login', 'admin.login.store',
        ];
        
        if (in_array($request->route()?->getName(), $allowedRoutes, true)) {
            return $next($request);
        }

        if (session('staff.logged_in')) {
            $user = StaffUser::find(session('staff.user_id'));
            if ($user && ! $user->two_factor_enabled) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Two-Factor Authentication setup required.'], 403);
                }

                return redirect()->route('2fa.settings')->with('warning', 'You must set up Two-Factor Authentication to access your account.');
            }
        } elseif (session('admin.logged_in')) {
            $user = AdminCredential::find(session('admin.id'));
            if ($user && ! $user->two_factor_enabled) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Two-Factor Authentication setup required.'], 403);
                }

                return redirect()->route('2fa.settings')->with('warning', 'You must set up Two-Factor Authentication to access your account.');
            }
        }

        return $next($request);
    }
}
