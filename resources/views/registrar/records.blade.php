@extends('layouts.portal')

@section('title', 'Active Patient Records - ARO Visayas')

@php
    $blankPhoto = '/images/blank.jpg';

    $resolveAsset = function ($path) use ($blankPhoto) {
        $value = trim((string) $path);
        if ($value === '') return $blankPhoto;
        if (
            str_starts_with($value, 'http://') ||
            str_starts_with($value, 'https://') ||
            str_starts_with($value, '//') ||
            str_starts_with($value, '/')
        ) return $value;
        return '/' . ltrim($value, '/');
    };

    $formatDate = function ($value, $format = 'm/d/Y h:i A', $fallback = '-') {
        if (blank($value)) return $fallback;
        try { return \Carbon\Carbon::parse($value)->format($format); }
        catch (\Throwable) { return (string) $value; }
    };

    $displayOverall = function ($patient) {
        $chiefApproval = strtoupper(trim((string) $patient->applications->first()?->latestExamination()?->chief_doctor_approval ?? ''));
        $stat = strtoupper(trim((string) $patient->applications->first()?->latestExamination()?->overall_stat ?? ''));
        if (strtolower($patient->applications->first()?->status) === 'inactive') return 'Archived';
        if ($chiefApproval === 'FAILED') return 'Disqualified';
        if ($stat === 'COMPLETED') return 'Done';
        if ($stat === 'DONE') return 'Cleared';
        if ($stat === 'NOT DONE') return 'Ongoing';
        return 'Pending';
    };

    $statusClass = function ($patient) {
        $chiefApproval = strtoupper(trim((string) $patient->applications->first()?->latestExamination()?->chief_doctor_approval ?? ''));
        $stat = strtoupper(trim((string) $patient->applications->first()?->latestExamination()?->overall_stat ?? ''));
        if (strtolower($patient->applications->first()?->status) === 'inactive') return 'status-pending';
        if ($chiefApproval === 'FAILED') return 'status-disqualified';
        if ($stat === 'COMPLETED' || $stat === 'DONE') return 'status-approved';
        if ($stat === 'NOT DONE') return 'status-pending';
        return 'status-disqualified';
    };

    $statusIcon = function ($patient) {
        $chiefApproval = strtoupper(trim((string) $patient->applications->first()?->latestExamination()?->chief_doctor_approval ?? ''));
        $stat = strtoupper(trim((string) $patient->applications->first()?->latestExamination()?->overall_stat ?? ''));
        if ($chiefApproval === 'FAILED') {
            return '<i class="fas fa-times-circle" style="color: #dc2626;"></i>';
        }
        if ($stat === 'COMPLETED' || $stat === 'DONE') {
            return '<i class="fas fa-circle-check" style="color: #16a34a;"></i>';
        }
        return '<i class="fas fa-hourglass-half" style="color: #ca8a04;"></i>';
    };

    $categoryStatus = function ($value) {
        $val = strtoupper(trim((string) $value));
        return match($val) {
            'GO' => ['text' => 'GO', 'class' => 'status-approved'],
            'NO GO', 'DQ' => ['text' => 'NO GO', 'class' => 'status-disqualified'],
            'COMPLIANCE' => ['text' => 'COMPLIANCE', 'class' => 'status-compliance'],
            'WAITING FOR RESULT' => ['text' => 'WAITING FOR RESULT', 'class' => 'status-compliance'],
            default => ['text' => 'NOT STARTED', 'class' => 'status-pending'],
        };
    };

    $stations = config('stations.stations', []);

    $patientsPayload = $patients->map(function ($patient) use (
        $resolveAsset,
        $displayOverall,
        $statusClass,
        $statusIcon,
        $categoryStatus,
        $stations
    ) {
        $photoUrl = $patient->photo_url ?: $resolveAsset($patient->photo_path ?? null);
        $displayOverallValue = $displayOverall($patient);
        $badgeClass = $statusClass($patient);
        $badgeIcon = $statusIcon($patient);

        $fullName = trim(implode(' ', array_filter([
            $patient->first_name ?? '',
            $patient->middle_name ?? '',
            $patient->last_name ?? '',
        ]))) ?: 'N/A';

        $stationData = [];
        foreach ($stations as $key => $config) {
            $eval = $patient->applications->first()?->latestExamination()?->clearances->where('station.station_key', $key)->first();
            $approvalVal = $eval ? $eval->approval_status : '';
            $stationData[$key] = [
                'label' => $config['label'],
                'approval' => $approvalVal,
                'diagnosis' => $eval ? $eval->diagnosis : '',
                'remarks' => $eval ? $eval->remarks : '',
                'attachment' => $eval && $eval->attachment ? asset($eval->attachment) : '',
                'status' => $categoryStatus($approvalVal),
            ];
        }

        $chiefApproval = strtoupper(trim((string) $patient->applications->first()?->latestExamination()?->chief_doctor_approval ?? ''));
        $chiefDoctorStatus = match($chiefApproval) {
            'PASSED' => ['text' => 'CLEARED', 'class' => 'status-approved'],
            'FAILED' => ['text' => 'DISQUALIFIED', 'class' => 'status-disqualified'],
            default => ['text' => 'PENDING', 'class' => 'status-pending'],
        };
        $formData = ($patient->applications->first()?->latestExamination() && is_array($patient->applications->first()?->latestExamination()->form_data)) ? $patient->applications->first()?->latestExamination()->form_data : [];

        return [
            'patient_no' => (string) $patient->patient_id,
            'firstName' => $patient->first_name ?? '',
            'middleName' => $patient->middle_name ?? '',
            'lastName' => $patient->last_name ?? '',
            'full_name' => $fullName,
            'birthdate' => $patient->birthdate ?? '',
            'sex' => $patient->sex ?? '',
            'batch' => $patient->applications->first()?->batch ?? '',
            'unitorg' => $patient->serviceRecord?->unit?->unit_name ?? '',
            'appcode' => $patient->applications->first()?->app_code ?? '',
            'photo_url' => $photoUrl,
            'overall_stat' => $patient->applications->first()?->latestExamination()?->overall_stat ?? '',
            'chief_doctor_approval' => $patient->applications->first()?->latestExamination()?->chief_doctor_approval ?? '',
            'display_status' => $displayOverallValue,
            'status_badge_label' => $displayOverallValue,
            'status_badge_icon' => $badgeIcon,
            'status_badge_class' => $badgeClass,
            'stations' => $stationData,
            'chief_doctor' => [
                'status' => $chiefDoctorStatus,
                'diagnosis' => $formData['doctors_diagnosis'] ?? '',
                'remarks' => $formData['doctors_remarks'] ?? '',
            ],
            'medical_form_id' => $patient->applications->first()?->latestExamination()?->examination_id,
            'medical_form_exists' => $patient->applications->first()?->latestExamination() !== null,
            'all_assessments_completed' => $patient->applications->first()?->latestExamination()?->allAssessmentsCompleted() ?? false,
        ];
    })->values();

    $batchOptions = $patientsPayload->pluck('batch')->filter()->unique()->sort()->values();
