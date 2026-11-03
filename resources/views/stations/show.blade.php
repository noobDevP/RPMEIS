@extends('layouts.portal')

@section('title', 'Station Portal - QR Scanner')

@section('sidebar-nav')
<div class="nav-group-title">Station Control</div>
<a href="{{ route('station.show', ['station' => $station ?? 'dental']) }}" class="nav-item {{ request()->routeIs('station.show') ? 'active' : '' }}">
    <i class="fas fa-qrcode"></i> QR Scanner
</a>
<a href="{{ route('station.processed', ['station' => $station ?? 'dental']) }}" class="nav-item {{ request()->routeIs('station.processed') ? 'active' : '' }}">
    <i class="fas fa-users"></i> Retrieved Patient Data
</a>
@endsection

@section('header-title', ($stationConfig['label'] ?? strtoupper($station)) . ' Station Dashboard')

@section('header-actions')
@endsection

@php
    $stationColors = [
        'dental'       => '#FF9900',
        'optha_eent'   => '#00A000',
        'ecg_xray'     => '#800080',
        'lab_drugtest' => '#4B0082',
        'np'           => '#8B4513',
        'gpe'          => '#6CB4EE',
    ];
    $stationColor = $stationColors[$station] ?? '#005f73';
    $diagnosisField = $stationConfig['diagnosis'] ?? 'diagnosis';
    $remarksField = $stationConfig['remarks'] ?? 'remarks';
    $approvalField = $stationConfig['approval'] ?? 'approval';
    $uploadInputName = $stationConfig['upload_input'] ?? 'fileAttachment';
@endphp

