<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AdminLoginRequest;
use App\Services\Auth\AdminAuthService;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function __construct(
        private AdminAuthService $adminAuth
    ) {}

    public function create(): View|RedirectResponse
    {
        if (session('admin.logged_in')) {
            return redirect('/admin/dashboard');
        }

        return view('admin.auth.login');
    }

    public function store(AdminLoginRequest $request): RedirectResponse
    {
        $admin = $this->adminAuth->authenticate(
            $request->username(),
            $request->password(),
        );

        if (! $admin) {
            return back()
                ->withInput($request->only('username'))
                ->withErrors(['login' => 'Invalid username or password!']);
        }

        
        if ($admin->two_factor_enabled) {
            session([
                '2fa.pending' => [
                    'type' => 'admin',
                    'id' => $admin->admin_id
                ]
            ]);
            return redirect()->route('2fa.challenge');
        }

        session([
            'admin.logged_in' => true,
            'admin.username' => (string) $admin->admin_username,
            'admin.id' => (int) $admin->admin_id,
        ]);

        $request->session()->regenerate();
        $admin->update(['session_id' => session()->getId()]);

        return redirect('/admin/dashboard');
    }
}
