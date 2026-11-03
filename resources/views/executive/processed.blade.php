@extends('layouts.portal')

@section('title', 'Applicant Data - ARO Visayas')

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
        $stat = strtoupper(trim((string) $patient->applications->first()?->latestExamination()?->overall_stat ?? ''));
        if ($chiefApproval === 'FAILED') return 'Disqualified';
        if ($stat === 'COMPLETED') return 'Done';
        if ($stat === 'DONE') return 'Cleared';
        if ($stat === 'NOT DONE') return 'Ongoing';
        return 'Pending';
    };

    $statusClass = function ($patient) {
        $chiefApproval = strtoupper(trim((string) $patient->applications->first()?->latestExamination()?->chief_doctor_approval ?? ''));
        $stat = strtoupper(trim((string) $patient->applications->first()?->latestExamination()?->overall_stat ?? ''));
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
            'PASSED' => ['text' => 'PASSED', 'class' => 'status-approved'],
            'FAILED' => ['text' => 'FAILED', 'class' => 'status-disqualified'],
            default => ['text' => 'PENDING', 'class' => 'status-pending'],
        };

        return [
            'patient_no' => (string) $patient->patient_id,
            'firstName' => $patient->first_name ?? '',
            'middleName' => $patient->middle_name ?? '',
            'lastName' => $patient->last_name ?? '',
            'full_name' => $fullName,
            'birthdate' => $patient->birthdate ?? '',
            'sex' => $patient->sex ?? '',
            'batch' => $patient->applications->first()?->batch ?? '',
            'purpose' => $patient->purpose ?? '',
            'unitorg' => $patient->serviceRecord?->unit?->unit_name ?? '',
            'appcode' => $patient->applications->first()?->app_code ?? '',
            'photo_url' => $photoUrl,
            'overall_stat' => $patient->applications->first()?->latestExamination()?->overall_stat ?? '',
            'chief_doctor_approval' => $patient->applications->first()?->latestExamination()?->chief_doctor_approval ?? '',
            'display_status' => $displayOverallValue,
            'status_badge_label' => $displayOverallValue,
            'status_badge_class' => $badgeClass,
            'status_badge_icon' => $badgeIcon,
            'stations' => $stationData,
            'chief_doctor' => [
                'status' => $chiefDoctorStatus,
                'diagnosis' => $patient->doctors_diagnosis ?? '',
                'remarks' => $patient->doctors_remarks ?? '',
            ],
            'medical_form_id' => $patient->applications->first()?->latestExamination()?->examination_id,
            'medical_form_exists' => $patient->applications->first()?->latestExamination() !== null,
            'all_assessments_completed' => $patient->applications->first()?->latestExamination()?->allAssessmentsCompleted() ?? false,
        ];
    })->values();

    $batchOptions = $patientsPayload->pluck('batch')->filter()->unique()->sort()->values();
@endphp

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

@section('header-title', 'Applicant Data')

@section('header-actions')
@endsection

