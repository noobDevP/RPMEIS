@extends('layouts.portal')

@section('title', 'Station Evaluation - Doctor Portal')

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

    $displayOverall = function ($patient) {
        $chiefApproval = strtoupper(trim((string) $patient->applications->first()?->latestExamination()?->chief_doctor_approval ?? ''));
        if ($chiefApproval === 'FAILED') return 'Disqualified';
        $stat = strtoupper(trim((string) $patient->applications->first()?->latestExamination()?->overall_stat ?? ''));
        if ($stat === 'DONE') return 'Cleared';
        if ($stat === 'COMPLETED') return 'Done';
        if ($stat === 'NOT DONE') return 'Ongoing';
        return 'Pending';
    };

    $statusClass = function ($patient) {
        $chiefApproval = strtoupper(trim((string) $patient->applications->first()?->latestExamination()?->chief_doctor_approval ?? ''));
        if ($chiefApproval === 'FAILED') return 'status-disqualified';
        $s = strtoupper(trim((string)$patient->applications->first()?->latestExamination()?->overall_stat));
        if ($s === 'DONE' || $s === 'COMPLETED') return 'status-approved';
        if ($s === 'NOT DONE') return 'status-pending';
        return 'status-disqualified';
    };

    $statusIcon = function ($patient) {
        $chiefApproval = strtoupper(trim((string) $patient->applications->first()?->latestExamination()?->chief_doctor_approval ?? ''));
        if ($chiefApproval === 'FAILED') return '<i class="fas fa-times-circle" style="color: #dc2626;"></i>';
        $s = strtoupper(trim((string)$patient->applications->first()?->latestExamination()?->overall_stat));
        if ($s === 'DONE' || $s === 'COMPLETED') return '<i class="fas fa-circle-check" style="color: #16a34a;"></i>';
        if ($s === 'NOT DONE') return '<i class="fas fa-hourglass-half" style="color: #ca8a04;"></i>';
        return '<i class="fas fa-hourglass-half" style="color: #ca8a04;"></i>';
    };

    $categoryStatus = function ($value) {
        $val = strtoupper(trim((string)$value));
        return match($val) {
            'GO' => ['text' => 'GO', 'class' => 'status-approved', 'icon' => '<i class="fas fa-check-circle" style="color: #16a34a;"></i>'],
            'NO GO', 'DQ' => ['text' => 'NO GO', 'class' => 'status-disqualified', 'icon' => '<i class="fas fa-times-circle" style="color: #dc2626;"></i>'],
            'COMPLIANCE' => ['text' => 'COMPLIANCE', 'class' => 'status-compliance', 'icon' => '<i class="fas fa-clipboard-list" style="color: #ca8a04;"></i>'],
            'WAITING FOR RESULT' => ['text' => 'WAITING FOR RESULT', 'class' => 'status-compliance', 'icon' => '<i class="fas fa-clock" style="color: #ca8a04;"></i>'],
            default => ['text' => 'ONGOING', 'class' => 'status-pending', 'icon' => '<i class="fas fa-hourglass-half" style="color: #64748b;"></i>'],
        };
    };

    $stations = config('stations.stations', []);
    $assessmentType = match ($station) {
        'dental' => 'dental',
        'ecg_xray' => 'ecg',
        'optha_eent' => 'ocular',
        'np' => 'psych',
        'lab_drugtest' => 'lab',
        'gpe' => 'gpe',
        default => null,
    };
    $patientsPayload = $patients->map(function ($patient) use (
        $resolveAsset,
        $displayOverall,
        $statusClass,
        $statusIcon,
        $categoryStatus,
        $stations,
        $station,
        $stationConfig,
        $assessmentType
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

        $allStationsGo = true;
        foreach ($stations as $key => $config) {
            $eval = $patient->applications->first()?->latestExamination()?->clearances->where('station.station_key', $key)->first();
            $approvalVal = $eval ? $eval->approval_status : '';
            $approvalValUpper = trim(strtoupper($approvalVal));
            if ($approvalValUpper !== 'GO') {
                $allStationsGo = false;
            }
        }

        $stationEval = $patient->applications->first()?->latestExamination()?->clearances->where('station.station_key', $station)->first();

        return [
            'patient_no' => (string) $patient->patient_id,
            'firstName' => $patient->first_name ?? '',
            'middleName' => $patient->middle_name ?? '',
            'lastName' => $patient->last_name ?? '',
            'full_name' => $fullName,
            'birthdate' => $patient->birthdate ?? '',
            'age' => $patient->age ?? '',
            'sex' => $patient->sex ?? '',
            'appcode' => $patient->applications->first()?->app_code ?? '',
            'batch' => $patient->applications->first()?->batch ?? '',
            'unitorg' => $patient->serviceRecord?->unit?->unit_name ?? '',
            'photo_url' => $photoUrl,
            'overall_stat' => $patient->applications->first()?->latestExamination()?->overall_stat ?? '',
            'display_status' => $displayOverallValue,
            'status_badge_label' => $displayOverallValue,
            'status_badge_icon' => $badgeIcon,
            'status_badge_class' => $badgeClass,
            'diagnosis' => $stationEval ? $stationEval->diagnosis : '',
            'remarks' => $stationEval ? $stationEval->remarks : '',
            'status' => $categoryStatus($stationEval ? $stationEval->approval_status : ''),
            'attachment' => $stationEval && $stationEval->attachment ? asset($stationEval->attachment) : '',
            'chief_doctor_approval' => $patient->applications->first()?->latestExamination()?->chief_doctor_approval ?? '',
            'can_approve' => $allStationsGo,
            'medical_form_id' => $patient->applications->first()?->latestExamination()?->examination_id,
            'medical_form_step' => $patient->medicalForm?->step,
            'medical_form_status' => $patient->medicalForm?->status,
            'medical_form_exists' => $patient->applications->first()?->latestExamination() !== null,
            'all_assessments_completed' => $patient->applications->first()?->latestExamination()?->allAssessmentsCompleted() ?? false,
            'current_assessment_exists' => $assessmentType && $patient->medicalForm
                ? $patient->medicalForm->assessments->where('type', $assessmentType)->isNotEmpty()
                : false,
            'hearing_assessment_exists' => ($station === 'optha_eent') && $patient->medicalForm
                ? $patient->medicalForm->assessments->where('type', 'hearing')->isNotEmpty()
                : false,
            'xray_assessment_exists' => ($station === 'ecg_xray') && $patient->medicalForm
                ? $patient->medicalForm->assessments->where('type', 'xray')->isNotEmpty()
                : false,
        ];
    })->values();

    $batchOptions = $patientsPayload->pluck('batch')->filter()->unique()->sort()->values();
