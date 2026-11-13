<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'RPMEIS Portal')</title>
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    @stack('styles')
    <link rel="stylesheet" href="{{ asset('css/portal-layout.css') }}?v={{ @filemtime(public_path('css/portal-layout.css')) ?: 1 }}">
</head>
<body>
    <header class="top-nav">
        <div style="display:flex; align-items:center;">
            <button class="hamburger" id="hamburgerBtn"><i class="fas fa-bars"></i></button>
            <div class="brand">
                <div style="background: white; border-radius: 50%; padding: 4px; display: flex; align-items: center; justify-content: center; width: 44px; height: 44px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <img src="{{ asset('images/Visayas.png') }}" alt="RPMEIS Logo" style="height: 36px; width: auto; object-fit: contain;">
                </div>
                <div style="display: flex; flex-direction: column;">
                    <span class="brand-name">RPMEIS</span>
                    @php
                        $displayName = session('staff.user_username') 
                            ?: session('staff.username') 
                            ?: session('admin.username', 'Guest');
                    @endphp
                    <span style="font-size: 0.8rem; font-weight: 700; color: rgba(30,43,54,0.7); text-transform: uppercase; margin-top: -2px; letter-spacing: 0.05em;">{{ $displayName }}</span>
                </div>
            </div>
        </div>
        <div class="nav-right">
            {{-- Empty right section --}}
        </div>
    </header>

    <div class="app-body">
        <aside class="sidebar" id="sidebar">
            <div>
                <ul class="nav-links" style="margin-top: 1rem;">
                    @yield('sidebar-nav')
                </ul>
            </div>
            
            <div style="padding: 1rem;">
                <ul class="nav-links" style="padding: 0;">
                    <li>
                        <a href="#" id="accountSettingsNavBtn" data-action="open-layout-password-modal" class="nav-item" onclick="openLayoutPasswordModal(); return false;">
                            <i class="fas fa-key"></i> Account Settings
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('2fa.settings') }}" class="nav-item">
                            <i class="fas fa-shield-alt"></i> Two-Factor Auth
                        </a>
                    </li>
                    <li>
                        <form action="{{ route('logout') }}" method="POST" style="margin: 0;">
                            @csrf
                            <button type="submit" class="nav-item" style="color: #ef4444;">
                                <i class="fas fa-sign-out-alt"></i> Sign Out
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </aside>

        <main class="content-area">
            <div class="container">
                @hasSection('header-title')
                <div class="page-header">
                    <div>
                        <h1>@yield('header-title')</h1>
                        <p>@yield('header-subtitle', 'System-wide overview and data management')</p>
                    </div>
                    @hasSection('header-actions')
                    <div style="display:flex; gap:10px;">
                        @yield('header-actions')
                    </div>
                    @endif
                </div>
                @endif
                
                @yield('content')
            </div>
        </main>
    </div>

    @php
        $staffUser = null;
        if (session('staff.user_id')) {
            $staffUser = \App\Models\StaffUser::find(session('staff.user_id'));
        } elseif (session()->has('staff.role')) {
            $staffUser = \App\Models\StaffUser::where('username', session('staff.role'))->first();
        }
    @endphp

    {{-- Password / Settings Modal --}}
    <div id="layoutPasswordModal" class="modal-overlay" data-change-password-url="{{ route('password.change') }}" onclick="if(event.target===this)closeLayoutPasswordModal()">
        <div class="card" style="width:90%;max-width:420px;margin:auto;" onclick="event.stopPropagation()">
            <div class="card-header">
                <span>Account Settings</span>
                <span class="modal-close" data-action="close-layout-password-modal" onclick="closeLayoutPasswordModal()">&times;</span>
            </div>
            <div class="card-body" style="max-height: 70vh; overflow-y: auto;">
                <form id="layoutPwForm" onsubmit="return false;">
                    @if(strtoupper(session('staff.role', '')) !== 'ADMIN')
                    <div style="margin-bottom:12px;">
                        <label style="display:block;margin-bottom:5px;font-weight:500;color:var(--text-muted);font-size:0.85rem;">Full Name (Printed on Signatures)</label>
                        <input type="text" id="layoutDisplayName" class="input-modern" value="{{ session('staff.display_name') }}" placeholder="e.g. JUAN G DELA CRUZ">
                    </div>
                    @endif
                    <div style="margin-bottom:12px;">
                        <label style="display:block;margin-bottom:5px;font-weight:500;color:var(--text-muted);font-size:0.85rem;">New Password (leave blank to keep current)</label>
                        <input type="password" id="layoutPwNew" class="input-modern" placeholder="••••••••">
                    </div>
                    <div style="margin-bottom:16px;">
                        <label style="display:block;margin-bottom:5px;font-weight:500;color:var(--text-muted);font-size:0.85rem;">Confirm Password</label>
                        <input type="password" id="layoutPwConfirm" class="input-modern" placeholder="••••••••">
                    </div>

                    @if(str_contains(strtoupper(session('staff.role', '')), 'DOCTOR') || strtoupper(session('staff.role', '')) === 'EXECUTIVE')
                    <div style="margin: 20px 0; border-top: 1px solid rgba(180,195,210,0.3); padding-top: 15px;">
                        <h4 style="margin-bottom: 12px; font-size: 0.9rem; color: var(--text-main);">Signature / Credential Info</h4>
                        <div style="margin-bottom:12px;">
                            <label style="display:block;margin-bottom:5px;font-weight:500;color:var(--text-muted);font-size:0.85rem;">Rank</label>
                            <select id="layoutRank" class="select-modern" style="width: 100%;">
                                <option value="">- None -</option>
                                @foreach(['PVT', 'PFC', 'CPL', 'SGT', 'SSG', 'TSG', 'MSG', '2LT', '1LT', 'CPT', 'MAJ', 'LTC', 'COL'] as $r)
                                    <option value="{{ $r }}" {{ ($staffUser->rank ?? '') === $r ? 'selected' : '' }}>{{ $r }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div style="margin-bottom:12px;">
                            <label style="display:block;margin-bottom:5px;font-weight:500;color:var(--text-muted);font-size:0.85rem;">AFPOS</label>
                            <input type="text" id="layoutAfpos" class="input-modern" value="{{ $staffUser->afpos ?? '' }}" placeholder="e.g. MC">
                        </div>
                        <div style="margin-bottom:12px;">
                            <label style="display:block;margin-bottom:5px;font-weight:500;color:var(--text-muted);font-size:0.85rem;">Status</label>
                            <input type="text" id="layoutStatus" class="input-modern" value="{{ $staffUser->status ?? '' }}" placeholder="e.g. GSC">
                        </div>
                        <div style="margin-bottom:12px;">
                            <label style="display:block;margin-bottom:5px;font-weight:500;color:var(--text-muted);font-size:0.85rem;">Position</label>
                            <input type="text" id="layoutPosition" class="input-modern" value="{{ $staffUser->position ?? '' }}" placeholder="e.g. CHAIRMAN">
                        </div>
                        @if(str_contains(strtoupper(session('staff.role', '')), 'DOCTOR'))
                        <div style="margin-bottom:12px;">
                            <label style="display:block;margin-bottom:5px;font-weight:500;color:var(--text-muted);font-size:0.85rem;">E-Signature (Transparent PNG)</label>
                            @if(!empty($staffUser->e_signature_path))
                                <div style="margin-bottom:8px;">
                                    <img src="{{ asset('storage/' . $staffUser->e_signature_path) }}" alt="Current Signature" style="max-height: 50px; background: #f0f0f0; border: 1px dashed #ccc; padding: 4px;">
                                </div>
                            @endif
                            <input type="file" id="layoutESignature" class="input-modern" accept=".png">
                        </div>
                        @endif
                    </div>
                    @endif

                    <div id="layoutPwMsg" style="display:none; padding:10px; border-radius:12px; margin-bottom:16px; font-size:0.85rem; text-align:center;"></div>
                    <button type="button" id="submitLayoutPasswordBtn" class="btn btn-primary" onclick="submitLayoutPassword()" style="width:100%; border-radius:40px; padding:0.8rem;">Update Settings</button>

                    <div style="margin-top: 20px; border-top: 1px solid rgba(180,195,210,0.35); padding-top: 16px;">
                        <h4 style="margin: 0 0 8px 0; font-size: 0.9rem; color: var(--text-main);">
                            <i class="fas fa-shield-alt" style="color: #2563eb; margin-right: 6px;"></i> Two-Factor Authentication
                        </h4>
                        <p style="margin: 0 0 12px 0; font-size: 0.8rem; color: var(--text-muted); line-height: 1.45;">
                            View or refresh your backup recovery codes in case you do not have access to your authenticator app.
                        </p>
                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            <a href="{{ route('2fa.recovery-codes') }}" class="btn btn-outline" style="width: 100%; border-radius: 40px; padding: 0.65rem; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 8px; border: 1px solid #2563eb; color: #2563eb; font-weight: 600;">
                                <i class="fas fa-key"></i> View 2FA Recovery Codes
                            </a>
                            <a href="{{ route('2fa.settings') }}" class="btn btn-outline" style="width: 100%; border-radius: 40px; padding: 0.6rem; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 8px; font-size: 0.85rem;">
                                <i class="fas fa-cog"></i> Manage 2FA Authenticator
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @include('partials.analytics-chart-modal')

    <script src="{{ asset('js/portal-layout.js') }}?v={{ @filemtime(public_path('js/portal-layout.js')) ?: 1 }}" defer></script>
    @stack('scripts')
</body>
</html>