@section('content')
<div class="card" style="padding: 24px;">
    <div style="display: flex; gap: 16px; align-items: center; margin-bottom: 24px; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 140px;">
            <label style="display: block; margin-bottom: 6px; font-size: 0.85rem; font-weight: 600; color: var(--text-muted);">Record Type</label>
            <select id="recordTypeFilter" class="select-modern" style="width: 100%;">
                <option value="0" {{ !($archive ?? false) ? 'selected' : '' }}>Active</option>
                <option value="1" {{ ($archive ?? false) ? 'selected' : '' }}>Archived</option>
            </select>
        </div>
        <div style="flex: 1; min-width: 180px;">
            <label style="display: block; margin-bottom: 6px; font-size: 0.85rem; font-weight: 600; color: var(--text-muted);">Batch Filter</label>
            <select id="globalBatchFilter" class="select-modern" style="width: 100%;">
                <option value="All">All</option>
                @foreach ($batchOptions as $batch)
                    <option value="{{ $batch }}">{{ $batch }}</option>
                @endforeach
            </select>
        </div>
        <div style="flex: 1; min-width: 180px;">
            <label style="display: block; margin-bottom: 6px; font-size: 0.85rem; font-weight: 600; color: var(--text-muted);">Status Filter</label>
            <select id="queueStatusFilter" class="select-modern" style="width: 100%;">
                <option value="All">All</option>
                <option value="Ongoing" {{ $filter === 'ongoing' ? 'selected' : '' }}>Ongoing</option>
                <option value="Cleared" {{ $filter === 'cleared' ? 'selected' : '' }}>Cleared</option>
                <option value="Done" {{ $filter === 'done' ? 'selected' : '' }}>Done</option>
                <option value="Disqualified" {{ $filter === 'disqualified' ? 'selected' : '' }}>Disqualified</option>
            </select>
        </div>
        <div style="flex: 2; min-width: 240px;">
            <label style="display: block; margin-bottom: 6px; font-size: 0.85rem; font-weight: 600; color: var(--text-muted);">Search Applicant</label>
            <div style="position: relative;">
                <i class="fas fa-search" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
                <input type="text" id="searchInput" class="input-modern" style="padding-left: 40px; width: 100%;" placeholder="Name, Code, or Batch">
            </div>
        </div>
    </div>
    
    <div style="overflow-x: auto; border-radius: 12px; border: 1px solid var(--border-color);">
        <table class="table-modern" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: rgba(0,0,0,0.03); border-bottom: 2px solid var(--border-color); text-align: left;">
                    <th style="padding: 16px; font-weight: 700; color: var(--text-main);">Name</th>
                    <th style="padding: 16px; font-weight: 700; color: var(--text-main);">App Code</th>
                    <th style="padding: 16px; font-weight: 700; color: var(--text-main);">Batch</th>
                    <th style="padding: 16px; font-weight: 700; color: var(--text-main);">Purpose</th>
                    <th style="padding: 16px; font-weight: 700; color: var(--text-main);">Decision</th>
                </tr>
            </thead>
            <tbody id="queueList">
            </tbody>
        </table>
    </div>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px;">
        <div id="queueSummary" class="queue-summary" style="font-size: 0.95rem; color: var(--text-muted); font-weight: 500;">Showing 0-0 of 0</div>
        <div id="queuePagination" class="queue-pagination" style="display: flex; gap: 8px;"></div>
    </div>
</div>

<!-- Specialist Modal -->
<div id="specialistModal" class="modal">
    <div style="background: var(--white); margin: auto; padding: 30px; border-radius: 16px; width: 90%; max-width: 900px; max-height: 85vh; overflow-y: auto; position: relative;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid var(--border-color); padding-bottom: 20px; margin-bottom: 20px;">
            <div>
                <h2 style="margin: 0; font-size: 1.5rem; color: var(--text-main);" id="modalApplicantName">Applicant Details</h2>
                <div style="color: var(--text-muted); font-size: 0.9rem; margin-top: 4px;" id="modalApplicantMeta"></div>
            </div>
            <span class="close" onclick="closeSpecialistModal()" style="font-size: 28px; cursor: pointer; color: var(--text-muted); line-height: 1;">&times;</span>
        </div>
        <div id="specialistModalContent">
        </div>
    </div>
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

@endsection