@push('styles')
<style>
    .station-banner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
        padding: 18px 20px;
        background: rgba(255, 255, 255, 0.6);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.8);
        border-radius: 20px;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
    }
    .station-banner-title {
        font-size: 1rem;
        font-weight: 700;
        color: var(--text-main);
    }
    .station-banner-subtitle {
        margin-top: 4px;
        font-size: 0.85rem;
        color: var(--text-muted);
    }
    /* Badge styles */
    .badge-go { background-color: #22c55e; color: white; padding: 3px 8px; border-radius: 4px; font-size: 0.75rem; }
    .badge-no-go { background-color: #ef4444; color: white; padding: 3px 8px; border-radius: 4px; font-size: 0.75rem; }
    .badge-compliance { background-color: #eab308; color: black; padding: 3px 8px; border-radius: 4px; font-size: 0.75rem; }
    .badge-pending { background-color: #6b7280; color: white; padding: 3px 8px; border-radius: 4px; font-size: 0.75rem; }

    /* Details / Actions visibility */
    #detailsSection, #actionsSection { display: none; }
    #detailsSection.visible, #actionsSection.visible { display: block; }

    .details-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    @media (max-width: 768px) { .details-grid { grid-template-columns: 1fr; } }

    .details-block h4 { margin: 0 0 12px; font-size: 0.85rem; font-weight: 700; color: var(--primary-color); text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid var(--primary-color); padding-bottom: 6px; }
    .section-heading {
        display: flex;
        align-items: center;
        text-align: center;
        margin: 0;
        padding: 14px 20px;
        font-size: 0.85rem;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 1px;
        border-bottom: 1px solid var(--border-color);
        background: #fafaf9;
    }
    .section-heading span {
        flex: 1;
    }
    .section-heading strong {
        font-weight: 700;
        color: #57534e;
    }
    .scanner-shell {
        background: rgba(255, 255, 255, 0.4);
        border: 1px dashed rgba(180, 195, 210, 0.8);
        border-radius: 20px;
        padding: 24px;
        text-align: center;
        min-height: 280px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 16px;
        overflow: hidden;
    }
    .scanner-shell video {
        width: 100%;
        max-width: 520px;
        border-radius: 16px;
        background: rgba(0,0,0,0.05);
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }
    .queue-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
        padding: 16px 20px 0;
    }
    .queue-count {
        font-size: 0.82rem;
        color: var(--text-muted);
        font-weight: 600;
    }
    .record-table th {
        background: var(--primary-color);
        color: white;
        padding: 10px 12px;
        text-align: left;
        font-size: 0.8rem;
        white-space: nowrap;
    }
    .record-table td {
        padding: 8px 12px;
        border-bottom: 1px solid var(--border-color);
        font-size: 0.85rem;
        vertical-align: top;
    }
    .record-table tbody tr {
        transition: background 0.15s ease;
    }
    .record-table tbody tr:hover {
        background: #fafaf9;
    }
    .inline-actions {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
    }
    .action-chip {
        border: none;
        border-radius: 4px;
        padding: 6px 10px;
        font-size: 0.75rem;
        font-weight: 700;
        cursor: pointer;
        color: white;
    }
    .action-chip.success { background: #22c55e; }
    .action-chip.danger { background: #ef4444; }

    /* Action form grid */
    .action-form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 14px 16px;
        text-align: left;
    }
    .action-form-grid .full-width { grid-column: 1 / -1; }
    .action-form-grid label.field-label {
        display: block;
        font-size: 0.78rem;
        font-weight: 700;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        margin-bottom: 4px;
    }
    .action-form-grid textarea,
    .action-form-grid select,
    .action-form-grid input[type="text"] {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid var(--border-color);
        border-radius: var(--radius);
        font-size: 0.88rem;
        font-family: inherit;
        box-sizing: border-box;
        background: white;
    }
    .action-form-grid textarea {
        min-height: 150px;
        resize: vertical;
        border-left: 4px solid {{ $stationColor }};
    }
    .action-form-grid select {
        border-left: 4px solid {{ $stationColor }};
        cursor: pointer;
    }

    .action-form-surface {
        background: rgba(255, 255, 255, 0.4);
        border: 1px solid rgba(180, 195, 210, 0.4);
        border-radius: 16px;
        padding: 18px;
    }
    .selected-patient-bar {
        background: var(--primary-color);
        color: white;
        padding: 10px 16px;
        border-radius: var(--radius);
        font-weight: 600;
        font-size: 0.9rem;
        margin-bottom: 16px;
        display: inline-block;
    }
    /* Submit button */
    .submit-btn {
        background: #22c55e;
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: var(--radius);
        font-weight: 700;
        font-size: 0.95rem;
        cursor: pointer;
        transition: opacity 0.15s;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    .submit-btn:hover { opacity: 0.9; }
    .submit-btn:disabled { opacity: 0.6; cursor: not-allowed; }

    /* Image modal */
    #imageModal {
        display: none;
        position: fixed;
        top: 0; left: 0;
        width: 100%; height: 100%;
        background: rgba(255, 255, 255, 0.4);
        backdrop-filter: blur(8px);
        z-index: 2000;
        justify-content: center;
        align-items: center;
    }
    #imageModal img {
        max-width: 80%;
        max-height: 80%;
        border-radius: 10px;
    }

    /* Toast notification */
    #toastNotification {
        display: none;
        position: fixed;
        bottom: 24px;
        right: 24px;
        background: var(--primary-color);
        color: white;
        padding: 12px 20px;
        border-radius: var(--radius);
        font-size: 0.85rem;
        font-weight: 500;
        z-index: 3000;
        box-shadow: 0 4px 12px rgba(0,0,0,0.25);
    }

    /* Data Confirm Modal */
    .data-modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0; top: 0;
        width: 100%; height: 100%;
        background: rgba(255, 255, 255, 0.4);
        backdrop-filter: blur(8px);
        justify-content: center;
        align-items: center;
    }
    .data-modal-content {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(12px);
        margin: auto;
        padding: 24px;
        border-radius: 20px;
        width: 90%;
        max-width: 700px;
        text-align: left;
        border: 1px solid rgba(255, 255, 255, 0.5);
        box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.1);
    }

    /* Photo thumbnail */
    .patient-photo-thumb {
        width: 90px;
        height: 90px;
        object-fit: cover;
        border-radius: 50%;
        cursor: pointer;
        border: 1px solid var(--border-color);
        display: block;
        margin: 0 auto;
    }

    .table-responsive { overflow-x: auto !important; }

    @media (max-width: 768px) {
        .action-form-grid { grid-template-columns: 1fr; }
        .data-modal-content { width: 80%; }
    }
    @media (max-width: 480px) {
        .data-modal-content { width: 90%; }
    }