@endphp

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

@section('header-title', 'Active Patient Records')

@section('content')
<div class="spec-dashboard">
    <aside class="card queue-panel">
        <div class="queue-header">
            <div class="panel-title">Active Patients</div>
        </div>

        <div class="patient-sidebar-controls" style="padding: 12px 20px 0 20px; border-bottom: none;">
            <div class="control-group">
                <div style="display:flex; gap: 12px; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 180px;">
                        <select id="globalBatchFilter" class="select-modern">
                            <option value="All">Batch: All</option>
                            @foreach($batchOptions as $batch)
                                <option value="{{ $batch }}">{{ $batch }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div style="flex: 1; min-width: 240px; position: relative;">
                        <i class="fas fa-search" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
                        <input type="text" id="searchInput" class="input-modern" style="padding-left: 40px;" placeholder="Search Applicant">
                    </div>
                </div>
            </div>
        </div>

        <div class="queue-controls">
            <div class="control-group">
                <label for="queueStatusFilter">Status Filter</label>
                <select id="queueStatusFilter" class="select-modern">
                    <option value="All">All</option>
                    <option value="Ongoing" {{ $filter === 'ongoing' ? 'selected' : '' }}>Ongoing</option>
                    <option value="Cleared" {{ $filter === 'cleared' ? 'selected' : '' }}>Cleared</option>
                    <option value="Done" {{ $filter === 'done' ? 'selected' : '' }}>Done</option>
                    <option value="Disqualified" {{ $filter === 'disqualified' ? 'selected' : '' }}>Disqualified</option>
                    <option value="Archived" {{ $filter === 'archived' ? 'selected' : '' }}>Archived</option>
                </select>
            </div>
        </div>

        <div id="queueList" class="queue-list"></div>

        <div class="queue-footer">
            <div id="queueSummary" class="queue-summary">Showing 0-0 of 0</div>
            <div id="queuePagination" class="queue-pagination"></div>
        </div>
    </aside>

    <section class="card details-panel">
        <div class="details-header">
            <div class="panel-title">Patient Details</div>
            <div id="selectedApplicantMeta" class="panel-subtitle">Select an applicant from the queue.</div>
        </div>

        <div id="detailsContent" class="details-content"></div>
    </section>
</div>

<div id="imageModal" class="modal image-modal">
    <span class="close image-modal-close" onclick="closeImageModal()">&times;</span>
    <img id="modalImg" alt="Applicant photo">
</div>

{{-- Analytics Modal --}}
<div id="analyticsModal" style="display:none;position:fixed;z-index:1000;left:0;top:0;width:100%;height:100%;background-color:rgba(0,0,0,0.5);">
    <div style="background:var(--white);margin:8% auto;padding:30px;border-radius:8px;width:60%;max-width:700px;">
        <span onclick="closeAnalyticsModal()" style="float:right;font-size:28px;font-weight:bold;cursor:pointer;color:#aaa;">&times;</span>
        <h2 style="margin-top:0;color:var(--primary-color);"><i class="fas fa-chart-line"></i> Analytics Dashboard</h2>
        <div style="display:flex;gap:15px;align-items:center;flex-wrap:wrap;">
            <div>
                <label style="font-weight:600;display:block;margin-bottom:4px;font-size:0.85rem;">Record Type</label>
                <select id="analyticsRecordTypeSelect" class="select-modern">
                    <option value="0">Active</option>
                    <option value="1">Archived</option>
                </select>
            </div>
            <div>
                <label style="font-weight:600;display:block;margin-bottom:4px;font-size:0.85rem;">Category</label>
                <select id="analyticsCategorySelect" class="select-modern">
                    <option value="">Select Category</option>
                    <option value="All">All</option>
                    @foreach ($categoryMap as $key => $column)
                        <option value="{{ $key }}">{{ str_replace('_', '/', $key) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="font-weight:600;display:block;margin-bottom:4px;font-size:0.85rem;">Batch</label>
                <select id="analyticsBatchSelect" class="select-modern">
                    <option value="">Select Batch</option>
                    <option value="All">All</option>
                    @foreach ($batches as $batch)
                        <option value="{{ $batch }}">{{ $batch }}</option>
                    @endforeach
                </select>
            </div>
            <button id="filterAnalyticsBtn" class="btn btn-sm" style="background:var(--primary-color);color:white;margin-top:18px;">
                <i class="fas fa-filter"></i> Filter
            </button>
        </div>
        <div style="display:flex;gap:20px;margin-top:20px;">
            <div style="flex:1;padding:20px;border-radius:8px;text-align:center;box-shadow:0 2px 8px rgba(0,0,0,0.1);border-left:5px solid #22c55e;background:#f0fdf4;">
                <h3 style="margin:0 0 10px;font-size:1.1rem;">GO</h3>
                <p style="font-size:2rem;font-weight:bold;margin:0;" id="goCount">0</p>
            </div>
            <div style="flex:1;padding:20px;border-radius:8px;text-align:center;box-shadow:0 2px 8px rgba(0,0,0,0.1);border-left:5px solid #ef4444;background:#fef2f2;">
                <h3 style="margin:0 0 10px;font-size:1.1rem;">NO GO</h3>
                <p style="font-size:2rem;font-weight:bold;margin:0;" id="noGoCount">0</p>
            </div>
            <div style="flex:1;padding:20px;border-radius:8px;text-align:center;box-shadow:0 2px 8px rgba(0,0,0,0.1);border-left:5px solid #eab308;background:#fefce8;">
                <h3 style="margin:0 0 10px;font-size:1.1rem;">COMPLYING</h3>
                <p style="font-size:2rem;font-weight:bold;margin:0;" id="complyingCount">0</p>
            </div>
            <div style="flex:1;padding:20px;border-radius:8px;text-align:center;box-shadow:0 2px 8px rgba(0,0,0,0.1);border-left:5px solid #3b82f6;background:#eff6ff;">
                <h3 style="margin:0 0 10px;font-size:1.1rem;">WAITING</h3>
                <p style="font-size:2rem;font-weight:bold;margin:0;" id="waitingCount">0</p>
            </div>
        </div>
        <div style="margin-top:30px;">
            <canvas id="analyticsChart" width="400" height="200"></canvas>
        </div>
    </div>
</div>

{{-- Medical Certificate Modal --}}
<div id="medCertModal" class="modal" style="display:none;">
    <div class="modal-content" style="background:var(--white);border-radius:8px;max-width:550px;width:90%;padding:30px;">
        <span class="close" onclick="closeMedCertModal()" style="float:right;font-size:28px;font-weight:bold;cursor:pointer;color:#aaa;">&times;</span>
        <h2 style="margin-top:0;color:var(--primary-color);"><i class="fas fa-file-medical"></i> Download Medical Certificate</h2>
        
        <div style="margin-top:16px; margin-bottom: 20px;">
            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 600;">
                <input type="checkbox" id="medCertManualToggle" onchange="toggleMedCertManualMode()">
                Type a signing doctor manually
            </label>
        </div>

        <div id="medCertExistingDoctorBlock">
            <label style="display:block;font-weight:600;margin-bottom:8px;">Select Existing Signing Doctor</label>
            <select id="signingDoctorSelect" class="select-modern" style="width:100%;">
                @foreach ($signingDoctors as $doctor)
                    <option value="{{ $doctor->id }}">{{ $doctor->name }} {{ $doctor->rank ? '(' . $doctor->rank . ')' : '' }} {{ $doctor->position ? '- ' . $doctor->position : '' }}</option>
                @endforeach
            </select>
        </div>

        <div id="medCertManualDoctorBlock" style="display: none;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div>
                    <label style="display:block;font-weight:600;margin-bottom:4px;font-size:0.85rem;">Full Name *</label>
                    <input type="text" id="manualName" class="input-modern" style="width:100%;" placeholder="e.g. Emmanuel M Garcia Jr">
                </div>
                <div>
                    <label style="display:block;font-weight:600;margin-bottom:4px;font-size:0.85rem;">Rank *</label>
                    <input type="text" id="manualRank" class="input-modern" style="width:100%;" placeholder="e.g. Colonel">
                </div>
                <div>
                    <label style="display:block;font-weight:600;margin-bottom:4px;font-size:0.85rem;">AFPOS</label>
                    <input type="text" id="manualAfpos" class="input-modern" style="width:100%;" placeholder="e.g. MC">
                </div>
                <div>
                    <label style="display:block;font-weight:600;margin-bottom:4px;font-size:0.85rem;">Status</label>
                    <input type="text" id="manualStatus" class="input-modern" style="width:100%;" placeholder="e.g. GSC">
                </div>
                <div style="grid-column: span 2;">
                    <label style="display:block;font-weight:600;margin-bottom:4px;font-size:0.85rem;">Position *</label>
                    <input type="text" id="manualPosition" class="input-modern" style="width:100%;" placeholder="e.g. Commanding Officer">
                </div>
            </div>
        </div>

        <div style="margin-top:24px;display:flex;gap:10px;justify-content:flex-end;">
            <button type="button" class="btn btn-secondary" onclick="closeMedCertModal()">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="downloadMedCert(document.getElementById('medCertModal').dataset.patientNo)">Download</button>
        </div>
    </div>
</div>
    @include('partials.qr-modal')

<!-- Export Modal -->
<div class="modal-overlay" id="exportBatchModal" style="display: none; align-items: center; justify-content: center;">
    <div class="modal-content" style="max-width: 450px; padding: 24px; border-radius: 12px; background: white; text-align: center;">
        <h3 style="margin-top: 0; color: #1e293b;">Export & Archive Batch</h3>
        <p style="color: #64748b; margin-bottom: 24px; text-align: left;">
            Select a batch to export. 
            <br><br>
            <strong style="color: #ca8a04;"><i class="fas fa-exclamation-circle"></i> Note:</strong> Exporting this batch will automatically mark it as inactive and move it to the Archives.
        </p>
        <div style="text-align: left; margin-bottom: 20px;">
            <label style="font-weight: 500; display: block; margin-bottom: 8px;">Select Batch:</label>
            <select id="exportBatchSelect" class="select-modern" style="width: 100%;">
                <option value="" disabled selected>Select a batch to export...</option>
                @foreach($batches as $batch)
                    <option value="{{ $batch }}">{{ $batch }}</option>
                @endforeach
            </select>
        </div>
        <div style="display: flex; gap: 12px; justify-content: flex-end;">
            <button class="btn btn-secondary" onclick="closeExportModal()">Cancel</button>
            <button class="btn btn-primary" onclick="submitExportBatch()">Export & Archive</button>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
    .spec-dashboard {
        display: grid;
        grid-template-columns: minmax(300px, 360px) minmax(0, 1fr);
        gap: 20px;
        height: calc(100vh - 140px);
        overflow: hidden;
        --border-color: rgba(180, 195, 210, 0.4);
        --border-thick: 1px;
    }

    .queue-panel,
    .details-panel {
        display: flex;
        flex-direction: column;
        min-height: 0;
        background: rgba(255, 255, 255, 0.6);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.8);
        border-radius: 20px;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
    }

    .card {
        display: flex;
        flex-direction: column;
    }

    .queue-header,
    .details-header {
        padding: 18px 20px;
        border-bottom: var(--border-thick) solid var(--border-color);
        background: rgba(255, 255, 255, 0.3);
        border-top-left-radius: 20px;
        border-top-right-radius: 20px;
    }

    .panel-title {
        font-size: 1rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--text-main);
    }

    .panel-subtitle {
        margin-top: 6px;
        font-size: 0.85rem;
        color: var(--text-muted);
    }

    .queue-controls {
        padding: 16px 20px;
        border-bottom: var(--border-thick) solid var(--border-color);
        background: rgba(255, 255, 255, 0.2);
    }

    .control-group label {
        display: block;
        margin-bottom: 6px;
        font-size: 0.78rem;
        font-weight: 600;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .queue-panel {
        background: transparent;
        overflow: hidden;
    }

    .queue-list {
        flex: 1;
        padding: 10px;
        background: transparent;
        overflow-y: auto;
    }

    .queue-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        width: 100%;
        border: 1px solid rgba(180, 195, 210, 0.4);
        border-radius: 14px;
        background: rgba(255, 255, 255, 0.5);
        padding: 14px 12px;
        cursor: pointer;
        transition: all 0.2s ease;
        text-align: left;
    }

    .queue-item:hover {
        background: rgba(255, 255, 255, 0.8);
        border-color: var(--primary-color);
    }

    .queue-item.active {
        background: var(--primary-grad);
        border-color: rgba(255, 255, 255, 0.3);
        box-shadow: 0 4px 14px rgba(217, 119, 6, 0.3);
    }

    .queue-radio {
        width: 16px;
        height: 16px;
        border-radius: 50%;
        border: 2px solid rgba(180, 195, 210, 0.5);
        position: relative;
        flex-shrink: 0;
    }

    .queue-item.active .queue-radio {
        border-color: var(--primary-color);
    }

    .queue-item.active .queue-radio::after {
        content: '';
        position: absolute;
        inset: 3px;
        border-radius: 50%;
        background: var(--primary-color);
    }

    .station-card-header {
        padding: 12px 16px;
        background: rgba(255, 255, 255, 0.5);
        border-bottom: 1px solid var(--border-color);
        font-size: 0.85rem;
        font-weight: 700;
        color: var(--text-main);
    }

    .queue-item-label {
        font-weight: 700;
        color: var(--text-main);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .status-badge-chief {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border-radius: 999px;
        padding: 5px 10px;
        font-size: 0.76rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .status-approved { background: #dcfce7; color: #16a34a; }
    .status-pending { background: #fef3c7; color: #92400e; }
    .status-disqualified { background: #fee2e2; color: #991b1b; }
    .status-compliance { background: #ffedd5; color: #9a3412; }

    .queue-footer {
        padding: 14px 20px 18px;
        border-top: var(--border-thick) solid var(--border-color);
        background: rgba(255, 255, 255, 0.2);
        border-bottom-left-radius: 20px;
        border-bottom-right-radius: 20px;
    }

    .queue-summary {
        font-size: 0.82rem;
        color: var(--text-muted);
        margin-bottom: 12px;
    }

    .queue-pagination {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        align-items: center;
        margin-bottom: 14px;
    }

    .page-btn {
        min-width: 34px;
        height: 34px;
        border-radius: 10px;
        border: var(--border-thick) solid var(--border-color);
        background: var(--white);
        color: var(--text-main);
        cursor: pointer;
        padding: 0 12px;
        font-size: 0.82rem;
    }

    .page-btn.active {
        background: var(--primary-color);
        border-color: var(--primary-color);
        color: var(--white);
    }

    .page-btn:disabled {
        opacity: 0.45;
        cursor: not-allowed;
    }

    .details-content {
        flex: 1;
        overflow-y: auto;
        padding: 20px;
    }

    .details-stack {
        display: grid;
        gap: 18px;
    }

    .details-section {
        border: var(--border-thick) solid var(--border-color);
        border-radius: 16px;
        overflow: hidden;
        background: rgba(255, 255, 255, 0.3);
    }

    .station-card {
        border: 1px solid var(--border-color);
        border-radius: 12px;
        overflow: hidden;
        background: rgba(255, 255, 255, 0.4);
    }

    .details-section-header {
        padding: 14px 18px;
        border-bottom: var(--border-thick) solid var(--border-color);
        background: rgba(255, 255, 255, 0.4);
        font-size: 0.82rem;
        font-weight: 700;
        color: var(--text-main);
        text-transform: uppercase;
        letter-spacing: 0.06em;
    }

    .details-section-body {
        padding: 18px;
    }

    .applicant-grid {
        display: grid;
        grid-template-columns: 140px minmax(0, 1fr);
        gap: 18px;
        align-items: start;
    }

    .applicant-photo {
        width: 120px;
        height: 120px;
        border-radius: 20px;
        object-fit: cover;
        border: var(--border-thick) solid var(--border-color);
        cursor: pointer;
        box-shadow: var(--shadow-sm);
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px 18px;
    }

    .info-item {
        display: grid;
        grid-template-columns: 130px minmax(0, 1fr);
        gap: 10px;
    }

    .info-label {
        font-weight: 600;
        color: var(--text-muted);
    }

    .info-value {
        color: var(--text-main);
        word-break: break-word;
    }

    .action-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .btn-secondary {
        background: #64748b;
        color: #fff;
    }
    .btn-secondary:hover { background: #475569; }

    .btn-warning {
        background: #f59e0b;
        color: #fff;
    }
    .btn-warning:hover { background: #d97706; }

    .btn-danger-solid {
        background: #ef4444;
        color: #fff;
    }
    .btn-danger-solid:hover { background: #dc2626; }

    .btn-purple {
        background: #8b5cf6;
        color: #fff;
    }
    .btn-purple:hover { background: #7c3aed; }

    .btn-outline-soft {
        background: #fff;
        border: 1px solid var(--border-color);
        color: var(--text-main);
    }
    .btn-outline-soft:hover { background: var(--bg-color); }

    .empty-panel {
        display: grid;
        place-items: center;
        min-height: 360px;
        text-align: center;
        color: var(--text-muted);
        border: 1px dashed var(--border-color);
        border-radius: 16px;
        padding: 24px;
    }

    .modal {
        display: none;
        position: fixed;
        inset: 0;
        z-index: 1000;
        background: rgba(28, 25, 23, 0.6);
        justify-content: center;
        align-items: center;
        padding: 20px;
    }

    .image-modal {
        display: none;
        justify-content: center;
        align-items: center;
        background: rgba(28, 25, 23, 0.88);
    }
    .image-modal img {
        max-width: min(92vw, 900px);
        max-height: 88vh;
        border-radius: 18px;
        box-shadow: 0 24px 48px rgba(0, 0, 0, 0.4);
    }
    .image-modal-close {
        top: 20px;
        right: 32px;
        color: #fff;
    }

    @media (max-width: 1000px) {
        .spec-dashboard {
            grid-template-columns: 1fr;
        }
        .applicant-grid {
            grid-template-columns: 1fr;
        }
        .info-grid,
        .form-row {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 640px) {
        .content { padding: 16px; }
        .details-content { padding: 16px; }
        .queue-header,
        .details-header,
        .queue-controls,
        .queue-footer,
        .details-section-header,
        .details-section-body {
            padding-left: 16px;
            padding-right: 16px;
        }
        .info-item {
            grid-template-columns: 1fr;
            gap: 4px;
        }
        .action-buttons .btn {
            width: 100%;
        }
        .spec-header-actions {
            width: 100%;
            flex-wrap: wrap;
        }
        .spec-toolbar-select,
        .spec-search {
            width: 100%;
            min-width: 0;
        }
    }
</style>
@endpush

@push('scripts')
<script src="{{ asset('js/vendor/chart-4.6.1.js') }}"></script>
<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
const patients = @json($patientsPayload);
const currentFilter = @json($filter);

const state = {
    batch: 'All',
    search: '',
    statusFilter: currentFilter === '' ? 'All' : (
        currentFilter === 'ongoing' ? 'Ongoing' :
        currentFilter === 'cleared' ? 'Cleared' :
        currentFilter === 'done' ? 'Done' :
        currentFilter === 'disqualified' ? 'Disqualified' : 'All'
    ),
    page: 1,
    perPage: 20,
    selectedPatientNo: patients[0]?.patient_no ?? null,
};

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function getPatient(patientNo = state.selectedPatientNo) {
    return patients.find(p => p.patient_no === String(patientNo)) ?? null;
}

function normalizeStatusFilter(patient) {
    return patient.display_status;
}

function getFilteredPatients() {
    let result = [...patients];

    if (state.batch !== 'All') {
        result = result.filter(p => p.batch === state.batch);
    }

    if (state.search.trim() !== '') {
        const keyword = state.search.trim().toLowerCase();
        result = result.filter(p => {
            return [
                p.patient_no,
                p.full_name,
                p.batch,
            ].some(field => String(field ?? '').toLowerCase().includes(keyword));
        });
    }

    if (state.statusFilter !== 'All') {
        result = result.filter(p => normalizeStatusFilter(p) === state.statusFilter);
    }

    result.sort((a, b) => Number(b.patient_no) - Number(a.patient_no));

    return result;
}

function updateQueueCounts() {
    // Optional, for future use
}

function renderQueue() {
    updateQueueCounts();

    const filtered = getFilteredPatients();
    const listEl = document.getElementById('queueList');
    const summaryEl = document.getElementById('queueSummary');
    const paginationEl = document.getElementById('queuePagination');

    if (!listEl || !summaryEl || !paginationEl) return;

    const total = filtered.length;
    const pageCount = Math.max(1, Math.ceil(total / state.perPage));
    if (state.page > pageCount) state.page = pageCount;

    const startIndex = total === 0 ? 0 : ((state.page - 1) * state.perPage) + 1;
    const endIndex = Math.min(state.page * state.perPage, total);
    const pageItems = filtered.slice(startIndex - 1, endIndex);

    if (!filtered.some(p => p.patient_no === state.selectedPatientNo)) {
        state.selectedPatientNo = filtered[0]?.patient_no ?? null;
    }

    if (pageItems.length === 0) {
        listEl.innerHTML = `
            <div class="empty-panel">
                <div>
                    <strong>No matching applicants.</strong>
                    <div style="margin-top:8px;">Try changing the status filter, batch filter, or search term.</div>
                </div>
            </div>
        `;
    } else {
        listEl.innerHTML = pageItems.map(p => `
            <button type="button" class="queue-item ${p.patient_no === state.selectedPatientNo ? 'active' : ''}" onclick="selectApplicant('${escapeHtml(p.patient_no)}')" style="display: grid; grid-template-columns: 22px minmax(0, 1fr); gap: 12px;">
                <span class="queue-radio" aria-hidden="true" style="margin-top: 5px;"></span>
                <span style="min-width: 0;">
                    <span style="display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 8px;">
                        <span style="font-weight: 700; color: var(--text-main);">${escapeHtml(p.patient_no)}</span>
                        <span class="status-badge-chief ${escapeHtml(p.status_badge_class)}">
                            ${p.status_badge_icon}
                            <span>${escapeHtml(p.status_badge_label)}</span>
                        </span>
                    </span>
                    <div style="font-size: 0.92rem; font-weight: 600; color: var(--text-main); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${escapeHtml(p.full_name)}</div>
                    <div style="margin-top: 4px; font-size: 0.78rem; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${escapeHtml(p.batch)}</div>
                </span>
            </button>
        `).join('');
    }

    summaryEl.textContent = `Showing ${startIndex}-${endIndex} of ${total}`;

    const pagesToShow = [];
    for (let page = 1; page <= pageCount; page += 1) {
        if (page === 1 || page === pageCount || Math.abs(page - state.page) <= 1) pagesToShow.push(page);
    }
    const uniquePages = [...new Set(pagesToShow)];
    paginationEl.innerHTML = `
        <button type="button" class="page-btn" ${state.page === 1 ? 'disabled' : ''} onclick="changePage(${state.page - 1})">Prev</button>
        ${uniquePages.map(page => `
            <button type="button" class="page-btn ${page === state.page ? 'active' : ''}" onclick="changePage(${page})">${page}</button>
        `).join('')}
        <button type="button" class="page-btn" ${state.page === pageCount ? 'disabled' : ''} onclick="changePage(${state.page + 1})">Next</button>
    `;
}

function renderDetails() {
    const content = document.getElementById('detailsContent');
    const meta = document.getElementById('selectedApplicantMeta');
    const patient = getPatient();

    if (!content || !meta) return;

    if (!patient) {
        meta.textContent = 'Select an applicant from the queue.';
        content.innerHTML = `
            <div class="empty-panel">
                <div>
                    <strong>No applicant selected.</strong>
                    <div style="margin-top:8px;">Pick a patient from the left queue to view details.</div>
                </div>
            </div>
        `;
        return;
    }

    meta.innerHTML = `${escapeHtml(patient.full_name)} • Patient No ${escapeHtml(patient.patient_no)}`;

    let stationDetailsHtml = '';
    Object.keys(patient.stations).forEach(key => {
        const station = patient.stations[key];
        stationDetailsHtml += `
            <section class="details-section">
                <div class="details-section-header">${escapeHtml(station.label)}</div>
                <div class="details-section-body">
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Status</div>
                            <div class="info-value"><span class="status-badge-chief ${escapeHtml(station.status.class)}">${escapeHtml(station.status.text)}</span></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Diagnosis</div>
                            <div class="info-value">${escapeHtml(station.diagnosis) || '-'}</div>
                        </div>
                        <div class="info-item" style="grid-column: 1 / -1;">
                            <div class="info-label">Remarks</div>
                            <div class="info-value">${escapeHtml(station.remarks) || '-'}</div>
                        </div>
                        ${station.attachment ? `
                            <div class="info-item" style="grid-column: 1 / -1;">
                                <div class="info-label">Attachment</div>
                                <div class="info-value">
                                    <a href="${escapeHtml(station.attachment)}" target="_blank" style="display:inline-flex;align-items:center;gap:6px;color:var(--primary-color);text-decoration:underline;">
                                        <i class="fas fa-download"></i>
                                        ${escapeHtml(station.attachment.split('/').pop())}
                                    </a>
                                </div>
                            </div>
                        ` : ''}
                    </div>
                </div>
            </section>
        `;
    });

    content.innerHTML = `
        <div class="details-stack">
            <section class="details-section">
                <div class="details-section-body">
                    <div class="applicant-grid">
                        <div style="display:flex; justify-content:center;">
                            <img src="${escapeHtml(patient.photo_url)}" class="applicant-photo" alt="${escapeHtml(patient.full_name)}" onclick="enlargeImage(this)">
                        </div>
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">Patient No</div>
                                <div class="info-value">${escapeHtml(patient.patient_no)}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Full Name</div>
                                <div class="info-value">${escapeHtml(patient.full_name)}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Birthdate</div>
                                <div class="info-value">${escapeHtml(patient.birthdate)}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Sex</div>
                                <div class="info-value">${escapeHtml(patient.sex)}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Batch</div>
                                <div class="info-value">${escapeHtml(patient.batch)}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Unit / Org</div>
                                <div class="info-value">${escapeHtml(patient.unitorg)}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            ${stationDetailsHtml}

            <section class="details-section">
                <div class="details-section-header">Chief Doctor Evaluation</div>
                <div class="details-section-body">
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Status</div>
                            <div class="info-value"><span class="status-badge-chief ${escapeHtml(patient.chief_doctor.status.class)}">${escapeHtml(patient.chief_doctor.status.text)}</span></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Diagnosis</div>
                            <div class="info-value">${escapeHtml(patient.chief_doctor.diagnosis) || '-'}</div>
                        </div>
                        <div class="info-item" style="grid-column: 1 / -1;">
                            <div class="info-label">Remarks</div>
                            <div class="info-value">${escapeHtml(patient.chief_doctor.remarks) || '-'}</div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="details-section">
                <div class="details-section-header">Actions</div>
                <div class="details-section-body">
                    <div class="action-buttons" style="margin-bottom:14px;">
                        <button type="button" class="btn btn-success btn-sm" onclick="markPatientDone(${parseInt(patient.patient_no)})" ${!['Cleared', 'Disqualified'].includes(patient.display_status) ? 'disabled style="opacity:0.6;cursor:not-allowed;"' : ''}>
                            <i class="fas fa-check"></i> Mark as Done
                        </button>
                        <button type="button" class="btn btn-danger btn-sm" onclick="returnPatient(${parseInt(patient.patient_no)})" ${!['Done', 'Archived'].includes(patient.display_status) ? 'disabled style="opacity:0.6;cursor:not-allowed;"' : ''}>
                            <i class="fas fa-undo"></i> Return
                        </button>
                        ${patient.medical_form_id && patient.medical_form_exists && patient.all_assessments_completed ? `
                            <a href="{{ url('/medical-form/pdf') }}/${patient.medical_form_id}" target="_blank" class="btn btn-primary btn-sm">
                                <i class="fas fa-file-pdf"></i> Download Redform
                            </a>
                        ` : `
                            <button type="button" class="btn btn-secondary btn-sm" disabled title="Red Form not created or specialist assessments incomplete">
                                <i class="fas fa-file-pdf"></i> Download Redform
                            </button>
                        `}
                        <button type="button" class="btn btn-outline-soft btn-sm" onclick="openMedCertModal(${parseInt(patient.patient_no)})">
                            <i class="fas fa-file-medical"></i> Download Medical Cert
                        </button>
                        <button type="button" class="btn btn-primary btn-sm" onclick='showQrModal(${JSON.stringify(patient).replace(/'/g, "\\'")})' title="View Applicant QR">
                            <i class="fas fa-qrcode"></i> View QR
                        </button>
                    </div>
                </div>
            </section>
        </div>
    `;
}

function renderAll() {
    renderQueue();
    renderDetails();
}

function selectApplicant(patientNo) {
    state.selectedPatientNo = String(patientNo);
    renderAll();
}

function changePage(page) {
    state.page = Math.max(1, page);
    renderQueue();
}

function enlargeImage(img) {
    const modal = document.getElementById('imageModal');
    const modalImg = document.getElementById('modalImg');
    modalImg.src = img.src;
    modal.style.display = 'flex';
}

function closeImageModal() {
    const modal = document.getElementById('imageModal');
    modal.style.display = 'none';
}


document.getElementById('showAnalyticsBtn')?.addEventListener('click', function() {
    document.getElementById('analyticsModal').style.display = 'block';
});

function closeAnalyticsModal() {
    document.getElementById('analyticsModal').style.display = 'none';
}

window.addEventListener('click', function (e) {
    if (e.target === document.getElementById('analyticsModal')) {
        closeAnalyticsModal();
    }
});
document.getElementById('filterAnalyticsBtn').addEventListener('click', function() {
    const batch = document.getElementById('analyticsBatchSelect').value;
    const category = document.getElementById('analyticsCategorySelect').value;
    const archive = document.getElementById('analyticsRecordTypeSelect').value;
    if (!batch || !category) { alert('Please select both batch and category.'); return; }

    fetch(`/api/analytics/category-counts?batch=${encodeURIComponent(batch)}&category=${encodeURIComponent(category)}&archive=${archive}&_t=${Date.now()}`)
        .then(r => r.json())
        .then(data => {
            document.getElementById('goCount').textContent = data.goCount ?? 0;
            document.getElementById('noGoCount').textContent = data.noGoCount ?? 0;
            document.getElementById('complyingCount').textContent = data.complyingCount ?? 0;
            document.getElementById('waitingCount').textContent = data.waitingCount ?? 0;
            
            if (window.analyticsChartInstance) {
                window.analyticsChartInstance.destroy();
            }
            const ctx = document.getElementById('analyticsChart').getContext('2d');
            window.analyticsChartInstance = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['GO', 'NO GO', 'COMPLYING', 'WAITING FOR RESULT'],
                    datasets: [{
                        label: 'Applicant Counts',
                        data: [data.goCount ?? 0, data.noGoCount ?? 0, data.complyingCount ?? 0, data.waitingCount ?? 0],
                        backgroundColor: [
                            'rgba(34, 197, 94, 0.6)',
                            'rgba(239, 68, 68, 0.6)',
                            'rgba(234, 179, 8, 0.6)',
                            'rgba(59, 130, 246, 0.6)'
                        ],
                        borderColor: [
                            'rgb(34, 197, 94)',
                            'rgb(239, 68, 68)',
                            'rgb(234, 179, 8)',
                            'rgb(59, 130, 246)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { stepSize: 1 }
                        }
                    }
                }
            });
        })
        .catch(() => alert('Failed to load analytics.'));
});

window.addEventListener('click', function (e) {
    if (e.target === document.getElementById('imageModal')) {
        closeImageModal();
    }
});

// Analytics features temporarily removed

function markPatientDone(patientNo) {
    if (!confirm('Mark patient #' + patientNo + ' as done?')) return;

    const fd = new FormData();
    fd.append('patient_no', patientNo);

    fetch('{{ route('api.patients.mark-done') }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken },
        body: fd
    })
    .then(r => r.json())
    .then(data => {
        if (data && data.success) {
            alert('Patient marked as done.');
            location.reload();
        } else {
            alert('Unable to mark patient as done.');
        }
    })
    .catch(() => alert('Network error.'));
}

function returnPatient(patientNo) {
    if (!confirm('Return patient #' + patientNo + ' to cleared state?')) return;

    const fd = new FormData();
    fd.append('patient_no', patientNo);

    fetch('{{ route('api.patients.registrar-return') }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken },
        body: fd
    })
    .then(r => r.json())
    .then(data => {
        if (data && data.success) {
            alert('Patient returned to cleared state.');
            location.reload();
        } else {
            alert('Unable to return patient.');
        }
    })
    .catch(() => alert('Network error.'));
}

function downloadRedForm(patientNo) {
    window.open(`{{ url('/patients') }}/${patientNo}/pdf`, '_blank');
}

function openMedCertModal(patientNo) {
    const modal = document.getElementById('medCertModal');
    modal.dataset.patientNo = patientNo;
    modal.style.display = 'flex';
}

function toggleMedCertManualMode() {
    const isManual = document.getElementById('medCertManualToggle').checked;
    document.getElementById('medCertExistingDoctorBlock').style.display = isManual ? 'none' : 'block';
    document.getElementById('medCertManualDoctorBlock').style.display = isManual ? 'block' : 'none';
}

function downloadMedCert(patientNo) {
    const isManual = document.getElementById('medCertManualToggle').checked;

    if (isManual) {
        if (!document.getElementById('manualName').value || !document.getElementById('manualRank').value || !document.getElementById('manualPosition').value) {
            alert('Please fill out Name, Rank, and Position fields.');
            return;
        }
    } else {
        if (!document.getElementById('signingDoctorSelect').value) {
            alert('Please select an existing signing doctor.');
            return;
        }
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ route('api.patients.med-cert') }}';
    form.target = '_blank';

    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = csrfToken;
    form.appendChild(csrfInput);

    const patientInput = document.createElement('input');
    patientInput.type = 'hidden';
    patientInput.name = 'patient_no';
    patientInput.value = patientNo;
    form.appendChild(patientInput);

    const manualToggleInput = document.createElement('input');
    manualToggleInput.type = 'hidden';
    manualToggleInput.name = 'is_manual';
    manualToggleInput.value = isManual ? '1' : '0';
    form.appendChild(manualToggleInput);

    if (isManual) {
        ['manualName', 'manualRank', 'manualAfpos', 'manualStatus', 'manualPosition'].forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            // manualName -> manual_name
            input.name = id.replace(/([A-Z])/g, "_$1").toLowerCase(); 
            input.value = document.getElementById(id).value;
            form.appendChild(input);
        });
    } else {
        const doctorIdInput = document.createElement('input');
        doctorIdInput.type = 'hidden';
        doctorIdInput.name = 'signing_doctor_id';
        doctorIdInput.value = document.getElementById('signingDoctorSelect').value;
        form.appendChild(doctorIdInput);
    }

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);

    document.getElementById('medCertModal').style.display = 'none';
}

function closeMedCertModal() {
    document.getElementById('medCertModal').style.display = 'none';
}

// Initialize filters
document.getElementById('globalBatchFilter').addEventListener('change', function () {
    state.batch = this.value;
    state.page = 1;
    renderAll();
});

document.getElementById('queueStatusFilter').addEventListener('change', function () {
    state.statusFilter = this.value;
    state.page = 1;

    let url = '{{ route('registrar.records') }}';
    if (state.statusFilter !== 'All') {
        const filterValue =
            state.statusFilter === 'Ongoing' ? 'ongoing' :
            state.statusFilter === 'Cleared' ? 'cleared' :
            state.statusFilter === 'Done' ? 'done' :
            state.statusFilter === 'Disqualified' ? 'disqualified' :
            state.statusFilter === 'Archived' ? 'archived' : '';
        url += '?filter=' + encodeURIComponent(filterValue);
    }
    window.location.href = url;
});

document.getElementById('searchInput').addEventListener('input', function () {
    state.search = this.value;
    state.page = 1;
    renderQueue();
});

// Render on load
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.has('search')) {
    const searchVal = urlParams.get('search');
    document.getElementById('searchInput').value = searchVal;
    state.search = searchVal;
    
    setTimeout(() => {
        const firstPatient = patients.find(p => 
            p.full_name.toLowerCase().includes(searchVal.toLowerCase()) || 
            (p.patient_no && p.patient_no.toString().includes(searchVal))
        );
        if (firstPatient) {
            selectApplicant(firstPatient.patient_no);
        }
    }, 100);
}

renderAll();

function openExportModal() {
    document.getElementById('exportBatchModal').style.display = 'flex';
}

function closeExportModal() {
    document.getElementById('exportBatchModal').style.display = 'none';
    document.getElementById('exportBatchSelect').value = '';
}

function submitExportBatch() {
    const batch = document.getElementById('exportBatchSelect').value;
    if (!batch) {
        alert('Please select a batch to export.');
        return;
    }

    const url = '{{ route('api.patients.export') }}?batch=' + encodeURIComponent(batch);
    window.open(url, '_blank');

    closeExportModal();
    setTimeout(() => {
        window.location.reload();
    }, 1500);
}
</script>
@endpush

