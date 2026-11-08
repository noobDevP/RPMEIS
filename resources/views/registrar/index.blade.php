@extends('layouts.portal')

@section('title', 'Registrar - ARO Visayas')

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

@section('header-title', 'Registrar Dashboard')

@section('header-actions')
@if($batches->isNotEmpty())
    <select id="batchFilterIndex" class="select-modern" style="width: auto; min-width: 160px;">
        <option value="">ALL BATCHES</option>
        @foreach($batches as $batch)
            <option value="{{ $batch }}">{{ $batch }}</option>
        @endforeach
    </select>
@endif
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
                    <option value="All" selected>All Categories</option>
                    @foreach ($categoryMap as $key => $column)
                        <option value="{{ $key }}">{{ str_replace('_', '/', $key) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="font-weight:600;display:block;margin-bottom:4px;font-size:0.85rem;">Batch</label>
                <select id="analyticsBatchSelect" class="select-modern">
                    <option value="All" selected>All Batches</option>
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

@push('scripts')
<script src="{{ asset('js/vendor/jsqr-1.3.1.js') }}"></script>
<script>
    const video = document.getElementById('video');
    const result = document.getElementById('result');

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
            const context = canvas.getContext('2d');

            const tick = () => {
                if (video.readyState === video.HAVE_ENOUGH_DATA) {
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    context.drawImage(video, 0, 0, canvas.width, canvas.height);
                    const imageData = context.getImageData(0, 0, canvas.width, canvas.height);
                    const code = window.jsQR(imageData.data, imageData.width, imageData.height);
                    if (code && !window.isScanning) {
                        window.isScanning = true;
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
                                // Redirect to the Active Records page and search for this patient
                                window.location.href = '{{ route('registrar.records') }}' + '?search=' + encodeURIComponent(res.patient_no);
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

    // Show Analytics modal
    document.getElementById('showAnalyticsBtn')?.addEventListener('click', function(e) {
        if (e && typeof e.preventDefault === 'function') e.preventDefault();
        document.getElementById('analyticsModal').style.display = 'block';
        document.getElementById('filterAnalyticsBtn')?.click();
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
</script>
@endpush