</style>
@endpush

@section('content')
<div style="display: flex; flex-direction: column; gap: 20px;">

    <div class="station-banner">
        <div>
            <div class="station-banner-title">{{ $stationConfig['label'] ?? strtoupper($station) }} Station Workspace</div>
            <div class="station-banner-subtitle">Use the registrar-style scanner flow to validate applicants, review details, and submit station results.</div>
        </div>
    </div>

    {{-- QR Scanner Card --}}
    <div class="card">
        <div class="card-header">QR Scanner</div>
        <div class="card-body">
            <div class="scanner-shell">
                <video id="video" autoplay playsinline></video>
                <p id="result" style="color:#a8a29e; font-size:0.9rem; margin: 0;">Scan a QR code to auto-load a patient record.</p>
                <button id="toggleCameraBtn" type="button" class="btn btn-sm" style="background-color: var(--primary-color); color: white;">
                    <i class="fas fa-camera"></i> Disable Camera
                </button>
            </div>
        </div>
    </div>

    {{-- Scanned Applicant Information --}}
    <div class="card">
        <div class="section-heading"><span></span><strong>Scanned Applicants</strong><span></span></div>
        <div class="queue-toolbar">
            <div class="queue-count">Scanned records will appear here before you load the full patient details.</div>
        </div>
        <div class="card-body" style="padding: 0;">
            <div class="table-responsive" style="overflow-y: hidden;">
                <table class="record-table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Applicant No.</th>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="applicantInfo"></tbody>
                </table>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border-top:1px solid var(--border-color);font-size:0.82rem;color:#78716c;flex-wrap:wrap;gap:8px;">
                <span style="font-weight:500;" id="scanPageInfo">Showing 0 of 0</span>
                <div style="display:flex;gap:4px;align-items:center;" id="scanPageButtons"></div>
            </div>
        </div>
    </div>

    {{-- Details Section --}}
    <div class="card" id="detailsSection">
        <div class="section-heading"><span></span><strong>Details</strong><span></span></div>
        <div class="card-body">
            <div class="details-grid">
                {{-- Left Column: Patient Details --}}
                <div class="details-block">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                        <h4 style="margin:0;">Patient Details</h4>
                        <img id="detail-photo" src="{{ asset('images/blank.jpg') }}" alt="Applicant Photo" style="width:68px;height:68px;border-radius:10px;object-fit:cover;border:2px solid var(--border-color);box-shadow:0 2px 6px rgba(0,0,0,0.08);cursor:pointer;" onclick="enlargeImage(this)">
                    </div>
                    <div style="display: grid; grid-template-columns: 130px 1fr; gap: 6px 12px;">
                        <span style="font-weight: 600; color: #57534e;">Patient No.</span><span id="detail-patient-no">-</span>
                        <span style="font-weight: 600; color: #57534e;">Applicant Code</span><span id="detail-appcode">-</span>
                        <span style="font-weight: 600; color: #57534e;">First Name</span><span id="detail-firstname">-</span>
                        <span style="font-weight: 600; color: #57534e;">Middle Name</span><span id="detail-middlename">-</span>
                        <span style="font-weight: 600; color: #57534e;">Last Name</span><span id="detail-lastname">-</span>
                        <span style="font-weight: 600; color: #57534e;">Birthdate</span><span id="detail-birthdate">-</span>
                        <span style="font-weight: 600; color: #57534e;">Age</span><span id="detail-age">-</span>
                        <span style="font-weight: 600; color: #57534e;">Sex</span><span id="detail-sex">-</span>
                        <span style="font-weight: 600; color: #57534e;">Batch</span><span id="detail-batch">-</span>
                    </div>
                </div>

                {{-- Right Column: Examination Information --}}
                <div class="details-block">
                    <h4>Examination Information</h4>
                    <div style="display: grid; grid-template-columns: 130px 1fr; gap: 6px 12px;">
                        <span style="font-weight: 600; color: #57534e;">Place of Birth</span><span id="detail-placeOfBirth">-</span>
                        <span style="font-weight: 600; color: #57534e;">Current Address</span><span id="detail-currentAddress">-</span>
                        <span style="font-weight: 600; color: #57534e;">Place of Examination</span><span id="detail-placeOfExamination">-</span>
                        <span style="font-weight: 600; color: #57534e;">Date of Examination</span><span id="detail-dateOfExamination">-</span>
                        <span style="font-weight: 600; color: #57534e;">Purpose</span><span id="detail-purpose">-</span>
                        <span style="font-weight: 600; color: #57534e;">Race</span><span id="detail-race">-</span>
                        <span style="font-weight: 600; color: #57534e;">Unit Org.</span><span id="detail-unitorg">-</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Actions Section --}}
    <div class="card" id="actionsSection">
        <div class="section-heading"><span></span><strong>Actions</strong><span></span></div>
        <div class="card-body" style="text-align: center;">
            <div id="selectedPatientBar" class="selected-patient-bar">
                SELECTED PATIENT: #----
            </div>

            <form id="stationActionForm" onsubmit="event.preventDefault(); submitData(this.querySelector('button[type=submit]'));" enctype="multipart/form-data">
                <input type="hidden" id="form-patient-no" name="patient_no">
                <div class="action-form-surface">
                    <div class="action-form-grid">
                    {{-- Diagnosis --}}
                    <div>
                        <label class="field-label" for="form-diagnosis">Diagnosis <span style="color:red">*</span></label>
                        <textarea id="form-diagnosis" name="diagnosis" class="input-modern diagnosis" placeholder="Enter diagnosis..."></textarea>
                    </div>

                    {{-- Remarks --}}
                    <div>
                        <label class="field-label" for="form-remarks">Remarks <span style="color:red">*</span></label>
                        <textarea id="form-remarks" name="remarks" class="input-modern remarks" placeholder="Enter remarks..."></textarea>
                    </div>

                    {{-- Approval Status --}}
                    <div>
                        <label class="field-label" for="form-approval">Approval Status <span style="color:red">*</span></label>
                        <select id="form-approval" name="action" class="select-modern approvalStatus" required>
                            <option value="">-- Select Status --</option>
                            <option value="GO">GO</option>
                            <option value="NO GO">NO GO</option>
                            <option value="COMPLIANCE">COMPLIANCE</option>
                        </select>
                    </div>

                    {{-- Submission Button --}}
                    <div class="full-width" style="text-align: center;">
                        <button type="submit" class="submit-btn" id="submitStationBtn">
                            <i class="fas fa-check"></i> Submit
                        </button>
                    </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

