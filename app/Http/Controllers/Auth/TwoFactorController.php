<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AdminCredential;
use App\Models\StaffUser;
use App\Services\ActivityLogger;
use App\Services\Auth\StaffAuthService;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorController extends Controller
{
    public function __construct(private StaffAuthService $auth) {}

    public function challenge()
    {
        if (!session()->has('2fa.pending')) {
            return redirect()->route('login');
        }

        return view('auth.2fa_challenge');
    }

    public function verify(Request $request)
    {
        $request->validate([
            'otp' => ['required', 'string', 'digits:6'],
        ]);

        $pending = session('2fa.pending');
        if (!$pending) {
            return redirect()->route('login');
        }

        $google2fa = new Google2FA();
        $user = $this->resolvePendingUser($pending);

        if (!$user || !$user->two_factor_secret) {
            return redirect()->route('login')->withErrors(['username' => 'Authentication error.']);
        }

        $otp = (string) $request->input('otp');
        $valid = $google2fa->verifyKey($user->two_factor_secret, $otp);

        if (!$valid) {
            return back()->withErrors(['otp' => 'Invalid 2FA verification code.']);
        }

        // Prevent TOTP replay within the 60-second window
        $replayKey = sprintf('2fa_otp_used_%s_%s_%s', $pending['type'], $pending['id'], $otp);
        if (!Cache::add($replayKey, true, 60)) {
            return back()->withErrors(['otp' => 'This 2FA code was already used. Please wait for the next 6-digit code.']);
        }

        // Lazily ensure recovery codes exist for legacy 2FA-enabled accounts
        if (empty($user->two_factor_recovery_codes)) {
            $user->generateRecoveryCodes(8);
        }

        return $this->completePendingLogin($request, $pending['type'], $user);
    }

    /**
     * Show the recovery code challenge page during login.
     */
    public function recoveryChallenge()
    {
        if (!session()->has('2fa.pending')) {
            return redirect()->route('login');
        }

        return view('auth.2fa_recovery_challenge');
    }

    /**
     * Verify a single-use recovery code during login.
     */
    public function verifyRecovery(Request $request)
    {
        $request->validate([
            'recovery_code' => ['required', 'string', 'min:8', 'max:20'],
        ]);

        $pending = session('2fa.pending');
        if (!$pending) {
            return redirect()->route('login');
        }

        $user = $this->resolvePendingUser($pending);
        if (!$user || !$user->two_factor_enabled) {
            return redirect()->route('login')->withErrors(['username' => 'Authentication error.']);
        }

        $code = (string) $request->input('recovery_code');
        if (!$user->consumeRecoveryCode($code)) {
            return back()->withErrors(['recovery_code' => 'Invalid or already-used recovery code.']);
        }

        $remaining = count($user->two_factor_recovery_codes);
        $response = $this->completePendingLogin($request, $pending['type'], $user);

        if ($PendingTypeIsStaff = ($pending['type'] === 'staff')) {
            ActivityLogger::log('Logged In via 2FA Recovery Code');
        }

        // If all recovery codes have been used up, automatically generate a fresh set
        // and redirect to the recovery codes page so the user is never locked out.
        if ($remaining === 0) {
            $user->generateRecoveryCodes(8);
            session()->flash('recovery_codes_just_generated', true);

            return redirect()->route('2fa.recovery-codes')->with(
                'success',
                'You used your final recovery code! A fresh set of 8 recovery codes has been automatically generated for you. Please save them now.'
            );
        }

        return $response;
    }

    public function settings(Request $request)
    {
        $auth = $this->getAuthenticatedUser();
        if (!$auth) {
            return redirect()->route('login');
        }

        $user = $auth['user'];

        if ($user->two_factor_enabled) {
            if (empty($user->two_factor_recovery_codes)) {
                $user->generateRecoveryCodes(8);
            }

            return view('auth.2fa_manage', [
                'enabled' => true,
                'recoveryCodesCount' => count($user->two_factor_recovery_codes),
            ]);
        }

        $google2fa = new Google2FA();
        $secret = $request->session()->get('2fa_setup_secret');
        if (!$secret) {
            $secret = $google2fa->generateSecretKey();
            $request->session()->put('2fa_setup_secret', $secret);
        }

        $username = $auth['type'] === 'admin' ? $user->admin_username : $user->username;
        $qrCodeUrl = $google2fa->getQRCodeUrl(
            'PME Portal',
            $username,
            $secret
        );

        $renderer = new ImageRenderer(
            new RendererStyle(250),
            new SvgImageBackEnd()
        );
        $writer = new Writer($renderer);
        $qrCodeSvg = $writer->writeString($qrCodeUrl);

        return view('auth.2fa_setup', [
            'secret' => $secret,
            'qrCode' => $qrCodeSvg,
            'qrCodeSvg' => $qrCodeSvg,
        ]);
    }

    public function enable(Request $request)
    {
        $request->validate([
            'otp' => ['required', 'string', 'digits:6'],
        ]);

        $auth = $this->getAuthenticatedUser();
        if (!$auth) {
            return redirect()->route('login');
        }

        $secret = $request->session()->get('2fa_setup_secret');
        if (!$secret) {
            return redirect()->route('2fa.settings')->withErrors(['otp' => 'Setup session expired. Please try again.']);
        }

        $google2fa = new Google2FA();
        $otp = (string) $request->input('otp');
        $valid = $google2fa->verifyKey($secret, $otp);

        if (!$valid) {
            return back()->withErrors(['otp' => 'Invalid verification code. Please scan the QR code and try again.']);
        }

        $user = $auth['user'];
        $userId = $auth['type'] === 'admin' ? $user->admin_id : $user->user_id;
        $replayKey = sprintf('2fa_otp_used_%s_%s_%s', $auth['type'], $userId, $otp);
        if (!Cache::add($replayKey, true, 60)) {
            return back()->withErrors(['otp' => 'This 2FA code was already used. Please wait for the next 6-digit code.']);
        }

        $user->two_factor_secret = $secret;
        $user->two_factor_enabled = 1;
        $user->generateRecoveryCodes(8);

        $request->session()->forget('2fa_setup_secret');
        $request->session()->flash('recovery_codes_just_generated', true);

        if ($auth['type'] === 'staff') {
            ActivityLogger::log('Enabled Two-Factor Authentication');
        }

        return redirect()->route('2fa.recovery-codes')->with(
            'success',
            'Two-Factor Authentication has been enabled! Save your backup recovery codes below in a safe place.'
        );
    }

    public function disable(Request $request)
    {
        $request->validate([
            'otp' => ['required', 'string', 'digits:6'],
        ]);

        $auth = $this->getAuthenticatedUser();
        if (!$auth) {
            return redirect()->route('login');
        }

        $user = $auth['user'];
        $google2fa = new Google2FA();
        $otp = (string) $request->input('otp');

        if (!$google2fa->verifyKey($user->two_factor_secret, $otp)) {
            return back()->withErrors(['otp' => 'Invalid OTP. Cannot disable 2FA.']);
        }

        $userId = $auth['type'] === 'admin' ? $user->admin_id : $user->user_id;
        $replayKey = sprintf('2fa_otp_used_%s_%s_%s', $auth['type'], $userId, $otp);
        if (!Cache::add($replayKey, true, 60)) {
            return back()->withErrors(['otp' => 'This 2FA code was already used. Please wait for the next 6-digit code.']);
        }

        $user->two_factor_secret = null;
        $user->two_factor_enabled = 0;
        $user->two_factor_recovery_codes = null;
        $user->save();

        if ($auth['type'] === 'staff') {
            ActivityLogger::log('Disabled Two-Factor Authentication');
        }

        return redirect()->route('2fa.settings')->with('success', 'Two-Factor Authentication has been disabled.');
    }

    /**
     * Display the authenticated user's 2FA recovery codes page.
     */
    public function recoveryCodes()
    {
        $auth = $this->getAuthenticatedUser();
        if (!$auth) {
            return redirect()->route('login');
        }

        $user = $auth['user'];

        if (!$user->two_factor_enabled) {
            return redirect()->route('2fa.settings')->with(
                'warning',
                'Please enable Two-Factor Authentication first to generate backup recovery codes.'
            );
        }

        $codes = $user->two_factor_recovery_codes;
        if (empty($codes)) {
            $codes = $user->generateRecoveryCodes(8);
            session()->flash('recovery_codes_just_generated', true);
        }

        $dashboardUrl = $auth['type'] === 'admin'
            ? route('admin.dashboard')
            : $this->auth->redirectPathFor($user);

        return view('auth.2fa_recovery_codes', [
            'codes' => $codes,
            'remainingCount' => count($codes),
            'dashboardUrl' => $dashboardUrl,
            'userType' => $auth['type'],
        ]);
    }

    /**
     * Regenerate (refresh) a fresh set of 8 recovery codes, invalidating old ones.
     */
    public function regenerateRecoveryCodes(Request $request)
    {
        $auth = $this->getAuthenticatedUser();
        if (!$auth) {
            return redirect()->route('login');
        }

        $user = $auth['user'];
        if (!$user->two_factor_enabled) {
            return redirect()->route('2fa.settings');
        }

        $user->generateRecoveryCodes(8);
        $request->session()->flash('recovery_codes_just_generated', true);

        if ($auth['type'] === 'staff') {
            ActivityLogger::log('Regenerated 2FA Recovery Codes');
        }

        return redirect()->route('2fa.recovery-codes')->with(
            'success',
            'New recovery codes have been generated! Your previous recovery codes are now invalid.'
        );
    }

    private function resolvePendingUser(array $pending): StaffUser|AdminCredential|null
    {
        if (($pending['type'] ?? '') === 'staff') {
            return StaffUser::find($pending['id'] ?? null);
        }
        if (($pending['type'] ?? '') === 'admin') {
            return AdminCredential::find($pending['id'] ?? null);
        }

        return null;
    }

    private function completePendingLogin(Request $request, string $type, StaffUser|AdminCredential $user)
    {
        session()->forget('2fa.pending');
        $request->session()->regenerate();

        if ($type === 'staff' && $user instanceof StaffUser) {
            $user->last_login_at = now();
            $user->last_login_ip = $request->ip();
            $user->session_id = session()->getId();
            $user->save();

            $role = strtoupper(trim((string) $user->username));
            $request->session()->put([
                'staff.logged_in' => true,
                'staff.user_id' => $user->user_id,
                'staff.username' => $role,
                'staff.display_name' => $user->displayName(),
                'staff.role' => $role,
                'username' => $role,
                'user_username' => $user->displayName(),
            ]);

            ActivityLogger::log('Logged In (2FA Verified)');

            return redirect($this->auth->redirectPathFor($user));
        }

        if ($type === 'admin' && $user instanceof AdminCredential) {
            $user->session_id = session()->getId();
            $user->save();

            $request->session()->put([
                'admin.logged_in' => true,
                'admin.id' => $user->admin_id,
                'admin.username' => $user->admin_username,
                'admin_logged_in' => true,
                'admin_username' => $user->admin_username,
            ]);

            return redirect()->route('admin.dashboard');
        }

        return redirect()->route('login');
    }

    private function getAuthenticatedUser(): ?array
    {
        if (session('staff.logged_in')) {
            $user = StaffUser::find(session('staff.user_id'));
            return $user ? ['type' => 'staff', 'user' => $user] : null;
        }
        if (session('admin.logged_in')) {
            $user = AdminCredential::find(session('admin.id'));
            return $user ? ['type' => 'admin', 'user' => $user] : null;
        }
        return null;
    }
}
