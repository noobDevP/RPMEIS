@extends('layouts.portal')

@section('title', 'Submission Logs - ARO Visayas')

@section('sidebar-nav')
<div class="nav-group-title">Registrar Operations</div>
<a href="{{ route('registrar.index') }}" class="nav-item {{ request()->is('registrar') && !request()->is('registrar/*') ? 'active' : '' }}">
    <i class="fas fa-qrcode"></i> QR Scanner
</a>
<a href="{{ route('registrar.activate') }}" class="nav-item {{ request()->is('registrar/activate') ? 'active' : '' }}">
    <i class="fas fa-user-check"></i> Activate Account
</a>
<a href="{{ route('registrar.records') }}" class="nav-item {{ request()->is('registrar/records') ? 'active' : '' }}">
    <i class="fas fa-users"></i> Active Patient Records
</a>
<a href="#" id="showAnalyticsBtn" class="nav-item">
    <i class="fas fa-chart-line"></i> Show Analytics
</a>
<a href="{{ route('registrar.archives') }}" class="nav-item {{ request()->is('registrar/archives') ? 'active' : '' }}">
    <i class="fas fa-archive"></i> Archives
</a>
@endsection

@section('header-title', 'Submission Logs')

@section('header-actions')
@endsection

@push('styles')
<style>
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0; left: 0;
        width: 100%; height: 100%;
        background-color: rgba(255, 255, 255, 0.4);
        backdrop-filter: blur(8px);
        z-index: 1000;
    }
    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    .filter-group label {
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }
</style>
@endpush

@section('content')
<div class="card">
    <div class="card-header">
        <a href="{{ route('registrar.index') }}" class="btn btn-outline btn-sm">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>
    <div class="card-body">
        {{-- Filter Controls --}}
        <div style="display:flex; gap:16px; flex-wrap:wrap; margin-bottom:20px;">
            <div class="filter-group">
                <label>Applicant No.</label>
                <select id="patientSelect" class="select-modern" style="width:auto; min-width:180px;">
                    <option value="">Select Applicant No.</option>
                    @foreach ($patients as $p)
                        <option value="{{ $p->patient_no_logs }}"
                                data-first="{{ $p->firstName_logs }}"
                                data-last="{{ $p->lastName_logs }}">
                            {{ $p->patient_no_logs }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="filter-group">
                <label>First Name</label>
                <input type="text" id="firstNameInput" class="input-modern" disabled
                       style="width:auto; min-width:160px; background:var(--bg-color);">
            </div>

            <div class="filter-group">
                <label>Last Name</label>
                <input type="text" id="lastNameInput" class="input-modern" disabled
                       style="width:auto; min-width:160px; background:var(--bg-color);">
            </div>

            <div class="filter-group">
                <label>Section Logs</label>
                <select id="sectionLogs" class="select-modern" style="width:auto; min-width:200px;">
                    <option value="">Select Section</option>
                    <option value="dental">DENTAL LOGS</option>
                    <option value="ophtha">OPHTHA/EENT LOGS</option>
                    <option value="ecg_xray">ECG/X-RAY LOGS</option>
                    <option value="lab_drugtest">LABORATORY/DRUGTEST LOGS</option>
                    <option value="np">NP(NEURO) LOGS</option>
                    <option value="gpe">GPE LOGS</option>
                </select>
            </div>
        </div>

        {{-- Logs Table --}}
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Approval Status</th>
                        <th>Timestamp</th>
                        <th>Name of User</th>
                    </tr>
                </thead>
                <tbody id="logsTableBody">
                    <tr>
                        <td colspan="3" style="text-align:center; padding:20px; font-weight:600; color:var(--text-muted);">
                            No records found
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    document.getElementById('patientSelect').addEventListener('change', function() {
        const opt = this.options[this.selectedIndex];
        document.getElementById('firstNameInput').value = opt.dataset.first || '';
        document.getElementById('lastNameInput').value = opt.dataset.last || '';
    });

    document.getElementById('sectionLogs').addEventListener('change', function() {
        const patientNo = document.getElementById('patientSelect').value;
        const section = this.value;
        if (!patientNo || !section) return;

        fetch(`{{ route('api.logs.registrar') }}?patient_no=${patientNo}&section=${section}`)
            .then(r => r.json())
            .then(data => {
                const tbody = document.getElementById('logsTableBody');
                if (data.length) {
                    tbody.innerHTML = data.map(row =>
                        `<tr>
                            <td><span class="badge badge-neutral">${row.approval}</span></td>
                            <td>${row.timestamp}</td>
                            <td>${row.user_username}</td>
                        </tr>`
                    ).join('');
                } else {
                    tbody.innerHTML = '<tr><td colspan="3" style="text-align:center; padding:20px; font-weight:600; color:var(--text-muted);">No records found</td></tr>';
                }
            });
    });

</script>
@endpush