</div>

{{-- Data Confirm Modal --}}
<div id="dataModal" class="data-modal">
    <div class="data-modal-content" style="background:var(--white);border-radius:var(--radius);">
        <span class="close" onclick="closeDataModal()" style="color:#aaa;font-size:28px;font-weight:bold;cursor:pointer;float:right;">&times;</span>
        <h3 style="margin-top:0;color:var(--primary-color);">Confirm Scanned Data</h3>
        <p id="modalData" style="white-space: pre-wrap; background: #fafaf9; padding: 12px; border-radius: var(--radius); border: 1px solid var(--border-color);"></p>
        <div class="modal-footer" style="text-align: right; margin-top: 10px;">
            <button id="confirmButton" class="btn btn-success btn-sm">Confirm</button>
            <button id="cancelButton" class="btn btn-danger btn-sm">Cancel</button>
        </div>
    </div>
</div>

{{-- Image Modal --}}
<div id="imageModal" onclick="closeImageModal()">
    <span onclick="closeImageModal()" style="position:absolute;top:15px;right:30px;font-size:35px;color:white;cursor:pointer;font-weight:bold;">&times;</span>
    <img id="modalImg" src="" alt="Preview">
</div>

{{-- Toast Notification --}}
<div id="toastNotification"></div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.3.1/dist/jsQR.js"></script>
<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
const updateEndpoint = '{{ $cleanUpdateUrl }}';
const stationColor = '{{ $stationColor }}';
const diagnosisField = '{{ $diagnosisField }}';
const remarksField = '{{ $remarksField }}';
const approvalField = '{{ $approvalField }}';

let currentPatient = null;
let currentScanData = null;
let currentScanPage = 1;
const SCAN_PAGE_SIZE = 50;

