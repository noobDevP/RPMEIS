@extends('layouts.portal')

@section('title', 'Retrieved Patient Data - Station Portal')

@section('sidebar-nav')
<div class="nav-group-title">Station Control</div>
<a href="{{ route('station.show', ['station' => $station]) }}" class="nav-item {{ request()->routeIs('station.show') ? 'active' : '' }}">
    <i class="fas fa-qrcode"></i> QR Scanner
</a>
<a href="{{ route('station.processed', ['station' => $station]) }}" class="nav-item {{ request()->routeIs('station.processed') ? 'active' : '' }}">
    <i class="fas fa-users"></i> Retrieved Patient Data
</a>
@endsection

@section('header-title', ($stationConfig['label'] ?? strtoupper($station)) . ' Station Dashboard - Retrieved Data')

@section('header-actions')
@endsection

@php
    if (!isset($patientsPayload, $batchOptions)) {
        ['patientsPayload' => $patientsPayload, 'batchOptions' => $batchOptions] = app(\App\Services\PatientTablePayloadService::class)
            ->buildDirectoryData($patients, ['mode' => 'station']);
    }
@endphp

@section('content')
<div class="spec-dashboard">
    <aside class="card queue-panel">
        <div class="queue-header">
            <div class="panel-title">Applicants</div>
            @include('partials.patient-filters-toggle', ['targetId' => 'stationProcessedFilters', 'storageKey' => 'station_processed'])
        </div>

        <div id="stationProcessedFilters" class="filter-collapsible-body is-collapsed" data-filter-body>
            <div class="patient-sidebar-controls" style="padding: 12px 20px 0 20px; border-bottom: none;">
                <div class="control-group">
                    <div style="display:flex; gap: 12px; flex-wrap: wrap;">
                        <div style="flex: 1; min-width: 180px;">
                            <select id="globalBatchFilter" class="select-modern">
                                <option value="All">Batch: All</option>
                                @foreach ($batchOptions as $batch)
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
                    </select>
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
            <div class="panel-title">Applicant Details</div>
            <div id="selectedApplicantMeta" class="panel-subtitle">Select an applicant from the queue.</div>
        </div>

        <div id="detailsContent" class="details-content"></div>
    </section>
</div>

<div id="imageModal" class="modal image-modal">
    <span class="close image-modal-close" onclick="closeImageModal()">&times;</span>
    <img id="modalImg" alt="Applicant photo">
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
            <div class="empty-panel">
                <div>
                    <strong>No matching applicants.</strong>
                    <div style="margin-top:8px;">Try changing the status filter, batch filter or search term.</div>
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
                    <div style="margin-top:8px;">Pick an applicant from the left queue to view details.</div>
                </div>
            </div>
        `;
        return;
    }

    meta.innerHTML = `${escapeHtml(patient.full_name)} • Patient No ${escapeHtml(patient.patient_no)}`;

    let stationCardsHtml = '';
    const currentStationKey = @json($station);
    if (patient.stations[currentStationKey]) {
        const station = patient.stations[currentStationKey];
        stationCardsHtml += `
            <div class="station-card">
                <div class="station-card-header">${escapeHtml(station.label)}</div>
                <div class="station-card-body">
                    <div class="info-grid" style="grid-template-columns: 1fr;">
                        <div class="info-item">
                            <div class="info-label">Status</div>
                            <div class="info-value"><span class="status-badge-chief ${escapeHtml(station.status.class)}">${escapeHtml(station.status.text)}</span></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Diagnosis</div>
                            <div class="info-value">${escapeHtml(station.diagnosis) || '-'}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Remarks</div>
                            <div class="info-value">${escapeHtml(station.remarks) || '-'}</div>
                        </div>
                        ${station.attachment ? `
                            <div class="info-item">
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
            </div>
        `;
    }

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
                <div class="details-section-header">Station Evaluations</div>
                <div class="details-section-body">
                    <div class="station-grid">
                        ${stationCardsHtml}
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

window.addEventListener('click', function (e) {
    if (e.target === document.getElementById('imageModal')) {
        closeImageModal();
    }
});

// Initialize filters
document.getElementById('globalBatchFilter').addEventListener('change', function () {
    state.batch = this.value;
    state.page = 1;
    renderAll();
});

document.getElementById('queueStatusFilter').addEventListener('change', function () {
    state.statusFilter = this.value;
    state.page = 1;

    let url = '{{ route('station.processed', ['station' => $station]) }}';
    if (state.statusFilter !== 'All') {
        const filterValue =
            state.statusFilter === 'Ongoing' ? 'ongoing' :
            state.statusFilter === 'Cleared' ? 'cleared' :
            state.statusFilter === 'Done' ? 'done' :
            state.statusFilter === 'Disqualified' ? 'disqualified' : '';
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
renderAll();
</script>
@endpush

