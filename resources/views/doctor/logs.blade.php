@extends('layouts.portal')

@section('title', 'Upload History - Doctor Portal')

@section('sidebar-nav')
<div class="nav-group-title">Medical Assessment</div>
<a href="#" class="nav-item" onclick="event.preventDefault(); toggleColumns();">
    <i class="fas fa-columns"></i> Toggle Details
</a>
@endsection

@section('header-title', 'Doctor Logs')

@section('content')
<div class="card" style="margin-bottom: 24px;">
    <div class="card-header">
        <a href="{{ route('doctor.index') }}" class="btn btn-outline btn-sm" style="margin-bottom: 0;">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header">Filter Logs</div>
    <div class="card-body">
        <div style="display: flex; gap: 16px; flex-wrap: wrap; margin-bottom: 20px;">
            <div>
                <label style="display: block; font-weight: 500; margin-bottom: 4px; font-size: 0.85rem; color: var(--text-muted);">Applicant No.</label>
                <select id="patientSelect" class="select-modern" style="width: 200px;">
                    <option value="">Select Applicant No.</option>
                    @foreach ($patients as $p)
                        <option value="{{ $p->patient_no_logs }}" data-first="{{ $p->firstName_logs }}" data-last="{{ $p->lastName_logs }}">
                            {{ $p->patient_no_logs }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="display: block; font-weight: 500; margin-bottom: 4px; font-size: 0.85rem; color: var(--text-muted);">First Name</label>
                <input type="text" id="firstNameInput" disabled class="input-modern" style="width: 180px; background: rgba(255,255,255,0.5);">
            </div>
            <div>
                <label style="display: block; font-weight: 500; margin-bottom: 4px; font-size: 0.85rem; color: var(--text-muted);">Last Name</label>
                <input type="text" id="lastNameInput" disabled class="input-modern" style="width: 180px; background: rgba(255,255,255,0.5);">
            </div>
            <div>
                <label style="display: block; font-weight: 500; margin-bottom: 4px; font-size: 0.85rem; color: var(--text-muted);">Section Logs</label>
                <select id="sectionLogs" class="select-modern" style="width: 200px;">
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
    </div>
</div>

<div class="card" style="margin-top: 24px;">
    <div class="card-header">Log Results</div>
    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th>Approval Status</th>
                        <th>Timestamp</th>
                        <th>Name of User</th>
                    </tr>
                </thead>
                <tbody id="logsTableBody">
                    <tr><td colspan="3" style="text-align:center;padding:20px;font-weight:bold;color:var(--text-muted);">No records found</td></tr>
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
        fetch(`{{ route('api.logs.doctor') }}?patient_no=${patientNo}&section=${section}`)
            .then(r => r.json())
            .then(data => {
                const tbody = document.getElementById('logsTableBody');
                tbody.innerHTML = data.length
                    ? data.map(row => `<tr>
                        <td style="padding: 12px 16px; border-bottom: 1px solid var(--border-color);">
                            <span class="badge badge-neutral">${row.approval}</span>
                        </td>
                        <td style="padding: 12px 16px; border-bottom: 1px solid var(--border-color);">${row.timestamp}</td>
                        <td style="padding: 12px 16px; border-bottom: 1px solid var(--border-color);">${row.user_username}</td>
                    </tr>`).join('')
                    : '<tr><td colspan="3" style="text-align:center;padding:20px;font-weight:bold;color:#888;">No records found</td></tr>';
            });
    });
</script>
@endpush
