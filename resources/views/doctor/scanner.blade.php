@extends('layouts.portal')

@section('title', ($stationConfig['label'] ?? strtoupper($station)) . ' - QR Scanner')

@section('sidebar-nav')
<div class="nav-group-title">Medical Assessment</div>
<a href="{{ route('doctor.show', $station) }}" class="nav-item">
    <i class="fas fa-heartbeat"></i> {{ $stationConfig['label'] ?? strtoupper($station) }} Evaluation
</a>
<a href="{{ route('doctor.scanner', $station) }}" class="nav-item active">
    <i class="fas fa-qrcode"></i> QR Scanner
</a>
@endsection

@section('header-title', 'QR Scanner')

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
    .action-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 20px;
    }

    .action-buttons a {
        flex: 1 1 auto;
        min-width: 140px;
        justify-content: center;
    }
</style>
@endpush

@section('content')
<div style="display:flex; flex-direction:column; gap:20px;">

    {{-- QR Scanner Card --}}
    <div class="card">
        <div class="card-header">QR Scanner</div>
        <div class="card-body">
            <div class="camera-box" style="background: rgba(255, 255, 255, 0.4); border: 1px dashed rgba(180, 195, 210, 0.8); border-radius: 20px; padding: 24px; text-align: center; min-height: 280px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 16px; overflow: hidden;">
                <video id="video" autoplay playsinline style="width: 100%; max-width: 520px; border-radius: 16px; background: rgba(0,0,0,0.05); box-shadow: 0 4px 12px rgba(0,0,0,0.05);"></video>
                <p id="result" style="color:var(--text-muted); font-weight:600; font-size:0.9rem; margin: 0;">Scan a QR code to load a patient record.</p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/vendor/jsqr-1.4.0.min.js') }}"></script>
<script>
    const video = document.getElementById('video');
    const result = document.getElementById('result');

    function startScanner() {
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
                    

    
                    if (code && !window.isScanning) {
                        window.isScanning = true;
                        console.log('Scanned QR code data:', code.data);
                        result.textContent = 'QR scanned! Loading patient data...';
                        
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
                            if (res.exists) {
                                // Redirect to the Doctor dashboard page and search for this patient by patient_no
                                window.location.href = '{{ route('doctor.show', $station) }}' + '?search=' + encodeURIComponent(res.patient_no);
                            } else {
                                result.textContent = 'No record found for this applicant. Please try again.';
                                setTimeout(() => { window.isScanning = false; }, 3000);
                            }
                        })
                        .catch(() => {
                            result.textContent = 'Network error. Please try again.';
                            setTimeout(() => { window.isScanning = false; }, 3000);
                        });
                    }
                }
                requestAnimationFrame(tick);
            };

            requestAnimationFrame(tick);
        }).catch(() => {
            result.textContent = 'Camera permission was denied. The scanner will remain offline.';
        });
    }

    startScanner();
</script>
@endpush

