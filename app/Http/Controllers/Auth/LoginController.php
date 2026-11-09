<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;

use App\Services\Auth\StaffAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function __construct(
        private StaffAuthService $staffAuth
    ) {}

    public function create(): View|RedirectResponse
    {
        if (session('staff.logged_in') && session('staff.role')) {
            $routeName = config('roles.routes.'.session('staff.role'));
            $legacyPath = config('roles.legacy_redirects.'.session('staff.role'));

            if ($routeName) {
                try {
                    return redirect()->route($routeName);
                } catch (\Illuminate\Routing\Exceptions\UrlGenerationException) {
                    if ($legacyPath) {
                        return redirect($legacyPath);
                    }
                }
            } elseif ($legacyPath) {
                return redirect($legacyPath);
            }
        }

        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $user = $this->staffAuth->authenticate(
            $request->username(),
            $request->password(),
        );

        if (! $user) {
            $username = (string) $request->input('username', '');
            $safeInput = (!str_contains($username, '/') && !str_contains($username, '\\') && !str_contains($username, '..'))
                ? ['username' => $username]
                : [];

            return back()
                ->withInput($safeInput)
                ->withErrors(['login' => 'Invalid username or password.']);
        }

        if (! $user->isActive()) {
            $username = (string) $request->input('username', '');
            $safeInput = (!str_contains($username, '/') && !str_contains($username, '\\') && !str_contains($username, '..'))
                ? ['username' => $username]
                : [];

            return back()
                ->withInput($safeInput)
                ->withErrors(['login' => 'Account is inactive. Contact admin.']);
        }

        
        if ($user->two_factor_enabled) {
            $request->session()->forget(['staff', 'admin']);
            $request->session()->regenerate();
            session([
                '2fa.pending' => [
                    'type' => 'staff',
                    'id' => $user->user_id
                ]
            ]);
            $request->session()->save();
            return redirect()->route('2fa.challenge');
        }

        $request->session()->regenerate();
        session([
            'staff.logged_in' => true,
            'staff.username' => (string) $user->username,
            'staff.user_username' => $user->user_username,
            'staff.user_id' => (int) $user->user_id,
            'staff.role' => $user->role()?->value,
        ]);

        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
            'session_id' => session()->getId(),
        ]);

        \App\Services\ActivityLogger::log('Logged In', null, $user->user_username, $user->role()?->value);

        if (!$user->two_factor_enabled) {
            $request->session()->save();
            return redirect()->route('2fa.settings')->with('warning', 'You must set up Two-Factor Authentication to access your account.');
        }

        $request->session()->save();
        return redirect($this->staffAuth->redirectPathFor($user));
    }

    public function lookupDisplayName(Request $request): Response
    {
        $rawUsername = (string) $request->input('username', '');
        if (str_contains($rawUsername, '/') || str_contains($rawUsername, '\\') || str_contains($rawUsername, '..')) {
            return response('Invalid username', 400, ['Content-Type' => 'text/plain']);
        }

        $request->validate([
            'username' => ['required', 'string', 'max:50', 'regex:/^[a-zA-Z0-9_\-\.\@\s]+$/'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $displayName = $this->staffAuth->lookupDisplayName(
            $request->input('username'),
            $request->input('password'),
        );

        return response($displayName ?? '', 200, [
            'Content-Type' => 'text/plain',
        ]);
    }
}
