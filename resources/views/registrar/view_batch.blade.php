@extends('layouts.portal')

@section('title', 'Registrar - View Batch')

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
<a href="{{ route('registrar.archives') }}" class="nav-item active">
    <i class="fas fa-archive"></i> Archives
</a>
@endsection



@section('header-title', 'Batch: ' . $batch)

@section('content')
<div class="spec-dashboard">
    <div class="card" style="width: 100%;">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h3><i class="fas fa-users"></i> Applicants in {{ $batch }}</h3>
            <a href="{{ route('registrar.archives') }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Back to Archives</a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th style="width:50px;">#</th>
                            <th style="width:60px;">PHOTO</th>
                            <th>BATCH</th>
                            <th>APPLICANT NO.</th>
                            <th>APPCODE</th>
                            <th>FIRST NAME</th>
                            <th>MIDDLE NAME</th>
                            <th>LAST NAME</th>
                            <th>AGE</th>
                            <th>SEX</th>
                            <th>STATUS</th>
                            <th>ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($patients as $i => $patient)
                            @php
                                $approvalColumns = collect(config('stations.stations'))->pluck('approval')->all();
                                $statuses = array_map(fn ($col) => $patient->{$col}, $approvalColumns);
                                $overall = \App\Http\Controllers\ExecutiveController::patientStatus($statuses);
                            @endphp
                            <tr class="row-clickable"
                                style="cursor:pointer;"
                                data-name="{{ e($patient->fullName()) }}"
                                data-gpe="{{ e($patient->approvalValue('gpe') ?? '') }}"
                                data-np="{{ e($patient->approvalValue('np') ?? '') }}"
                                data-lab="{{ e($patient->approvalValue('lab_drugtest') ?? '') }}"
                                data-optha="{{ e($patient->approvalValue('optha_eent') ?? '') }}"
                                data-dental="{{ e($patient->approvalValue('dental') ?? '') }}"
                                data-ecg="{{ e($patient->approvalValue('ecg_xray') ?? '') }}">
                                <td style="padding:12px 16px;text-align:center;">{{ $i + 1 }}</td>
                                <td style="padding:12px 16px;text-align:center;">
                                    @php $photo = $patient->photo_url ?: '/images/blank.jpg'; @endphp
                                    <img src="{{ $photo }}" alt="Photo" style="width:55px;height:55px;border-radius:50%;object-fit:cover;border:2px solid var(--border-color);cursor:pointer;" onclick="event.stopPropagation(); openImgModal('{{ $photo }}')">
                                </td>
                                <td style="padding:12px 16px;text-align:center;">{{ $patient->applications->first()?->batch }}</td>
                                <td style="padding:12px 16px;text-align:center;">{{ $patient->patient_id }}</td>
                                <td style="padding:12px 16px;text-align:center;">{{ $patient->applications->first()?->app_code }}</td>
                                <td style="padding:12px 16px;text-align:center;">{{ $patient->first_name }}</td>
                                <td style="padding:12px 16px;text-align:center;">{{ $patient->middle_name }}</td>
                                <td style="padding:12px 16px;text-align:center;">{{ $patient->last_name }}</td>
                                <td style="padding:12px 16px;text-align:center;">{{ $patient->age }}</td>
                                <td style="padding:12px 16px;text-align:center;">{{ $patient->sex }}</td>
                                <td style="padding:12px 16px;text-align:center;">
                                    <span class="{{ $overall['class'] }}">{{ $overall['text'] }}</span>
                                </td>
                                <td style="padding:12px 16px;text-align:center;" onclick="event.stopPropagation();">
                                    <button class="btn btn-primary btn-sm" onclick="confirmMarkPatientActive('{{ $patient->patient_id }}', '{{ e($patient->fullName()) }}')">
                                        <i class="fas fa-undo"></i> Mark Active
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" style="padding:40px;text-align:center;color:var(--text-muted);font-style:italic;">No records found in this batch.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Reusing rowModal and imgModal structure from executive dashboard -->
<!-- Modal Mark Patient Active -->
<div class="modal-overlay" id="markPatientActiveModal" style="display: none; align-items: center; justify-content: center; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
    <div class="modal-content" style="max-width: 400px; padding: 24px; border-radius: 12px; background: white; text-align: center; position: relative;">
        <h3 style="margin-top: 0; color: #1e293b;">Mark Applicant Active</h3>
        <p style="color: #64748b; margin-bottom: 24px;">Are you sure you want to unarchive <strong id="activePatientName"></strong>? This will restore this individual applicant to the active queues.</p>
        <div style="display: flex; gap: 12px; justify-content: center;">
            <button class="btn btn-secondary" onclick="closePatientModal()">Cancel</button>
            <button class="btn btn-primary" onclick="submitMarkPatientActive()">Confirm</button>
        </div>
    </div>
</div>