@endphp

@section('sidebar-nav')
<div class="nav-group-title">Medical Assessment</div>
<a href="{{ route('doctor.show', $station) }}" class="nav-item active">
    <i class="fas fa-heartbeat"></i> {{ $stationConfig['label'] ?? strtoupper($station) }} Evaluation
</a>
<a href="{{ route('doctor.scanner', $station) }}" class="nav-item">
    <i class="fas fa-qrcode"></i> QR Scanner
</a>
@endsection

@section('header-title', ($stationConfig['label'] ?? strtoupper($station)) . ' Doctor Dashboard')

@section('header-actions')
@endsection

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
                    <div style="flex: 1; min-width: 180px;">
                        <select id="globalStatusFilter" class="select-modern">
                            <option value="All" selected>Status: All</option>
                            <option value="GO">GO</option>
                            <option value="NO GO">NO GO</option>
                            <option value="COMPLIANCE">COMPLIANCE</option>
                            <option value="ONGOING">ONGOING</option>
                        </select>
                    </div>
                    <div style="flex: 1; min-width: 240px; position: relative;">
                        <i class="fas fa-search" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
                        <input type="text" id="searchInput" class="input-modern" style="padding-left: 40px;" placeholder="Search Applicant">
                    </div>
                </div>
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

{{-- Upload Modal --}}
<div id="uploadModal" class="modal">
    <div class="modal-content" style="background:var(--white);border-radius:var(--radius);max-width:600px;width:90%;padding:30px;box-sizing:border-box;">
        <span class="close" onclick="closeUploadModal()" style="color:#aaa;font-size:28px;font-weight:bold;cursor:pointer;float:right;">&times;</span>
        <h2 style="margin-top:0;color:var(--primary-color);">Upload {{ strtoupper($stationConfig['label'] ?? $station) }} Attachment</h2>
        <form id="uploadForm" enctype="multipart/form-data">
            <input type="hidden" id="upload_patient_no" name="patient_no">
            <div style="margin:15px 0;padding:10px;border:1px dashed #ddd;border-radius:5px;background:#f9f9f9;">
                <label for="{{ $stationConfig['upload_input'] }}">Select file to upload: <span style="color:red;">*</span></label>
                <input type="file" id="{{ $stationConfig['upload_input'] }}" name="{{ $stationConfig['upload_input'] }}"
                    required accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" class="input-modern" style="margin-top:8px;">
                <p style="font-size:12px;color:#666;margin-top:5px;">Allowed: PDF, DOC, DOCX, JPG, JPEG, PNG (max 5MB)</p>
            </div>
            <button type="button" onclick="uploadAttachment()" class="btn btn-primary">Upload</button>
        </form>
    </div>
