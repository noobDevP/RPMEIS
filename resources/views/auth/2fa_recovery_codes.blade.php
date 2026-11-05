@extends('layouts.portal')

@section('title', '2FA Recovery Codes')
@section('header-title', 'Two-Factor Backup Recovery Codes')

@section('content')
<div class="card" style="max-width: 640px; margin: 0 auto;">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <span><i class="fas fa-key" style="margin-right: 8px; color: #2563eb;"></i> Backup Recovery Codes</span>
        <span style="background: {{ $remainingCount <= 2 ? '#fee2e2' : '#dcfce7' }}; color: {{ $remainingCount <= 2 ? '#991b1b' : '#166534' }}; padding: 4px 12px; border-radius: 999px; font-size: 12.5px; font-weight: 700;">
            {{ $remainingCount }} of 8 Codes Remaining
        </span>
    </div>

    <div class="card-body">
        @if (session('success'))
            <div style="background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; padding: 12px 16px; border-radius: 8px; margin-bottom: 18px; font-weight: 500;">
                <i class="fas fa-check-circle" style="margin-right: 6px;"></i> {{ session('success') }}
            </div>
        @endif

        @if (session('warning'))
            <div style="background: #fef3c7; color: #92400e; border: 1px solid #fde68a; padding: 12px 16px; border-radius: 8px; margin-bottom: 18px; font-weight: 500;">
                <i class="fas fa-exclamation-triangle" style="margin-right: 6px;"></i> {{ session('warning') }}
            </div>
        @endif

        <div style="background: #fffbeb; border: 1px solid #fde68a; color: #92400e; padding: 14px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 13.5px; line-height: 1.55;">
            <strong><i class="fas fa-shield-alt"></i> Keep these codes in a safe place!</strong><br>
            If you lose your phone or cannot access your authenticator app, you can input any of these single-use recovery codes on the 2FA sign-in screen. Each code works <strong>once</strong>. When all 8 codes are used up (or whenever you click <em>Generate New Codes</em> below), a fresh set of 8 codes is generated automatically.
        </div>

        <div id="recoveryCodesGrid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; background: #0f172a; color: #f8fafc; padding: 20px; border-radius: 10px; margin-bottom: 20px; border: 1px solid #334155;">
            @forelse ($codes as $index => $code)
                <div style="display: flex; align-items: center; justify-content: space-between; background: rgba(255, 255, 255, 0.06); padding: 10px 14px; border-radius: 6px; border: 1px solid rgba(255, 255, 255, 0.1); font-family: 'Courier New', Courier, monospace; font-size: 16px; font-weight: 700; letter-spacing: 2px;">
                    <span style="color: #64748b; font-size: 12px; font-weight: 600; letter-spacing: 0;">#{{ $index + 1 }}</span>
                    <span class="recovery-code-item">{{ $code }}</span>
                </div>
            @empty
                <div style="grid-column: span 2; text-align: center; padding: 16px; color: #fca5a5;">
                    All recovery codes have been used. Click "Generate New Codes" below to refresh your codes.
                </div>
            @endforelse
        </div>

        {{-- Action Buttons: Copy, Download, Print --}}
        <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 24px;">
            <button type="button" class="btn btn-primary" id="copyCodesBtn" style="flex: 1; min-width: 140px;">
                <i class="fas fa-copy"></i> <span id="copyCodesBtnText">Copy Codes</span>
            </button>
            <button type="button" class="btn btn-outline" id="downloadCodesBtn" style="flex: 1; min-width: 140px; border: 1px solid #cbd5e1; color: #1e293b; background: #fff;">
                <i class="fas fa-download"></i> Download .TXT
            </button>
            <button type="button" class="btn btn-outline" id="printCodesBtn" style="flex: 1; min-width: 140px; border: 1px solid #cbd5e1; color: #1e293b; background: #fff;">
                <i class="fas fa-print"></i> Print Codes
            </button>
        </div>

        <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 20px 0;">

        {{-- Regenerate / Refresh Codes & Navigation --}}
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <form method="POST" action="{{ route('2fa.recovery-codes.regenerate') }}" id="regenCodesForm" style="margin: 0;">
                @csrf
                <button type="submit" class="btn btn-danger" style="background: #dc2626; color: #fff;">
                    <i class="fas fa-sync-alt"></i> Generate New Codes (Refresh)
                </button>
            </form>

            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <a href="{{ route('2fa.settings') }}" class="btn btn-outline" style="text-decoration: none; border: 1px solid #cbd5e1; color: #334155; background: #f8fafc;">
                    <i class="fas fa-cog"></i> 2FA Settings
                </a>
                <a href="{{ $dashboardUrl ?? url('/') }}" class="btn btn-success" style="text-decoration: none;">
                    <i class="fas fa-arrow-right"></i> Go to Dashboard
                </a>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="{{ asset('js/2fa-recovery.js') }}" defer></script>
@endpush
@endsection
