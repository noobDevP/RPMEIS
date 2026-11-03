@extends('layouts.portal')

@section('title', '2FA Settings')
@section('header-title', 'Two-Factor Authentication Settings')

@section('content')
<div class="card" style="max-width: 560px; margin: 0 auto;">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <span>Manage Two-Factor Authentication</span>
        <span style="background: #dcfce7; color: #166534; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 700;">
            <i class="fas fa-check-circle"></i> Active
        </span>
    </div>
    <div class="card-body">
        @if (session('success'))
            <div style="background:#d1fae5;color:#065f46;padding:12px;border-radius:8px;margin-bottom:16px;">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div style="background:#fee2e2;color:#991b1b;padding:12px;border-radius:8px;margin-bottom:16px;">
                {{ $errors->first() }}
            </div>
        @endif

        <p style="margin-bottom: 16px; color: #166534; font-weight: 600;">
            <i class="fas fa-shield-alt"></i> Two-Factor Authentication is currently ENABLED for your account.
        </p>

        {{-- Recovery Codes Section --}}
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; margin-bottom: 24px;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 8px;">
                <strong style="color: #1e293b; font-size: 15px;">
                    <i class="fas fa-key" style="color: #2563eb; margin-right: 6px;"></i> Backup Recovery Codes
                </strong>
                <span style="background: {{ ($recoveryCodesCount ?? 0) <= 2 ? '#fef2f2' : '#eff6ff' }}; color: {{ ($recoveryCodesCount ?? 0) <= 2 ? '#dc2626' : '#1d4ed8' }}; padding: 3px 10px; border-radius: 999px; font-size: 12px; font-weight: 600;">
                    {{ $recoveryCodesCount ?? 0 }} / 8 unused codes remaining
                </span>
            </div>
            <p style="margin: 0 0 14px 0; color: #64748b; font-size: 13.5px; line-height: 1.5;">
                Recovery codes let you sign in to your account if you lose access to your authenticator device. You can view, print, or regenerate your codes at any time.
            </p>
            <a href="{{ route('2fa.recovery-codes') }}" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 8px; text-decoration: none;">
                <i class="fas fa-eye"></i> View / Refresh Recovery Codes
            </a>
        </div>

        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 20px 0;">

        <p style="margin-bottom: 16px; color: #4b5563; font-size: 14px;">
            If you wish to disable 2FA, please enter a valid 6-digit code from your authenticator app below.
        </p>

        <form method="POST" action="{{ route('2fa.disable') }}">
            @csrf
            <div style="margin-bottom: 16px;">
                <label style="display:block; margin-bottom:6px; font-weight:600;">Authenticator Code (OTP)</label>
                <input type="text" name="otp" class="input-modern" placeholder="Enter 6-digit code" maxlength="6" required>
            </div>
            <button type="submit" class="btn btn-danger" style="width: 100%;" onclick="return confirm('Are you sure you want to disable Two-Factor Authentication?');">
                Disable Two-Factor Authentication
            </button>
        </form>
    </div>
</div>
@endsection