// --- Image Modal ---
function enlargeImage(img) {
    let src = typeof img === 'string' ? img : img.src;
    document.getElementById('modalImg').src = src;
    document.getElementById('imageModal').style.display = 'flex';
}
function closeImageModal() {
    document.getElementById('imageModal').style.display = 'none';
}

// --- Data Confirm Modal ---
function closeDataModal() {
    document.getElementById('dataModal').style.display = 'none';
}
function showScanModal(data) {
    document.getElementById('modalData').innerText = JSON.stringify(data, null, 2);
    document.getElementById('dataModal').style.display = 'flex';
}

document.querySelector('#dataModal .close').addEventListener('click', closeDataModal);
document.getElementById('cancelButton').addEventListener('click', closeDataModal);
window.addEventListener('click', e => {
    if (e.target === document.getElementById('imageModal')) closeImageModal();
    if (e.target === document.getElementById('dataModal')) closeDataModal();
});

// --- Toast ---
function showToast(message) {
    const toast = document.getElementById('toastNotification');
    toast.textContent = message;
    toast.style.display = 'block';
    setTimeout(() => { toast.style.display = 'none'; }, 3500);
}

// --- QR Scanner helpers ---
function convertToId(label) {
    const map = {
        'Rank': 'rank', 'Serial No': 'serialNo', 'Branch': 'branch', 'First Name': 'firstName',
        'Middle Name': 'middleName', 'Last Name': 'lastName', 'Birthdate': 'birthdate', 'Age': 'age',
        'Sex': 'sex', 'Race': 'race', 'Place of Birth': 'placeOfBirth', 'Current Address': 'currentAddress',
        'Place of Examination': 'placeOfExamination', 'Date of Examination': 'dateOfExamination',
        'Purpose': 'purpose', 'Batch': 'batch', 'Applicant Code': 'appcode',
        'Unit Org.': 'unitorg', 'Other Data': 'otherData'
    };
    return map[label] || label.toLowerCase().replace(/\s/g, '');
}

function parseColonData(data) {
    const result = {};
    data.split(/\n+/).forEach(line => {
        const m = /^([\w\s]+):\s*(.+)$/.exec(line);
        if (m) {
            const key = m[1].trim();
            const val = m[2].trim();
            result[convertToId(key)] = val;
        }
    });
    return result;
}

const video = document.getElementById('video');
const toggleCameraBtn = document.getElementById('toggleCameraBtn');
let stream = null;
let isCameraEnabled = true;
const canvasEl = document.createElement('canvas');
const canvas = canvasEl.getContext('2d', { willReadFrequently: true });