</div>

<div id="imageModal" class="modal image-modal">
    <span class="close image-modal-close" onclick="closeImageModal()">&times;</span>
    <img id="modalImg" alt="Applicant photo">
</div>

<!-- Action Modal (Station Evaluation) -->
<div id="actionModal" class="modal">
    <div class="modal-content" style="background:var(--white);border-radius:var(--radius);max-width:600px;width:90%;padding:30px;box-sizing:border-box;">
        <span class="close" onclick="closeActionModal()" style="color:#aaa;font-size:28px;font-weight:bold;cursor:pointer;float:right;">&times;</span>
        <h2 id="actionModalTitle" style="margin-top:0;color:var(--primary-color);"></h2>
        <form id="actionForm">
            <input type="hidden" id="action_patient_no" name="patient_no">
            <div style="margin:15px 0;">
                <label for="action_status" style="display:block;margin-bottom:5px;font-weight:600;">Status</label>
                <select id="action_status" name="status" class="select-modern" style="width:100%;">
                    <option value="GO">GO</option>
                    <option value="NO GO">NO GO</option>
                    <option value="COMPLIANCE">COMPLIANCE</option>
                </select>
            </div>
            <div style="margin:15px 0;">
                <label for="action_diagnosis" style="display:block;margin-bottom:5px;font-weight:600;">Diagnosis</label>
                <textarea id="action_diagnosis" name="diagnosis" class="input-modern" style="width:100%;min-height:100px;"></textarea>
            </div>
            <div style="margin:15px 0;">
                <label for="action_remarks" style="display:block;margin-bottom:5px;font-weight:600;">Remarks</label>
                <textarea id="action_remarks" name="remarks" class="input-modern" style="width:100%;min-height:100px;"></textarea>
            </div>
            <div style="margin-top:20px;display:flex;gap:10px;justify-content:flex-end;">
                <button type="button" onclick="closeActionModal()" class="btn btn-secondary">Cancel</button>
                <button type="button" onclick="submitAction()" class="btn btn-primary">Submit</button>
            </div>
        </form>
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
    .status-pending { background: #e0f2fe; color: #0284c7; }
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

    .station-card {
        border: 1px solid var(--border-color);
        border-radius: 12px;
        overflow: hidden;
        background: rgba(255, 255, 255, 0.4);
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
<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
const patients = @json($patientsPayload);
const stationUpdateUrl = '{{ route('api.doctor.update-field', ['station' => $station]) }}';
const stationClearUrl = '{{ route('api.doctor.clear-status', ['station' => $station]) }}';
const stationUploadUrl = '{{ route('api.station.upload', ['station' => $station]) }}';
const stationLabel = '{{ $stationConfig['label'] ?? $station }}';
const approvalField = '{{ $stationConfig['approval'] }}';
const diagnosisField = '{{ $stationConfig['diagnosis'] }}';
const remarksField = '{{ $stationConfig['remarks'] }}';
const stationKey = @json($station);
const assessmentType = @json($assessmentType);
const medicalFormStartBaseUrl = '{{ url('/medical-form/start') }}';
const medicalFormEditBaseUrl = '{{ url('/medical-form/edit') }}';
const medicalFormPdfBaseUrl = '{{ url('/medical-form/pdf') }}';
const medicalFormAssessmentBaseUrl = assessmentType ? '{{ url('/medical-form/assessment') }}' + '/' + assessmentType : null;
const medicalFormHearingAssessmentBaseUrl = '{{ url('/medical-form/assessment/hearing') }}';
const medicalFormXrayAssessmentBaseUrl = '{{ url('/medical-form/assessment/xray') }}';

const state = {
    batch: 'All',
    status: 'All',
    search: '',
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
        .replace(/'/g, '&#39;');
}

function getPatient(patientNo = state.selectedPatientNo) {
    return patients.find(p => p.patient_no === String(patientNo)) ?? null;
}

function getFilteredPatients() {
    let result = [...patients];

    if (state.batch !== 'All') {
        result = result.filter(p => p.batch === state.batch);
    }

    if (state.status !== 'All') {
        result = result.filter(p => (p.status?.text || 'ONGOING') === state.status);
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

    result.sort((a, b) => Number(b.patient_no) - Number(a.patient_no));

    return result;
}

function renderQueue() {
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
                    <div style="margin-top:8px;">Try changing the batch filter or search term.</div>
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
                        <span class="status-badge-chief ${escapeHtml(p.status.class)}">
                            ${p.status.icon || ''}
                            <span>${escapeHtml(p.status.text)}</span>
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
                                <div class="info-label">Applicant Code</div>
                                <div class="info-value">${escapeHtml(patient.appcode)}</div>
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
                                <div class="info-label">Age</div>
                                <div class="info-value">${escapeHtml(patient.age)}</div>
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

            <section class="details-section">
                <div class="details-section-header">${escapeHtml(stationLabel)} Evaluation</div>
                <div class="details-section-body">
                    <div class="info-grid" style="grid-template-columns: 1fr;">
                        <div class="info-item">
                            <div class="info-label">Status</div>
                            <div class="info-value"><span class="status-badge-chief ${escapeHtml(patient.status.class)}">${patient.status.icon || ''} <span>${escapeHtml(patient.status.text)}</span></span></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Diagnosis</div>
                            <div class="info-value">
                                ${escapeHtml(patient.diagnosis) || '-'}
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Remarks</div>
                            <div class="info-value">
                                ${escapeHtml(patient.remarks) || '-'}
                            </div>
                        </div>
                        ${patient.attachment ? `
                            <div class="info-item" style="grid-column: 1 / -1;">
                                <div class="info-label">Attachment</div>
                                <div class="info-value">
                                    <a href="${escapeHtml(patient.attachment)}" target="_blank" style="display:inline-flex;align-items:center;gap:6px;color:var(--primary-color);text-decoration:underline;">
                                        <i class="fas fa-download"></i>
                                        ${escapeHtml(patient.attachment.split('/').pop())}
                                    </a>
                                </div>
                            </div>
                        ` : ''}
                    </div>
                    <div style="margin-top: 16px; display: flex; gap: 10px; flex-wrap: wrap;">
                        ${patient.overall_stat === 'NOT DONE' && patient.chief_doctor_approval !== 'PASSED' && patient.chief_doctor_approval !== 'FAILED'
                            ? (patient.status?.text && patient.status.text !== 'ONGOING'
                                ? `
                                    <button type="button" class="btn btn-outline-soft" onclick="returnStatus('${patient.patient_no}')">
                                        <i class="fas fa-rotate-left"></i> Return Applicant
                                    </button>
                                `
                                : `
                                    <button type="button" class="btn btn-success" style="background:#22c55e;color:#fff;" onclick="showActionModal('${patient.patient_no}', 'GO')">
                                        <i class="fas fa-check-circle"></i> GO
                                    </button>
                                    <button type="button" class="btn btn-danger-solid" onclick="showActionModal('${patient.patient_no}', 'NO GO')">
                                        <i class="fas fa-times-circle"></i> NO GO
                                    </button>
                                    <button type="button" class="btn btn-warning" onclick="showActionModal('${patient.patient_no}', 'COMPLIANCE')">
                                        <i class="fas fa-clipboard-list"></i> COMPLIANCE
                                    </button>
                                `)
                            : (patient.status?.text && patient.status.text !== 'ONGOING'
                                ? `
                                    <button type="button" class="btn btn-outline-soft" disabled style="opacity:0.6;cursor:not-allowed;">
                                        <i class="fas fa-rotate-left"></i> Return Applicant
                                    </button>
                                `
                                : `
                                    <button type="button" class="btn btn-success" disabled style="opacity:0.6;cursor:not-allowed;background:#22c55e;color:#fff;">
                                        <i class="fas fa-check-circle"></i> GO
                                    </button>
                                    <button type="button" class="btn btn-danger-solid" disabled style="opacity:0.6;cursor:not-allowed;">
                                        <i class="fas fa-times-circle"></i> NO GO
                                    </button>
                                    <button type="button" class="btn btn-warning" disabled style="opacity:0.6;cursor:not-allowed;">
                                        <i class="fas fa-clipboard-list"></i> COMPLIANCE
                                    </button>
                                `)}

                        ${stationKey === 'gpe' ? `
                            ${patient.medical_form_id && (patient.medical_form_step > 1 || patient.medical_form_status === 'completed') ? `
                                <button type="button" class="btn btn-purple" onclick="openMedicalForm('${patient.medical_form_id}')">
                                    <i class="fas fa-pen-to-square"></i> Edit Redform
                                </button>
                            ` : `
                                <button type="button" class="btn btn-purple" onclick="startMedicalForm('${patient.patient_no}')">
                                    <i class="fas fa-file-circle-plus"></i> Create Redform
                                </button>
                            `}
                        ` : (assessmentType ? `
                            ${patient.current_assessment_exists ? `
                                <button type="button" class="btn btn-purple" onclick="openAssessmentForm('${patient.patient_no}', true)">
                                    <i class="fas fa-pen-to-square"></i> Edit ${stationKey === 'optha_eent' ? 'Ocular' : (stationKey === 'ecg_xray' ? 'ECG' : 'Red Form')} Assessment
                                </button>
                            ` : `
                                <button type="button" class="btn btn-purple" onclick="openAssessmentForm('${patient.patient_no}', false)">
                                    <i class="fas fa-file-circle-plus"></i> Create ${stationKey === 'optha_eent' ? 'Ocular' : (stationKey === 'ecg_xray' ? 'ECG' : 'Red Form')} Assessment
                                </button>
                            `}
                            ${stationKey === 'optha_eent' ? (patient.hearing_assessment_exists ? `
                                <button type="button" class="btn btn-purple" onclick="openHearingAssessmentForm('${patient.patient_no}', true)">
                                    <i class="fas fa-pen-to-square"></i> Edit Hearing Assessment
                                </button>
                            ` : `
                                <button type="button" class="btn btn-purple" onclick="openHearingAssessmentForm('${patient.patient_no}', false)">
                                    <i class="fas fa-file-circle-plus"></i> Create Hearing Assessment
                                </button>
                            `) : ''}
                            ${stationKey === 'ecg_xray' ? (patient.xray_assessment_exists ? `
                                <button type="button" class="btn btn-purple" onclick="openXrayAssessmentForm('${patient.patient_no}', true)">
                                    <i class="fas fa-pen-to-square"></i> Edit Chest X-Ray Assessment
                                </button>
                            ` : `
                                <button type="button" class="btn btn-purple" onclick="openXrayAssessmentForm('${patient.patient_no}', false)">
                                    <i class="fas fa-file-circle-plus"></i> Create Chest X-Ray Assessment
                                </button>
                            `) : ''}
                        ` : ``)}
                        <button type="button" class="btn btn-secondary" onclick="showUploadModal('${patient.patient_no}')">
                            <i class="fas fa-upload"></i> Upload Attachment
                        </button>
                    </div>
                </div>
            </section>
        </div>
    `;

}

// Show action modal for station evaluation
function showActionModal(patientNo, statusValue) {
    const patient = getPatient(patientNo);
    const modal = document.getElementById('actionModal');
    const titleEl = document.getElementById('actionModalTitle');

    titleEl.textContent = 'Mark Applicant';
    document.getElementById('action_patient_no').value = patientNo;
    document.getElementById('action_status').value = ['GO', 'NO GO', 'COMPLIANCE'].includes(statusValue) ? statusValue : 'GO';
    document.getElementById('action_diagnosis').value = patient?.diagnosis || '';
    document.getElementById('action_remarks').value = patient?.remarks || '';
    
    modal.style.display = 'flex';
}

// Close action modal
function closeActionModal() {
    const modal = document.getElementById('actionModal');
    modal.style.display = 'none';
    document.getElementById('actionForm').reset();
}

// Submit action (update diagnosis, remarks, and approval status)
function submitAction() {
    const patientNo = document.getElementById('action_patient_no').value;
    const approvalValue = document.getElementById('action_status').value;
    const diagnosis = document.getElementById('action_diagnosis').value;
    const remarks = document.getElementById('action_remarks').value;

    // Update diagnosis
    const fdDiagnosis = new FormData();
    fdDiagnosis.append('patient_no', patientNo);
    fdDiagnosis.append('field', 'diagnosis');
    fdDiagnosis.append('value', diagnosis);

    const xhrDiagnosis = new XMLHttpRequest();
    xhrDiagnosis.open('POST', stationUpdateUrl, true);
    xhrDiagnosis.setRequestHeader('X-CSRF-TOKEN', csrfToken);
    xhrDiagnosis.setRequestHeader('Accept', 'application/json');

    // Update remarks
    const fdRemarks = new FormData();
    fdRemarks.append('patient_no', patientNo);
    fdRemarks.append('field', 'remarks');
    fdRemarks.append('value', remarks);

    const xhrRemarks = new XMLHttpRequest();
    xhrRemarks.open('POST', stationUpdateUrl, true);
    xhrRemarks.setRequestHeader('X-CSRF-TOKEN', csrfToken);
    xhrRemarks.setRequestHeader('Accept', 'application/json');

    // Update approval (status)
    const fdApproval = new FormData();
    fdApproval.append('patient_no', patientNo);
    fdApproval.append('field', 'action');
    fdApproval.append('value', approvalValue);

    const xhrApproval = new XMLHttpRequest();
    xhrApproval.open('POST', stationUpdateUrl, true);
    xhrApproval.setRequestHeader('X-CSRF-TOKEN', csrfToken);

    let completed = 0;
    const total = 3;
    let hasError = false;

    const checkDone = (status) => {
        completed++;
        if (status !== 200 && status !== 201) {
            hasError = true;
        }

        if (completed === total) {
            if (hasError) {
                alert('An error occurred while saving the data. Please refresh and try again.');
                return;
            }

            // Update local patient data
            const patient = patients.find(p => p.patient_no == patientNo);
            if (patient) {
                patient.diagnosis = diagnosis;
                patient.remarks = remarks;
                patient.status = getCategoryStatus(approvalValue);
            }
            closeActionModal();
            renderDetails();
            renderQueue();
            alert('Patient updated successfully!');
        }
    };

    xhrDiagnosis.onload = () => checkDone(xhrDiagnosis.status);
    xhrRemarks.onload = () => checkDone(xhrRemarks.status);
    xhrApproval.onload = () => checkDone(xhrApproval.status);

    xhrDiagnosis.onerror = () => alert('Network error updating diagnosis.');
    xhrRemarks.onerror = () => alert('Network error updating remarks.');
    xhrApproval.onerror = () => alert('Network error updating approval status.');

    xhrDiagnosis.send(fdDiagnosis);
    xhrRemarks.send(fdRemarks);
    xhrApproval.send(fdApproval);
}

function startMedicalForm(patientNo) {
    window.location.href = `${medicalFormStartBaseUrl}/${patientNo}`;
}

function openMedicalForm(medicalFormId) {
    window.location.href = `${medicalFormEditBaseUrl}/${medicalFormId}`;
}

function openAssessmentForm(patientNo, isEdit = false) {
    if (!medicalFormAssessmentBaseUrl) {
        alert('Assessment form is not available for this station.');
        return;
    }

    const url = isEdit
        ? `${medicalFormAssessmentBaseUrl}/${patientNo}/edit`
        : `${medicalFormAssessmentBaseUrl}/${patientNo}`;

    window.location.href = url;
}

function openHearingAssessmentForm(patientNo, isEdit = false) {
    const url = isEdit
        ? `${medicalFormHearingAssessmentBaseUrl}/${patientNo}/edit`
        : `${medicalFormHearingAssessmentBaseUrl}/${patientNo}`;

    window.location.href = url;
}

function openXrayAssessmentForm(patientNo, isEdit = false) {
    const url = isEdit
        ? `${medicalFormXrayAssessmentBaseUrl}/${patientNo}/edit`
        : `${medicalFormXrayAssessmentBaseUrl}/${patientNo}`;

    window.location.href = url;
}

function returnStatus(patientNo) {
    if (!confirm('Are you sure you want to return this applicant to ONGOING for this station?')) {
        return;
    }

    const fd = new FormData();
    fd.append('patient_no', patientNo);

    const xhr = new XMLHttpRequest();
    xhr.open('POST', stationClearUrl, true);
    xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
    xhr.onload = function () {
        if (xhr.status < 200 || xhr.status >= 300) {
            alert('Error returning applicant.');
            return;
        }

        const patient = patients.find(p => p.patient_no === patientNo);
        if (patient) {
            patient.diagnosis = '';
            patient.remarks = '';
            patient.status = getCategoryStatus('');
        }

        renderAll();
        alert('Applicant returned successfully!');
    };
    xhr.onerror = function () {
        alert('Error returning applicant.');
    };
    xhr.send(fd);
}

// Close action modal when clicking outside
window.addEventListener('click', function(e) {
    const modal = document.getElementById('actionModal');
    if (e.target === modal) {
        closeActionModal();
    }
});

function getCategoryStatus(value) {
    const val = (value || '').trim().toUpperCase();
    const statusMap = {
        'GO': { text: 'GO', class: 'status-approved', icon: '<i class="fas fa-check-circle" style="color: #16a34a;"></i>' },
        'NO GO': { text: 'NO GO', class: 'status-disqualified', icon: '<i class="fas fa-times-circle" style="color: #dc2626;"></i>' },
        'DQ': { text: 'NO GO', class: 'status-disqualified', icon: '<i class="fas fa-times-circle" style="color: #dc2626;"></i>' },
        'COMPLIANCE': { text: 'COMPLIANCE', class: 'status-compliance', icon: '<i class="fas fa-clipboard-list" style="color: #ca8a04;"></i>' },
        'WAITING FOR RESULT': { text: 'WAITING FOR RESULT', class: 'status-compliance', icon: '<i class="fas fa-clock" style="color: #ca8a04;"></i>' },
    };
    return statusMap[val] || { text: 'ONGOING', class: 'status-pending', icon: '<i class="fas fa-hourglass-half" style="color: #64748b;"></i>' };
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

window.addEventListener('click', function (e) {
    if (e.target === document.getElementById('imageModal')) {
        closeImageModal();
    }
});

/* Upload Modal */
function showUploadModal(patientNo) {
    document.getElementById('upload_patient_no').value = patientNo;
    document.getElementById('uploadModal').style.display = 'flex';
}
function closeUploadModal() {
    document.getElementById('uploadModal').style.display = 'none';
    document.getElementById('uploadForm').reset();
}
function uploadAttachment() {
    const patientNo = document.getElementById('upload_patient_no').value;
    const fileInput = document.getElementById('{{ $stationConfig['upload_input'] }}');
    const file = fileInput.files[0];
    if (!file) { alert('Please select a file.'); return; }
    if (file.size > 5 * 1024 * 1024) { alert('File size must be less than 5MB.'); return; }

    const fd = new FormData();
    fd.append('patient_no', patientNo);
    fd.append('{{ $stationConfig['upload_input'] }}', file);

    const xhr = new XMLHttpRequest();
    xhr.open('POST', stationUploadUrl, true);
    xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.onload = function() {
        if (xhr.status === 200 || xhr.status === 201) {
            try {
                const resp = JSON.parse(xhr.responseText);
                if (resp.status === 'success') {
                    alert('File uploaded successfully!');
                    closeUploadModal();
                    location.reload();
                } else {
                    alert('Error: ' + (resp.message || 'Upload failed.'));
                }
            } catch (e) { alert('Error parsing response.'); }
        } else if (xhr.status === 422) {
            try {
                const resp = JSON.parse(xhr.responseText);
                let msgs = [];
                if (resp.errors) {
                    for (let key in resp.errors) {
                        msgs.push(resp.errors[key].join(' '));
                    }
                }
                alert('Validation Error: ' + (msgs.join('\\n') || resp.message) + '\\n\\n(If this persists, your file might exceed the server php.ini upload limits)');
            } catch (e) {
                alert('Validation error. Please check the file type and ensure it is under 5MB.');
            }
        } else {
            alert('Error uploading file. Server returned: ' + xhr.status);
        }
    };
    xhr.onerror = function() { alert('Network error.'); };
    xhr.send(fd);
}

// Initialize filters
document.getElementById('globalBatchFilter').addEventListener('change', function () {
    state.batch = this.value;
    state.page = 1;
    renderAll();
});

document.getElementById('globalStatusFilter').addEventListener('change', function () {
    state.status = this.value;
    state.page = 1;
    renderAll();
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
            (p.patient_no && p.patient_no.toString().includes(searchVal)) ||
            (p.appcode && p.appcode.toLowerCase().includes(searchVal.toLowerCase()))
        );
        if (firstPatient) {
            selectPatient(firstPatient);
        }
    }, 100);
}

renderAll();
</script>
@endpush