@push('styles')
<style>
    .hover-row:hover {
        background-color: rgba(0,0,0,0.015);
    }
    .spec-dashboard {
        display: block;
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
        flex: 0 0 auto;
        padding: 10px;
        background: transparent;
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

    .status-approved { background: #dcfce7; color: #166534; }
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

    .station-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
    }

    .station-card {
        border: 1px solid var(--border-color);
        border-radius: 12px;
        overflow: hidden;
        background: rgba(255, 255, 255, 0.4);
    }

    .station-card-header {
        padding: 12px 16px;
        background: rgba(255, 255, 255, 0.5);
        border-bottom: 1px solid var(--border-color);
        font-size: 0.85rem;
        font-weight: 700;
        color: var(--text-main);
    }

    .station-card-body {
        padding: 16px;
    }

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
        .station-grid {
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
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .replace(/'/g, '&#39;');
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
            <tr>
                <td colspan="5" style="text-align: center; padding: 40px; color: var(--text-muted);">
                    <strong>No matching applicants.</strong>
                    <div style="margin-top:8px;">Try changing the status filter, batch filter or search term.</div>
                </td>
            </tr>
        `;
    } else {
        listEl.innerHTML = pageItems.map(p => `
            <tr style="cursor: pointer; border-bottom: 1px solid var(--border-color); transition: background-color 0.2s;" class="hover-row" onclick="selectApplicant('${escapeHtml(p.patient_no)}')">
                <td style="padding: 16px; font-weight: 600; color: var(--text-main);">${escapeHtml(p.full_name)}</td>
                <td style="padding: 16px;">${escapeHtml(p.appcode || p.patient_no)}</td>
                <td style="padding: 16px;">${escapeHtml(p.batch || '-')}</td>
                <td style="padding: 16px;">${escapeHtml(p.purpose || '-')}</td>
                <td style="padding: 16px;">
                    <span class="status-badge-chief ${escapeHtml(p.status_badge_class)}">
                        ${p.status_badge_icon}
                        <span>${escapeHtml(p.status_badge_label)}</span>
                    </span>
                </td>
            </tr>
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



function renderAll() {
    renderQueue();
}

function selectApplicant(patientNo) {
    const patient = getPatient(patientNo);
    if (!patient) return;
    
    document.getElementById('modalApplicantName').textContent = patient.full_name;
    document.getElementById('modalApplicantMeta').textContent = 'App Code: ' + (patient.appcode || patient.patient_no) + ' • Batch: ' + (patient.batch || '-');
    
    let stationCardsHtml = '';
    Object.keys(patient.stations).forEach(key => {
        const station = patient.stations[key];
        stationCardsHtml += `
            <div style="border: 1px solid var(--border-color); border-radius: 12px; margin-bottom: 16px; overflow: hidden;">
                <div style="background: rgba(0,0,0,0.03); padding: 12px 16px; font-weight: 700; color: var(--text-main); border-bottom: 1px solid var(--border-color);">${escapeHtml(station.label)}</div>
                <div style="padding: 16px; display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 16px;">
                    <div>
                        <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Decision</div>
                        <div style="margin-top: 6px;"><span class="status-badge-chief ${escapeHtml(station.status.class)}">${escapeHtml(station.status.text)}</span></div>
                    </div>
                    <div>
                        <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Diagnosis</div>
                        <div style="margin-top: 6px; color: var(--text-main);">${escapeHtml(station.diagnosis) || '-'}</div>
                    </div>
                    <div>
                        <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Remarks</div>
                        <div style="margin-top: 6px; color: var(--text-main);">${escapeHtml(station.remarks) || '-'}</div>
                    </div>
                </div>
            </div>
        `;
    });

    const downloadBtnHtml = (patient.medical_form_id && patient.medical_form_exists && patient.all_assessments_completed) ? `
        <a href="/medical-form/pdf/${patient.medical_form_id}" target="_blank" class="btn btn-primary" style="display: flex; justify-content: center; align-items: center; padding: 12px; font-size: 1.1rem; gap: 8px; border-radius: 10px;">
            <i class="fas fa-file-pdf"></i> Download Redform
        </a>
    ` : `
        <button type="button" class="btn btn-secondary" style="display: flex; width: 100%; justify-content: center; align-items: center; padding: 12px; font-size: 1.1rem; gap: 8px; border-radius: 10px; cursor: not-allowed; opacity: 0.6;" disabled title="Red Form not created or specialist assessments incomplete">
            <i class="fas fa-file-pdf"></i> Download Redform
        </button>
    `;

    document.getElementById('specialistModalContent').innerHTML = `
        <div style="margin-bottom: 24px;">
            <div style="border: 2px solid var(--primary-color); border-radius: 12px; margin-bottom: 16px; overflow: hidden; background: #f8fafc;">
                <div style="background: var(--primary-color); padding: 12px 16px; font-weight: 700; color: white; display:flex; justify-content: space-between; align-items: center;">
                    <div>Chief Doctor Evaluation</div>
                </div>
                <div style="padding: 16px; display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 16px;">
                    <div>
                        <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Decision</div>
                        <div style="margin-top: 6px;"><span class="status-badge-chief ${escapeHtml(patient.chief_doctor.status.class)}">${escapeHtml(patient.chief_doctor.status.text)}</span></div>
                    </div>
                    <div>
                        <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Diagnosis</div>
                        <div style="margin-top: 6px; color: var(--text-main); font-weight: 500;">${escapeHtml(patient.chief_doctor.diagnosis) || '-'}</div>
                    </div>
                    <div>
                        <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Remarks</div>
                        <div style="margin-top: 6px; color: var(--text-main); font-weight: 500;">${escapeHtml(patient.chief_doctor.remarks) || '-'}</div>
                    </div>
                </div>
            </div>
            ${stationCardsHtml}
        </div>
        ${downloadBtnHtml}
    `;
    
    document.getElementById('specialistModal').style.display = 'flex';
}

function closeSpecialistModal() {
    document.getElementById('specialistModal').style.display = 'none';
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
    if (e.target === document.getElementById('specialistModal')) {
        closeSpecialistModal();
    }
});

// Show Analytics modal
document.getElementById('showAnalyticsBtn').addEventListener('click', function() {
    document.getElementById('analyticsModal').style.display = 'block';
});

function closeAnalyticsModal() {
    document.getElementById('analyticsModal').style.display = 'none';
}

// Close modal when clicking outside
window.addEventListener('click', e => {
    if (e.target === document.getElementById('analyticsModal')) closeAnalyticsModal();
});

document.getElementById('filterAnalyticsBtn').addEventListener('click', function() {
    const batch = document.getElementById('analyticsBatchSelect').value;
    const category = document.getElementById('analyticsCategorySelect').value;
    const archive = document.getElementById('analyticsRecordTypeSelect').value;
    if (!batch || !category) { alert('Please select both batch and category.'); return; }

    fetch(`/api/analytics/category-counts?batch=${encodeURIComponent(batch)}&category=${encodeURIComponent(category)}&archive=${archive}&_t=${Date.now()}`)
        .then(r => r.json())
        .then(data => {
            // Populate the visual cards with analytics data
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



// Initialize filters
document.getElementById('globalBatchFilter').addEventListener('change', function () {
    state.batch = this.value;
    state.page = 1;
    renderAll();
});

    const applyFilters = () => {
        let url = new URL('{{ route('executive.processed') }}', window.location.origin);
        
        if (state.statusFilter !== 'All') {
            const filterValue =
                state.statusFilter === 'Ongoing' ? 'ongoing' :
                state.statusFilter === 'Cleared' ? 'cleared' :
                state.statusFilter === 'Done' ? 'done' :
                state.statusFilter === 'Disqualified' ? 'disqualified' : '';
            url.searchParams.set('filter', filterValue);
        }
        
        const archiveVal = document.getElementById('recordTypeFilter').value;
        if (archiveVal === '1') {
            url.searchParams.set('archive', '1');
        }
        
        window.location.href = url.toString();
    };

    document.getElementById('queueStatusFilter').addEventListener('change', function () {
        state.statusFilter = this.value;
        applyFilters();
    });

    document.getElementById('recordTypeFilter').addEventListener('change', function () {
        applyFilters();
    });

document.getElementById('searchInput').addEventListener('input', function () {
    state.search = this.value;
    state.page = 1;
    renderQueue();
});

// Render on load
renderAll();
</script>
@endpush