function startCamera() {
    // Browsers require a secure context (HTTPS or localhost) for camera access.
    if (!window.isSecureContext) {
        const cameraBox = video.closest('.camera-box') || video.parentElement;
        video.style.display = 'none';
        const warn = document.createElement('p');
        warn.style.cssText = 'color:#c0392b;font-weight:600;font-size:0.9rem;margin:0;padding:12px;';
        warn.textContent = '⚠ Camera requires HTTPS. Please access this page over https:// to enable QR scanning.';
        cameraBox.prepend(warn);
        return;
    }
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        alert('Camera access is not supported in this browser.');
        return;
    }
    navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
        .then(s => {
            stream = s;
            video.srcObject = stream;
            video.play();
            requestAnimationFrame(tick);
        })
        .catch(err => {
            const msg = err.name === 'NotAllowedError'
                ? 'Camera permission was denied. Please allow camera access in your browser settings.'
                : 'Cannot access camera: ' + err.message;
            alert(msg);
        });
}
function stopCamera() {
    if (stream) {
        stream.getTracks().forEach(t => t.stop());
        video.srcObject = null;
    }
}
function tick() {
    if (!isCameraEnabled) return;
    if (video.readyState === video.HAVE_ENOUGH_DATA) {
        canvasEl.height = video.videoHeight;
        canvasEl.width = video.videoWidth;
        canvas.drawImage(video, 0, 0, canvasEl.width, canvasEl.height);
        const imageData = canvas.getImageData(0, 0, canvasEl.width, canvasEl.height);
        const code = jsQR(imageData.data, imageData.width, imageData.height, { inversionAttempts: 'attemptBoth' });
        if (code) {
            let currentScanData = {};
            try {
                const parsed = JSON.parse(code.data);
                currentScanData = {
                    firstName: parsed.first_name || '',
                    middleName: parsed.middle_name || '',
                    lastName: parsed.last_name || '',
                    birthdate: parsed.date_of_birth || '',
                    patient_no: parsed.patient_no || ''
                };
            } catch (e) {
                currentScanData = parseColonData(code.data);
                // Try aggressive fallback if the standard map doesn't match
                if (!currentScanData.patient_no && currentScanData.applicantcode) currentScanData.patient_no = currentScanData.applicantcode;
                if (!currentScanData.patient_no && currentScanData.applicantno) currentScanData.patient_no = currentScanData.applicantno;
                if (!currentScanData.patient_no && currentScanData.patientno) currentScanData.patient_no = currentScanData.patientno;
            }
            
            // Automatically fetch patient record without modal confirmation
            const data = {
                patient_no:  currentScanData.patient_no  || '',
                firstName:   currentScanData.firstName   || '',
                middleName:  currentScanData.middleName  || '',
                lastName:    currentScanData.lastName    || '',
                birthdate:   currentScanData.birthdate   || ''
            };
            
            // Add a brief cooldown so it doesn't spam requests while QR is held
            if (window.lastScannedQr === code.data && (Date.now() - window.lastScanTime) < 3000) {
                requestAnimationFrame(tick);
                return;
            }
            window.lastScannedQr = code.data;
            window.lastScanTime = Date.now();
            
            showToast('Scanning QR... Fetching record.');
            
            fetch('{{ route('api.patient.validate-record') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-TOKEN': csrfToken },
                body: new URLSearchParams(data).toString()
            })
            .then(r => r.json())
            .then(res => {
                if (res.exists) {
                    return fetch('{{ route('api.patient.fetch-record') }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-TOKEN': csrfToken },
                        body: new URLSearchParams(data).toString()
                    });
                } else {
                    showToast('No record found for this applicant.');
                    throw new Error('no-record');
                }
            })
            .then(r => r.json())
            .then(rec => {
                if (rec.success) {
                    showToast('Record loaded successfully.');
                    displayPatientData(rec.data);
                }
            })
            .catch(e => {
                if (e && e.message !== 'no-record') console.error(e);
            });
        }
    }
    requestAnimationFrame(tick);
}

toggleCameraBtn.addEventListener('click', () => {
    if (isCameraEnabled) {
        stopCamera();
        toggleCameraBtn.innerHTML = '<i class="fas fa-camera"></i> Enable Camera';
    } else {
        startCamera();
        toggleCameraBtn.innerHTML = '<i class="fas fa-camera"></i> Disable Camera';
    }
    isCameraEnabled = !isCameraEnabled;
});

// --- Scanned Applicant Table ---
document.getElementById('confirmButton').addEventListener('click', function () {
    if (!currentScanData) return;
    const tbody = document.getElementById('applicantInfo');
    const row = document.createElement('tr');
    row.className = 'patient-row';
    row.innerHTML = `
        <td>-</td>
        <td>-</td>
        <td>${currentScanData.firstName || '-'}</td>
        <td>${currentScanData.lastName || '-'}</td>
        <td><div class="inline-actions"><button class="action-chip success" onclick="confirmActive(this)">Confirm Active</button><button class="action-chip danger" onclick="removeRow(this)">Remove</button></div></td>
    `;
    tbody.appendChild(row);
    renderScanPage(Math.ceil(tbody.querySelectorAll('tr').length / SCAN_PAGE_SIZE));
    closeDataModal();
});

function removeRow(btn) {
    if (confirm('Remove this entry?')) {
        btn.closest('tr').remove();
        renderScanPage(currentScanPage);
    }
}