<div id="rowModal" class="modal-overlay" style="display:none; align-items:center; justify-content:center;">
    <div class="modal-content" style="max-width:500px; padding:0; border-radius:12px; background:var(--white); overflow:hidden;">
        <div style="background:var(--primary-grad); padding:20px; color:white; display:flex; justify-content:space-between; align-items:center;">
            <h3 style="margin:0; font-size:1.2rem;">Applicant Status</h3>
            <span class="close" onclick="closeRowModal()" style="color:white; opacity:0.8; cursor:pointer;">&times;</span>
        </div>
        <div style="padding:24px;">
            <h4 id="rowName" style="margin-top:0; margin-bottom:20px; color:var(--text-color); border-bottom:1px solid var(--border-color); padding-bottom:10px;"></h4>
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px;">
                <div class="status-item">
                    <span style="font-weight:600; color:var(--text-muted); font-size:0.85rem; display:block; margin-bottom:4px;">GPE</span>
                    <span id="rowGpe" style="font-weight:700; font-size:1.1rem;"></span>
                </div>
                <div class="status-item">
                    <span style="font-weight:600; color:var(--text-muted); font-size:0.85rem; display:block; margin-bottom:4px;">Neuro-Psychiatric</span>
                    <span id="rowNp" style="font-weight:700; font-size:1.1rem;"></span>
                </div>
                <div class="status-item">
                    <span style="font-weight:600; color:var(--text-muted); font-size:0.85rem; display:block; margin-bottom:4px;">Lab & Drugtest</span>
                    <span id="rowLab" style="font-weight:700; font-size:1.1rem;"></span>
                </div>
                <div class="status-item">
                    <span style="font-weight:600; color:var(--text-muted); font-size:0.85rem; display:block; margin-bottom:4px;">Optha/EENT</span>
                    <span id="rowOptha" style="font-weight:700; font-size:1.1rem;"></span>
                </div>
                <div class="status-item">
                    <span style="font-weight:600; color:var(--text-muted); font-size:0.85rem; display:block; margin-bottom:4px;">Dental</span>
                    <span id="rowDental" style="font-weight:700; font-size:1.1rem;"></span>
                </div>
                <div class="status-item">
                    <span style="font-weight:600; color:var(--text-muted); font-size:0.85rem; display:block; margin-bottom:4px;">ECG & Xray</span>
                    <span id="rowEcg" style="font-weight:700; font-size:1.1rem;"></span>
                </div>
            </div>
        </div>
        <div style="padding:16px 24px; background:var(--bg-page); text-align:right;">
            <button class="btn btn-secondary" onclick="closeRowModal()">Close</button>
        </div>
    </div>
</div>

<div id="imageModal" class="modal image-modal" style="display:none;position:fixed;z-index:1000;left:0;top:0;width:100%;height:100%;background-color:rgba(0,0,0,0.8);align-items:center;justify-content:center;">
    <span class="close image-modal-close" onclick="closeImgModal()" style="position:absolute;top:20px;right:30px;color:white;font-size:40px;font-weight:bold;cursor:pointer;">&times;</span>
    <img id="modalImg" style="max-width:90%;max-height:90%;border-radius:8px;">
</div>
@endsection

@push('scripts')
<script>
    function formatStatus(val) {
        if (!val || val.trim() === '') return '<span style="color:#94a3b8;">Pending</span>';
        val = val.toUpperCase().trim();
        if (val === 'GO') return '<span style="color:#22c55e;">GO</span>';
        if (val === 'NO GO') return '<span style="color:#ef4444;">NO GO</span>';
        if (val === 'COMPLIANCE') return '<span style="color:#eab308;">COMPLYING</span>';
        if (val === 'WAITING FOR RESULT') return '<span style="color:#3b82f6;">WAITING</span>';
        return '<span>' + val + '</span>';
    }

    document.querySelectorAll('.row-clickable').forEach(row => {
        row.addEventListener('click', () => {
            document.getElementById('rowName').textContent = row.dataset.name;
            document.getElementById('rowGpe').innerHTML = formatStatus(row.dataset.gpe);
            document.getElementById('rowNp').innerHTML = formatStatus(row.dataset.np);
            document.getElementById('rowLab').innerHTML = formatStatus(row.dataset.lab);
            document.getElementById('rowOptha').innerHTML = formatStatus(row.dataset.optha);
            document.getElementById('rowDental').innerHTML = formatStatus(row.dataset.dental);
            document.getElementById('rowEcg').innerHTML = formatStatus(row.dataset.ecg);
            document.getElementById('rowModal').style.display = 'flex';
        });
    });

    function closeRowModal() {
        document.getElementById('rowModal').style.display = 'none';
    }

    function openImgModal(src) {
        const modal = document.getElementById('imageModal');
        const img = document.getElementById('modalImg');
        img.src = src;
        modal.style.display = 'flex';
    }

    function closeImgModal() {
        document.getElementById('imageModal').style.display = 'none';
    }

    window.onclick = function(e) {
        if (e.target == document.getElementById('rowModal')) closeRowModal();
        if (e.target == document.getElementById('imageModal')) closeImgModal();
    }
    let selectedPatientNo = '';

    function confirmMarkPatientActive(patientNo, patientName) {
        selectedPatientNo = patientNo;
        document.getElementById('activePatientName').innerText = patientName;
        document.getElementById('markPatientActiveModal').style.display = 'flex';
    }

    function closePatientModal() {
        document.getElementById('markPatientActiveModal').style.display = 'none';
        selectedPatientNo = '';
    }

    async function submitMarkPatientActive() {
        if (!selectedPatientNo) return;
        
        try {
            const res = await fetch('{{ route('registrar.archives.mark-patient-active') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ patient_no: selectedPatientNo })
            });
            const data = await res.json();
            if (data.success) {
                alert(data.message);
                window.location.reload();
            } else {
                alert('Error: ' + (data.message || 'Unknown error'));
            }
        } catch (e) {
            alert('An error occurred.');
        }
    }
</script>
@endpush

