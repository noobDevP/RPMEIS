@extends('layouts.portal')

@section('title', 'Registrar - Activate Account')

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

@section('header-title', 'Activate Account')

@section('header-actions')
@endsection

@push('styles')
<style>
    .camera-box video {
        width: 100%;
        max-width: 420px;
        border-radius: var(--radius);
        background: #000;
        display: block;
    }
    .activation-card {
        border-radius: var(--radius);
        background: #fff;
        padding: 24px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        display: none;
        flex-direction: column;
        align-items: center;
        gap: 16px;
        border: 1px solid var(--border-color);
        margin-top: 20px;
    }
    .activation-card img {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid var(--primary-color);
    }
    .activation-details {
        text-align: center;
    }
    .activation-details h3 {
        margin: 0 0 8px 0;
        font-size: 1.25rem;
        color: var(--text-color);
    }
    .activation-details p {
        margin: 0;
        color: var(--text-muted);
        font-size: 0.95rem;
    }
</style>
@endpush

@section('content')
<div style="display:flex; flex-direction:column; gap:20px;">

    {{-- QR Scanner Card --}}
    <div class="card">
        <div class="card-header">Scan QR Code to Activate</div>
        <div class="card-body">
            <div class="scanner-layout" style="display: flex; gap: 20px; flex-wrap: wrap; align-items: stretch;">
                <div class="camera-box" style="flex: 1; min-width: 300px; background: rgba(255, 255, 255, 0.4); border: 1px dashed rgba(180, 195, 210, 0.8); border-radius: 20px; padding: 24px; text-align: center; min-height: 280px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 16px; overflow: hidden;">
                    <video id="video" autoplay playsinline style="width: 100%; max-width: 520px; border-radius: 16px; background: rgba(0,0,0,0.05); box-shadow: 0 4px 12px rgba(0,0,0,0.05);"></video>
                    <p id="result" style="color:var(--text-muted); font-weight:600; font-size:0.9rem; margin: 0;">Point the camera at an applicant's QR code.</p>
                </div>
                
                <div id="activationCard" class="activation-card" style="flex: 1; min-width: 300px; margin-top: 0; display: none;">
                    <img id="applicantPhoto" src="" alt="Applicant Photo">
                    <div class="activation-details">
                        <h3 id="applicantName"></h3>
                        <p>App Code: <strong id="applicantCode"></strong></p>
                        <p>Batch: <strong id="applicantBatch"></strong></p>
                        <p style="margin-top: 10px;" id="activationStatus"></p>
                    </div>
                    <button id="btnActivate" class="btn btn-primary" style="margin-top: 10px; width: 100%; max-width: 200px;">
                        <i class="fas fa-check-circle"></i> Activate Applicant
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="{{ asset('js/vendor/jsqr-1.3.1.js') }}"></script>
<script>
    const video = document.getElementById('video');
    const result = document.getElementById('result');
    const activationCard = document.getElementById('activationCard');
    const applicantPhoto = document.getElementById('applicantPhoto');
    const applicantName = document.getElementById('applicantName');
    const applicantCode = document.getElementById('applicantCode');
    const applicantBatch = document.getElementById('applicantBatch');
    const btnActivate = document.getElementById('btnActivate');
    const activationStatus = document.getElementById('activationStatus');
    
    let currentPatientNo = null;
    let lastScannedData = null;
    let isProcessing = false;

    function startScanner() {
        // Browsers require a secure context (HTTPS or localhost) for camera access.
        if (!window.isSecureContext) {
            result.innerHTML = '⚠ Camera requires HTTPS. Please access this page over <strong>https://</strong> to enable QR scanning.';
            result.style.color = '#c0392b';
            return;
        }
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            result.textContent = 'Camera access is not available in this browser.';
            return;
        }

        navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } }).then(stream => {
            video.srcObject = stream;
            video.play();
            const canvas = document.createElement('canvas');
            const context = canvas.getContext('2d', { willReadFrequently: true });

                        const tick = () => {

                if (video.readyState === video.HAVE_ENOUGH_DATA) {
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    context.drawImage(video, 0, 0, canvas.width, canvas.height);
                    const imageData = context.getImageData(0, 0, canvas.width, canvas.height);
                    const code = window.jsQR(imageData.data, imageData.width, imageData.height);
                    

    
                    
                    if (code && !isProcessing) {
                        // Prevent re-processing the exact same QR code immediately unless it's a new one
                        if (lastScannedData !== code.data) {
                            lastScannedData = code.data;
                            isProcessing = true;
                            console.log('Scanned QR code data:', code.data);
                            result.textContent = 'QR scanned! Fetching details...';
                            
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
                                code.data.split(/\n+/).forEach(line => {
                                    const m = /^([\w\s]+):\s*(.+)$/.exec(line);
                                    if (m) {
                                        const key = m[1].trim().toLowerCase().replace(/\s/g, '');
                                        const map = {
                                            'firstname': 'firstName', 'middlename': 'middleName', 'lastname': 'lastName', 'birthdate': 'birthdate', 'patientno': 'patient_no', 'applicantcode': 'patient_no', 'applicantno': 'patient_no'
                                        };
                                        const mappedKey = map[key] || key;
                                        currentScanData[mappedKey] = m[2].trim();
                                    }
                                });
                            }

                            const data = {
                                patient_no:  currentScanData.patient_no  || '',
                                firstName:   currentScanData.firstName   || '',
                                middleName:  currentScanData.middleName  || '',
                                lastName:    currentScanData.lastName    || '',
                                birthdate:   currentScanData.birthdate   || ''
                            };

                            fetch('{{ route('api.patient.validate-record') }}', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                                body: new URLSearchParams(data).toString()
                            })
                            .then(r => r.json())
                            .then(res => {
                                if (res.exists && res.patient_no) {
                                    loadApplicantDetails(res.patient_no);
                                } else {
                                    result.textContent = 'No record found. Please scan again.';
                                    activationCard.style.display = 'none';
                                    setTimeout(() => { isProcessing = false; }, 2000);
                                }
                            })
                            .catch(() => {
                                result.textContent = 'Network error. Please try again.';
                                setTimeout(() => { isProcessing = false; }, 2000);
                            });
                        }
                    }
                }
                requestAnimationFrame(tick);
            };

            requestAnimationFrame(tick);
        }).catch(() => {
            result.textContent = 'Camera permission was denied. The scanner will remain offline.';
        });
    }
    
    function loadApplicantDetails(patientNo) {
        currentPatientNo = patientNo;
        fetch('/api/patients/' + patientNo)
            .then(r => r.json())
            .then(data => {
                if (data && !data.message) {
                    result.textContent = 'Ready for next scan.';
                    activationCard.style.display = 'flex';
                    
                    let name = (data.firstName || '') + ' ' + (data.middleName ? data.middleName[0] + '. ' : '') + (data.lastName || '');
                    applicantName.textContent = name;
                    applicantCode.textContent = data.appcode || 'N/A';
                    applicantBatch.textContent = data.batch || 'N/A';
                    applicantPhoto.src = data.photo_url || '/images/blank.jpg';
                    
                    if (data.is_activated) {
                        activationStatus.innerHTML = '<span style="color:var(--success-color); font-weight:600;"><i class="fas fa-check"></i> Already Activated</span>';
                        btnActivate.style.display = 'none';
                    } else {
                        activationStatus.innerHTML = '<span style="color:var(--danger-color); font-weight:600;"><i class="fas fa-times"></i> Not Activated</span>';
                        btnActivate.style.display = 'block';
                    }
                }
                // Allow scanning a new code
                setTimeout(() => { isProcessing = false; }, 1500);
            })
            .catch(() => {
                result.textContent = 'Failed to load details.';
                setTimeout(() => { isProcessing = false; }, 1500);
            });
    }
    
    btnActivate.addEventListener('click', function() {
        if (!currentPatientNo) return;
        
        btnActivate.disabled = true;
        btnActivate.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Activating...';
        
        fetch('/api/patients/' + currentPatientNo + '/activate', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                activationStatus.innerHTML = '<span style="color:var(--success-color); font-weight:600;"><i class="fas fa-check"></i> Successfully Activated</span>';
                btnActivate.style.display = 'none';
                lastScannedData = null; // Clear so it can be re-scanned if needed
            } else {
                alert('Failed to activate applicant.');
                btnActivate.disabled = false;
                btnActivate.innerHTML = '<i class="fas fa-check-circle"></i> Activate Applicant';
            }
        })
        .catch(() => {
            alert('Network error while activating.');
            btnActivate.disabled = false;
            btnActivate.innerHTML = '<i class="fas fa-check-circle"></i> Activate Applicant';
        });
    });

    startScanner();
</script>
@endpush
