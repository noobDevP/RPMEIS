<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use App\Models\StaffUser;
use App\Models\AdminCredential;
class SingleSessionMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (session('staff.logged_in')) {
            $user = StaffUser::find(session('staff.user_id'));
            if ($user && $user->session_id && $user->session_id !== session()->getId()) {
                session()->flush();
                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json(['message' => 'Your account logged in from another device.'], 401);
                }
                return redirect('/login')->withErrors(['login' => 'Your account logged in from another device.']);
            }
        } elseif (session('admin.logged_in')) {
            $user = AdminCredential::find(session('admin.id'));
            if ($user && $user->session_id && $user->session_id !== session()->getId()) {
                session()->flush();
                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json(['message' => 'Your account logged in from another device.'], 401);
                }
                return redirect('/admin/login')->withErrors(['login' => 'Your account logged in from another device.']);
            }
        }
        return $next($request);
    }
}