function renderScanPage(page) {
    const rows = Array.from(document.querySelectorAll('#applicantInfo tr'));
    const total = rows.length;
    const totalPages = Math.max(1, Math.ceil(total / SCAN_PAGE_SIZE));
    if (page < 1) page = 1;
    if (page > totalPages) page = totalPages;
    currentScanPage = page;

    const start = (page - 1) * SCAN_PAGE_SIZE;
    const end = start + SCAN_PAGE_SIZE;
    rows.forEach((row, index) => {
        row.style.display = (index >= start && index < end) ? '' : 'none';
    });

    const endCount = Math.min(end, total);
    document.getElementById('scanPageInfo').textContent = total === 0 ? 'Showing 0 of 0' : 'Showing ' + (start + 1) + '-' + endCount + ' of ' + total;

    const btnContainer = document.getElementById('scanPageButtons');
    btnContainer.innerHTML = '';

    const prevBtn = document.createElement('button');
    prevBtn.style.cssText = 'padding:4px 14px;border:1px solid var(--border-color);background:white;cursor:pointer;border-radius:4px;font-size:0.8rem;color:var(--primary-color);font-weight:600;';
    prevBtn.textContent = 'Previous';
    prevBtn.disabled = page <= 1;
    prevBtn.onclick = () => renderScanPage(page - 1);
    btnContainer.appendChild(prevBtn);

    const maxButtons = 5;
    let startPage = Math.max(1, page - Math.floor(maxButtons / 2));
    let endPage = Math.min(totalPages, startPage + maxButtons - 1);
    if (endPage - startPage < maxButtons - 1) startPage = Math.max(1, endPage - maxButtons + 1);

    for (let p = startPage; p <= endPage; p++) {
        const btn = document.createElement('button');
        const active = p === page;
        btn.style.cssText = 'padding:4px 10px;border:1px solid ' + (active ? 'var(--primary-color)' : 'var(--border-color)') + ';background:' + (active ? 'var(--primary-color)' : 'white') + ';cursor:pointer;border-radius:4px;font-size:0.8rem;color:' + (active ? 'white' : 'var(--primary-color)') + ';font-weight:' + (active ? '600' : '400') + ';';
        btn.textContent = `${p}`;
        btn.onclick = () => renderScanPage(p);
        btnContainer.appendChild(btn);
    }

    const nextBtn = document.createElement('button');
    nextBtn.style.cssText = 'padding:4px 14px;border:1px solid var(--border-color);background:white;cursor:pointer;border-radius:4px;font-size:0.8rem;color:var(--primary-color);font-weight:600;';
    nextBtn.textContent = 'Next';
    nextBtn.disabled = page >= totalPages;
    nextBtn.onclick = () => renderScanPage(page + 1);
    btnContainer.appendChild(nextBtn);
}

function confirmActive(btn) {
    const row = btn.closest('tr');
    const data = {
        firstName:   currentScanData.firstName   || '',
        middleName:  currentScanData.middleName  || '',
        lastName:    currentScanData.lastName    || '',
        birthdate:   currentScanData.birthdate   || ''
    };
    fetch('{{ route('api.patient.validate-record') }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-TOKEN': csrfToken },
        body: new URLSearchParams(data).toString()
    })
    .then(r => r.json())
    .then(res => {
        if (res.exists) {
            showToast('Record found. Loading patient data...');
            return fetch('{{ route('api.patient.fetch-record') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-TOKEN': csrfToken },
                body: new URLSearchParams(data).toString()
            });
        } else {
            showToast('No record found for this applicant.');
            throw new Error('no-record');
        }
    })
    .then(r => r.json())
    .then(rec => {
        if (rec.success) {
            const photoPath = rec.data.photo_url || (rec.data.photo_path ? ('/' + rec.data.photo_path.replace(/^\/+/, '')) : '/images/blank.jpg');
            row.innerHTML = `
                <td><img src="${photoPath}" class="thumb-img" onclick="event.stopPropagation(); enlargeImage(this)"></td>
                <td>${rec.data.patient_no || '-'}</td>
                <td>${rec.data.firstName || '-'}</td>
                <td>${rec.data.lastName || '-'}</td>
                <td><div class="inline-actions"><button class="action-chip danger" onclick="event.stopPropagation(); removeRow(this)">Remove</button></div></td>
            `;
            row.onclick = () => displayPatientData(rec.data);
            displayPatientData(rec.data);
        } else {
            showToast('Failed to fetch record.');
        }
    })
    .catch(e => {
        if (e && e.message !== 'no-record') console.error(e);
    });
}

