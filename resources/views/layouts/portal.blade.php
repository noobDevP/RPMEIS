<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'RPMEIS Portal')</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer">
    @stack('styles')
    <style>
        :root {
            --bg-page: linear-gradient(145deg, #f6f9fc 0%, #e9f0f5 100%);
            --glass-bg: rgba(230, 236, 242, 0.85);
            --glass-border: 1px solid rgba(200, 210, 220, 0.5);
            
            --primary-grad: linear-gradient(135deg, #fcd34d, #f59e0b);
            --primary-color: #f59e0b;
            --primary-hover: #fbbf24;
            
            --text-main: #1e2b36;
            --text-muted: #62778b;
            --font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            
            --danger: #ef4444;
            --success: #10b981;
            --warning: #f59e0b;
            --info: #3b82f6;
            
            --border-color: rgba(180, 195, 210, 0.3);
            --radius: 16px;
            --white: #ffffff;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: var(--font-family); 
            background: var(--bg-page); 
            color: var(--text-main); 
            min-height: 100vh; 
            display: flex; 
            flex-direction: column; 
        }

        /* Top Navigation */
        .top-nav { 
            height: 72px; 
            background: var(--primary-grad); 
            border-bottom: none; 
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3); 
            color: #1e2b36; 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
            padding: 0 1.5rem; 
            position: sticky; 
            top: 0; 
            z-index: 50; 
        }
        .brand { display: flex; align-items: center; gap: 10px; }
        .brand-name { font-size: 1.75rem; font-weight: 800; color: #1e2b36; letter-spacing: -0.01em; line-height: 1; display: block; }
        .hamburger { display: none; background: none; border: none; color: #1e2b36; font-size: 1.5rem; cursor: pointer; margin-right: 15px; }

        .profile-trigger {
            display: flex; 
            align-items: center; 
            gap: 10px; 
            background: rgba(255, 255, 255, 0.3); 
            border: 1px solid rgba(255, 255, 255, 0.4); 
            color: #1e2b36; 
            padding: 6px 12px 6px 6px; 
            border-radius: 40px;
        }

        .profile-avatar {
            width: 32px; 
            height: 32px; 
            border-radius: 50%; 
            background: #1e2b36; 
            color: #fcd34d; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-size: 0.85rem; 
            font-weight: 700;
        }

        /* Main Layout */
        .app-body { 
            display: flex; 
            flex: 1; 
        }
        
        .sidebar { 
            width: 280px; 
            background: var(--glass-bg); 
            backdrop-filter: blur(20px); 
            border-right: var(--glass-border); 
            flex-shrink: 0; 
            display: flex; 
            flex-direction: column; 
            justify-content: space-between; 
            transition: transform 0.3s ease; 
            z-index: 40; 
            position: sticky;
            top: 72px;
            height: calc(100vh - 72px);
        }
        
        .nav-group-title { 
            padding: 1.2rem 1rem 0.4rem 1.5rem; 
            font-size: 0.75rem; 
            text-transform: uppercase; 
            color: #7a8b9a; 
            font-weight: 600; 
            letter-spacing: 0.05em;
        }
        
        .nav-links { 
            list-style: none; 
            padding: 0.5rem 1rem; 
            display: flex; 
            flex-direction: column; 
            gap: 0.4rem; 
            overflow-y: auto; 
        }
        
        .nav-item { 
            display: flex; 
            align-items: center; 
            gap: 12px; 
            padding: 0.8rem 1rem; 
            text-decoration: none; 
            color: var(--text-muted); 
            font-weight: 500; 
            border-radius: 12px; 
            transition: all 0.2s; 
            border: none;
            background: transparent;
            font-family: inherit;
            font-size: 0.95rem;
            cursor: pointer;
            text-align: left;
            width: 100%;
        }
        
        .nav-item i { width: 20px; text-align: center; }
        
        .nav-item:hover { 
            background: rgba(200, 210, 220, 0.4); 
            color: var(--text-main); 
        }
        
        .nav-item.active { 
            background: var(--primary-grad); 
            color: #1e2b36; 
            box-shadow: 0 4px 14px rgba(245, 158, 11, 0.2); 
        }

        /* Modal Styles */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal-overlay.active { display: flex; }
        
        .modal-close {
            cursor: pointer;
            color: var(--text-muted);
            font-size: 1.5rem;
            line-height: 1;
        }

        /* Content Area */
        .content-area { 
            flex: 1; 
            padding: 2rem; 
            position: relative;
        }
        
        .container { 
            max-width: 1400px; 
            margin: 0 auto; 
            height: 100%; 
            display: flex; 
            flex-direction: column; 
            gap: 1.5rem; 
        }

        /* Page Header */
        .page-header { 
            background: var(--primary-grad); 
            border-radius: 24px; 
            padding: 1.8rem 2.5rem; 
            color: #1e2b36; 
            box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.3); 
            flex-shrink: 0; 
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .page-header h1 { 
            font-size: 1.8rem; 
            font-weight: 600; 
            margin-bottom: 0.4rem; 
            color: #1e2b36;
            letter-spacing: -0.02em;
        }
        
        .page-header p { 
            color: rgba(30, 43, 54, 0.85); 
            font-size: 1rem; 
            margin: 0; 
            font-weight: 500;
        }

        /* Cards & Glassmorphism */
        .card { 
            background: var(--glass-bg); 
            backdrop-filter: blur(10px); 
            -webkit-backdrop-filter: blur(10px);
            border: var(--glass-border); 
            border-radius: 20px; 
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05); 
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        
        .card-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            padding: 1.5rem 1.8rem;
            border-bottom: var(--glass-border); 
            background: rgba(220, 230, 240, 0.5);
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .card-header h2, .card-header span { 
            font-size: 1.2rem; 
            font-weight: 600; 
            color: var(--text-main); 
            display: flex; 
            align-items: center; 
            gap: 10px; 
        }
        
        .card-body {
            padding: 1.5rem 1.8rem;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 0.6rem 1.2rem;
            border-radius: 40px;
            font-weight: 500;
            font-size: 0.9rem;
            font-family: inherit;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s ease;
            border: none;
        }
        
        .btn-primary {
            background: var(--primary-grad);
            color: white;
            box-shadow: 0 4px 12px rgba(217, 119, 6, 0.3);
        }
        
        .btn-primary:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(217, 119, 6, 0.4);
        }

        .btn-success { background: #dcfce7; color: #166534; border: 1px solid #a7f3d0; }
        .btn-success:hover { background: #bbf7d0; }
        
        .btn-danger { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .btn-danger:hover { background: #fecaca; }

        .btn-outline {
            background: rgba(255, 255, 255, 0.6);
            color: var(--text-main);
            border: 1px solid rgba(180, 195, 210, 0.4);
        }
        .btn-outline:hover { background: rgba(255, 255, 255, 0.8); }
        .btn-sm { padding: 0.4rem 0.8rem; font-size: 0.8rem; }

        /* Forms & Inputs */
        .input-modern, .select-modern {
            width: 100%;
            padding: 0.8rem 1.2rem;
            border: 1px solid rgba(180, 195, 210, 0.6);
            border-radius: 40px;
            font-size: 0.95rem;
            color: var(--text-main);
            background: rgba(255, 255, 255, 0.6);
            transition: all 0.2s ease;
            outline: none;
            font-family: inherit;
        }
        
        .input-modern:focus, .select-modern:focus {
            border-color: var(--primary-color);
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(217, 119, 6, 0.1);
        }

        .select-modern option {
            background: white;
            color: var(--text-main);
        }

        /* Tables */
        table { width: 100%; border-collapse: collapse; }
        th { 
            text-align: left; 
            padding: 1rem; 
            border-bottom: 2px solid rgba(180, 195, 210, 0.4); 
            font-size: 0.8rem; 
            color: var(--text-muted); 
            font-weight: 600; 
            text-transform: uppercase; 
            letter-spacing: 0.05em;
        }
        td { 
            padding: 1rem; 
            border-bottom: 1px solid rgba(180, 195, 210, 0.2); 
            color: var(--text-main); 
            font-size: 0.95rem; 
        }
        tr:hover td { background: rgba(255, 255, 255, 0.5); }

        .status-badge { 
            padding: 0.3rem 0.8rem; 
            border-radius: 40px; 
            font-size: 0.75rem; 
            font-weight: 600; 
            display: inline-flex; 
            align-items: center;
            justify-content: center;
        }
        .status-active { background: #dcfce7; color: #166534; }
        .status-inactive { background: #fee2e2; color: #991b1b; }

        /* Modals */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(8px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        
        .modal-overlay.active { display: flex; }
        .modal-close { cursor: pointer; font-size: 1.5rem; color: var(--text-muted); transition: color 0.2s; }
        .modal-close:hover { color: var(--text-main); }

        /* Mobile */
        @media (max-width: 768px) {
            .hamburger { display: block; }
            .sidebar { 
                position: fixed; 
                top: 72px; 
                left: 0; 
                bottom: 0; 
                transform: translateX(-100%); 
            }
            .sidebar.open { transform: translateX(0); }
            .content-area { padding: 1rem; }
            .page-header { padding: 1.5rem; }
            .card-header, .card-body { padding: 1.2rem; }
        }

        /* Scrollbars */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: rgba(0, 0, 0, 0.02); }
        ::-webkit-scrollbar-thumb { background: rgba(0, 0, 0, 0.1); border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(0, 0, 0, 0.2); }
    
    /* Pagination Styles */
    .pagination {
        display: flex;
        padding-left: 0;
        list-style: none;
        gap: 4px;
        margin-top: 1rem;
        margin-bottom: 1rem;
        align-items: center;
        justify-content: center;
    }
    .page-item .page-link {
        position: relative;
        display: block;
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
        color: #3b82f6;
        text-decoration: none;
        background-color: #fff;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        transition: all 0.2s;
    }
    .page-item .page-link:hover {
        background-color: #f3f4f6;
        color: #2563eb;
    }
    .page-item.active .page-link {
        z-index: 3;
        color: #fff;
        background-color: #3b82f6;
        border-color: #3b82f6;
    }
    .page-item.disabled .page-link {
        color: #9ca3af;
        pointer-events: none;
        background-color: #f9fafb;
        border-color: #d1d5db;
    }
    .pagination p {
        display: none;
    }
    .hidden {
        display: none !important;
    }

    
    /* Pagination Layout Utilities */
    .d-none { display: none !important; }
    .d-flex { display: flex !important; }
    .justify-content-between { justify-content: space-between !important; }
    .align-items-center { align-items: center !important; }
    
    @media (min-width: 576px) {
        .d-sm-none { display: none !important; }
        .d-sm-flex { display: flex !important; }
        .align-items-sm-center { align-items: center !important; }
        .justify-content-sm-between { justify-content: space-between !important; }
        .flex-sm-fill { flex: 1 1 auto !important; }
    }
    
    .text-muted { color: #6b7280; font-size: 0.875rem; }

    </style>
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
                        <a href="#" class="nav-item" onclick="openLayoutPasswordModal(); return false;">
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
    <div id="layoutPasswordModal" class="modal-overlay" onclick="if(event.target===this)closeLayoutPasswordModal()">
        <div class="card" style="width:90%;max-width:420px;margin:auto;" onclick="event.stopPropagation()">
            <div class="card-header">
                <span>Account Settings</span>
                <span class="modal-close" onclick="closeLayoutPasswordModal()">&times;</span>
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
                    <button type="button" class="btn btn-primary" onclick="submitLayoutPassword()" style="width:100%; border-radius:40px; padding:0.8rem;">Update Settings</button>

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

    <script>
        // Sidebar Toggle
        const hamburgerBtn = document.getElementById('hamburgerBtn');
        if (hamburgerBtn) {
            hamburgerBtn.addEventListener('click', () => {
                document.getElementById('sidebar').classList.toggle('open');
            });
        }

        // Layout Password logic
        function openLayoutPasswordModal() { 
            document.getElementById('layoutPasswordModal').classList.add('active'); 
        }
        function closeLayoutPasswordModal() { 
            document.getElementById('layoutPasswordModal').classList.remove('active'); 
        }
        
        function submitLayoutPassword() {
            const p = document.getElementById('layoutPwNew').value.trim();
            const c = document.getElementById('layoutPwConfirm').value.trim();
            const msg = document.getElementById('layoutPwMsg');
            
            if (p !== '' && p !== c) { 
                msg.style.display = 'block'; 
                msg.style.background = '#fee2e2'; 
                msg.style.color = '#991b1b'; 
                msg.style.border = '1px solid rgba(239, 68, 68, 0.3)';
                msg.textContent = 'Passwords do not match!'; 
                return; 
            }
            
            const fd = new FormData();
            fd.append('new_password', p);
            fd.append('user_username', document.getElementById('layoutDisplayName') ? document.getElementById('layoutDisplayName').value : '');
            
            if (document.getElementById('layoutRank')) fd.append('rank', document.getElementById('layoutRank').value);
            if (document.getElementById('layoutAfpos')) fd.append('afpos', document.getElementById('layoutAfpos').value);
            if (document.getElementById('layoutStatus')) fd.append('status', document.getElementById('layoutStatus').value);
            if (document.getElementById('layoutPosition')) fd.append('position', document.getElementById('layoutPosition').value);
            
            if (document.getElementById('layoutCredName')) fd.append('credential_name', document.getElementById('layoutCredName').value);
            if (document.getElementById('layoutCredTitle')) fd.append('credential_title', document.getElementById('layoutCredTitle').value);
            if (document.getElementById('layoutCredLicense')) fd.append('credential_license', document.getElementById('layoutCredLicense').value);
                        if (document.getElementById('layoutCredStatus')) fd.append('credential_status', document.getElementById('layoutCredStatus').value);
            
            if (document.getElementById('layoutESignature') && document.getElementById('layoutESignature').files[0]) {
                fd.append('e_signature', document.getElementById('layoutESignature').files[0]);
            }
            
            fetch('{{ route('password.change') }}', { 
                method: 'POST', 
                body: fd, 
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } 
            })
            .then(r => r.json())
            .then(d => {
                msg.style.display = 'block';
                if (d.status === 'success' || d.success) { 
                    msg.style.background = '#dcfce7'; 
                    msg.style.color = '#166534'; 
                    msg.style.border = '1px solid #a7f3d0';
                    msg.textContent = 'Password updated successfully!'; 
                    setTimeout(() => closeLayoutPasswordModal(), 2000); 
                } else { 
                    msg.style.background = '#fee2e2'; 
                    msg.style.color = '#991b1b'; 
                    msg.textContent = d.message || 'Error occurred'; 
                }
            }).catch(() => { 
                msg.style.display = 'block'; 
                msg.style.background = '#fee2e2'; 
                msg.style.color = '#991b1b'; 
                msg.textContent = 'Network error'; 
            });
        }
    </script>
    @stack('scripts')
</body>
</html>
