@extends('layouts.portal')

@section('title', 'System Logs')

@section('sidebar-nav')
<div class="nav-group-title">Executive</div>
<a href="{{ route('executive.processed') }}" class="nav-item {{ request()->routeIs('executive.processed') ? 'active' : '' }}">
    <i class="fas fa-list-check"></i> Applicant Data
</a>
<a href="#" id="showAnalyticsBtn" class="nav-item">
    <i class="fas fa-chart-line"></i> Show Analytics
</a>
<a href="{{ route('executive.logs') }}" class="nav-item {{ request()->routeIs('executive.logs') ? 'active' : '' }}">
    <i class="fas fa-history"></i> System Logs
</a>
@endsection

@section('header-title', 'System Logs')

@section('content')
<div class="filter-tabs" style="display:flex; gap:10px; margin-bottom:20px;">
    <a href="{{ route('executive.logs') }}" class="btn {{ empty($filter) ? 'btn-primary' : 'btn-secondary' }}">
        All Logs
    </a>
    <a href="{{ route('executive.logs', ['filter' => 'logins']) }}" class="btn {{ $filter === 'logins' ? 'btn-primary' : 'btn-secondary' }}">
        Log ins
    </a>
    <a href="{{ route('executive.logs', ['filter' => 'medical']) }}" class="btn {{ $filter === 'medical' ? 'btn-primary' : 'btn-secondary' }}">
        Medical Actions
    </a>
    <a href="{{ route('executive.logs', ['filter' => 'downloads']) }}" class="btn {{ $filter === 'downloads' ? 'btn-primary' : 'btn-secondary' }}">
        Downloads
    </a>
</div>

{{-- System Activity Logs --}}
<div class="card" style="margin-top: 0; margin-bottom: 24px;">
    <div class="card-header">
        <span>System Activity Logs</span>
    </div>
    <div class="card-body" style="padding:0;">
        <div class="table-responsive" style="max-height:400px;overflow-y:auto;overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;min-width:800px;">
                <thead style="background:#f3f4f6;position:sticky;top:0;z-index:1;">
                    <tr>
                        <th style="padding:12px 16px;text-align:center;font-weight:600;color:#374151;border-bottom:2px solid #e5e7eb;">Date & Time</th>
                        <th style="padding:12px 16px;text-align:center;font-weight:600;color:#374151;border-bottom:2px solid #e5e7eb;">User</th>
                        <th style="padding:12px 16px;text-align:center;font-weight:600;color:#374151;border-bottom:2px solid #e5e7eb;">Role</th>
                        <th style="padding:12px 16px;text-align:center;font-weight:600;color:#374151;border-bottom:2px solid #e5e7eb;">Action</th>
                        <th style="padding:12px 16px;text-align:center;font-weight:600;color:#374151;border-bottom:2px solid #e5e7eb;">Applicant No</th>
                        <th style="padding:12px 16px;text-align:center;font-weight:600;color:#374151;border-bottom:2px solid #e5e7eb;">IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($activityLogs ?? [] as $log)
                        <tr style="border-bottom:1px solid #e5e7eb;">
                            <td style="padding:12px 16px;text-align:center;">{{ $log->created_at->format('Y-m-d h:i A') }}</td>
                            <td style="padding:12px 16px;text-align:center;font-weight:500;">{{ $log->user_username ?? 'System' }}</td>
                            <td style="padding:12px 16px;text-align:center;">{{ $log->role ?? '-' }}</td>
                            <td style="padding:12px 16px;text-align:center;"><span class="badge" style="background:#e0f2fe;color:#0369a1;padding:4px 8px;border-radius:4px;font-size:0.85rem;font-weight:500;">{{ $log->action }}</span></td>
                            <td style="padding:12px 16px;text-align:center;">{{ $log->patient_no ?? '-' }}</td>
                            <td style="padding:12px 16px;text-align:center;font-family:monospace;color:#4b5563;">{{ $log->ip_address ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding:20px;text-align:center;color:#6b7280;">No activity logs found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="mt-4">
    {{ $activityLogs->links() }}
</div>
@endsection