// --- Populate Details Card ---
function populateDetails(data) {
    const photoEl = document.getElementById('detail-photo');
    if (photoEl) {
        photoEl.src = data.photo_url || (data.photo_path ? ('/' + data.photo_path.replace(/^\/+/, '')) : '/images/blank.jpg');
    }
    const set = (id, val) => {
        const el = document.getElementById(id);
        if (el) el.textContent = (val !== undefined && val !== null && String(val).trim() !== '') ? val : '-';
    };
    set('detail-patient-no',        data.patient_no);
    set('detail-appcode',           data.appcode);
    set('detail-firstname',         data.firstName);
    set('detail-middlename',        data.middleName);
    set('detail-lastname',          data.lastName);
    set('detail-birthdate',         data.birthdate);
    set('detail-age',               data.age);
    set('detail-sex',               data.sex);
    set('detail-batch',             data.batch);
    set('detail-placeOfBirth',      data.placeOfBirth);
    set('detail-currentAddress',    data.currentAddress);
    set('detail-placeOfExamination',data.placeOfExamination);
    set('detail-dateOfExamination', data.dateOfExamination);
    set('detail-purpose',           data.purpose);
    set('detail-race',              data.raceSelect || data.race);
    set('detail-unitorg',           data.unitorg);
}

// --- Populate Actions Card ---
function populateActions(data) {
    const fullName = [data.lastName, data.firstName, data.middleName].filter(Boolean).join(', ');
    document.getElementById('selectedPatientBar').textContent =
        'SELECTED PATIENT: #' + (data.patient_no || '----') + ' - ' + fullName;

    document.getElementById('form-patient-no').value  = data.patient_no || '';
    
    // Find the station evaluation if it was loaded
    const evalData = (data.evaluations && Array.isArray(data.evaluations)) 
        ? data.evaluations.find(e => e.station_key === '{{ $station }}') 
        : null;

    document.getElementById('form-diagnosis').value   = (evalData && evalData.diagnosis) || data[diagnosisField] || data.diagnosis || '';
    document.getElementById('form-remarks').value     = (evalData && evalData.remarks) || data[remarksField]   || data.remarks   || '';
    const approvalRaw = ((evalData && evalData.approval_status) || data[approvalField] || data.action || data.approval || '').toString().trim().toUpperCase();
    document.getElementById('form-approval').value    = approvalRaw;
}

// --- Display Patient Data: show both Details & Actions sections ---
function displayPatientData(data) {
    currentPatient = data;
    populateDetails(data);
    populateActions(data);

    document.getElementById('detailsSection').classList.add('visible');
    document.getElementById('actionsSection').classList.add('visible');
    document.getElementById('detailsSection').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// --- Submit Form Data ---
function submitData(btn) {
    if (!currentPatient || !currentPatient.patient_no) {
        showToast('No patient selected.');
        return;
    }

    const patientNo  = currentPatient.patient_no;
    const diagnosis  = document.getElementById('form-diagnosis').value.trim();
    const remarks    = document.getElementById('form-remarks').value.trim();
    const action     = document.getElementById('form-approval').value.trim();

    if (!diagnosis || !remarks || !action) {
        showToast('Please fill in Diagnosis, Remarks, and Approval Status.');
        return;
    }

    btn.disabled = true;
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';

    const formData = new FormData();
    formData.append('patient_no', patientNo);
    formData.append('diagnosis', diagnosis);
    formData.append('remarks', remarks);
    formData.append('action', action);
    formData.append('user_username', '{{ session('staff.display_name', 'Guest') }}');

    fetch(updateEndpoint, {
        method: 'POST',
        body: formData,
        headers: { 'X-CSRF-TOKEN': csrfToken }
    })
    .then(r => r.json())
    .then(d => {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
        if (d && (d.success === true || d.status === 'success')) {
            showToast(d.message || 'Record submitted successfully.');
        } else if (d && d.message) {
            showToast(d.message);
        } else {
            showToast('Submitted successfully.');
        }
    })
    .catch(e => {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
        console.error(e);
        showToast('Failed to submit data.');
    });
}

// --- Start camera on load ---
startCamera();
</script>
@endpush
