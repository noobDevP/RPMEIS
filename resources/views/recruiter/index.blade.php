@extends('layouts.portal')

@section('title', 'Generate QR - ARO Visayas')

@section('sidebar-nav')
<div class="nav-group-title">Recruitment Operations</div>
<a href="{{ route('recruiter.dashboard') }}" class="nav-item {{ request()->is('recruiter') && !request()->is('recruiter/processed*') && !request()->is('recruiter/scanner*') ? 'active' : '' }}">
    <i class="fas fa-qrcode"></i> Generate QR
</a>
<a href="{{ route('recruiter.scanner') }}" class="nav-item {{ request()->is('recruiter/scanner*') ? 'active' : '' }}">
    <i class="fas fa-qrcode"></i> QR Scanner
</a>
<a href="{{ route('recruiter.processed') }}" class="nav-item {{ request()->is('recruiter/processed*') ? 'active' : '' }}">
    <i class="fas fa-list-check"></i> Applicant Data
</a>
@endsection

@section('header-title', 'Recruiter Dashboard')

@section('header-actions')
<a href="{{ route('recruiter.processed') }}" class="btn btn-outline btn-sm">
    <i class="fas fa-user-check"></i> Check Applicant Status
</a>
@endsection

@section('footer')
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/vendor/select2-4.0.13.min.css') }}" />
<style>
    /* Modal styles from original */
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0; left: 0;
        width: 100%; height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 1000;
    }
    .modal {
        display: none;
        position: fixed;
        top: 50%; left: 50%;
        transform: translate(-50%, -50%);
        width: 500px;
        padding: 20px;
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        z-index: 1001;
        text-align: center;
    }
    .main-wrapper > footer {
        display: none !important;
    }
    .modal h3 { margin-top: 0; font-size: 18px; color: #333; }
    #qrcode { position: relative; width: 300px; height: 300px; margin: 0 auto; }
    #qrcode img { width: 100%; height: 100%; }
    #qrcode .logo {
        position: absolute; top: 50%; left: 50%;
        transform: translate(-50%, -50%);
        width: 50px; height: 50px;
        border-radius: 10px; background: white; padding: 5px;
    }
</style>
@endpush

@section('content')
{{-- Main Form Card --}}
<div class="card">
    <div class="card-body" style="padding:1.5rem;">
        <div style="display:flex;gap:1.5rem;flex-wrap:wrap;">
            {{-- Left Column: Photo --}}
            <div style="flex:0 0 320px;display:flex;flex-direction:column;align-items:center;">
                <div style="text-align:center;margin-bottom:.75rem;width:100%;">
                    <h6 style="text-transform:uppercase;font-size:.85rem;font-weight:700;color:var(--text-muted);margin:0 0 .5rem 0;letter-spacing:.08em;border-bottom:1px solid rgba(180,195,210,0.3);padding-bottom:.5rem;text-align:left;">Applicant Photo</h6>
                    <div id="photoPlaceholder" style="width:280px;height:280px;border:1px dashed rgba(180,195,210,0.8);border-radius:16px;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,0.4);margin:1rem auto;overflow:hidden;">
                        <video id="video" style="width:280px;height:280px;object-fit:cover;border-radius:16px;background:rgba(0,0,0,0.05);" autoplay playsinline></video>
                        <canvas id="canvas" style="display:none;"></canvas>
                        <img id="photoPreview" style="width:280px;height:280px;object-fit:cover;border-radius:16px;border:2px solid var(--primary-color);display:none;" alt="Captured Photo">
                    </div>
                </div>
                <div style="display:flex;gap:10px;width:280px;margin:0 auto;">
                    <button type="button" onclick="captureAndSavePhoto()" class="btn btn-sm" style="flex:1;background:var(--primary-grad);color:white;border-radius:12px;font-weight:600;">
                        <i class="fas fa-camera"></i> Capture
                    </button>
                    <button type="button" onclick="recapturePhoto()" class="btn btn-sm" style="flex:1;background:var(--primary-grad);color:white;border-radius:12px;font-weight:600;">
                        <i class="fas fa-redo"></i> Retake
                    </button>
                </div>
            </div>

            {{-- Right Column: Form Fields --}}
            <div style="flex:1;min-width:280px;">
                {{-- APPLICATION INFORMATION --}}
                <h6 style="text-transform:uppercase;font-size:.85rem;font-weight:700;color:var(--text-muted);margin:0 0 1rem 0;letter-spacing:.08em;border-bottom:1px solid rgba(180,195,210,0.3);padding-bottom:.5rem;">Application Information</h6>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;margin-bottom:1.25rem;">
                    <div>
                        <label style="font-size:.8rem;font-weight:500;margin-bottom:4px;display:block;color:#333;">Rank</label>
                        <select id="rankSelect" onchange="document.getElementById('rank').value = this.value;" class="select-modern" style="width:100%;">
                            <option value="">Select Rank</option>
                            <option value="Sergeant">Sergeant</option>
                            <option value="Corporal">Corporal</option>
                            <option value="Private First Class">Private First Class</option>
                            <option value="Private">Private</option>
                        </select>
                        <input type="hidden" id="rank" name="rank">
                    </div>
                    <div>
                        <label style="font-size:.8rem;font-weight:500;margin-bottom:4px;display:block;color:#333;">Serial No</label>
                        <input type="text" id="serialNo" class="input-modern" placeholder="Enter Serial No" style="width:100%;">
                    </div>
                    <div>
                        <label style="font-size:.8rem;font-weight:500;margin-bottom:4px;display:block;color:#333;">Branch</label>
                        <input type="text" id="branch" class="input-modern" placeholder="Enter Branch" style="width:100%;">
                    </div>
                    <div>
                        <label style="font-size:.8rem;font-weight:500;margin-bottom:4px;display:block;color:#333;">Applicant Code</label>
                        <input type="text" id="appcode" class="input-modern" placeholder="Enter App. Code" required style="width:100%;">
                    </div>
                </div>

                {{-- PERSONAL INFORMATION --}}
                <h6 style="text-transform:uppercase;font-size:.85rem;font-weight:700;color:var(--text-muted);margin:1.5rem 0 1rem 0;letter-spacing:.08em;border-bottom:1px solid rgba(180,195,210,0.3);padding-bottom:.5rem;">Personal Information</h6>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:.75rem;margin-bottom:.75rem;">
                    <div>
                        <label style="font-size:.8rem;font-weight:500;margin-bottom:4px;display:block;color:#333;">First Name</label>
                        <input type="text" id="firstName" class="input-modern" placeholder="Enter First Name" required style="width:100%;">
                    </div>
                    <div>
                        <label style="font-size:.8rem;font-weight:500;margin-bottom:4px;display:block;color:#333;">Middle Name</label>
                        <input type="text" id="middleName" class="input-modern" placeholder="Enter Middle Name" required style="width:100%;">
                    </div>
                    <div>
                        <label style="font-size:.8rem;font-weight:500;margin-bottom:4px;display:block;color:#333;">Last Name</label>
                        <input type="text" id="lastName" class="input-modern" placeholder="Enter Last Name" required style="width:100%;">
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:.75rem;margin-bottom:.75rem;">
                    <div>
                        <label style="font-size:.8rem;font-weight:500;margin-bottom:4px;display:block;color:#333;">Birthdate</label>
                        <input type="date" id="birthdate" class="input-modern" onchange="calculateAge()" required style="width:100%;">
                    </div>
                    <div>
                        <label style="font-size:.8rem;font-weight:500;margin-bottom:4px;display:block;color:#333;">Age</label>
                        <input type="text" id="age" class="input-modern" readonly style="width:100%;">
                    </div>
                    <div>
                        <label style="font-size:.8rem;font-weight:500;margin-bottom:4px;display:block;color:#333;">Sex</label>
                        <select id="sex" class="select-modern" required style="width:100%;">
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                        </select>
                    </div>
                </div>
                <div style="margin-bottom:1rem;">
                    <label style="font-size:.8rem;font-weight:500;margin-bottom:4px;display:block;color:#333;">Ethnicity</label>
                    <select id="raceSelect" onchange="document.getElementById('race').value = this.value;" class="select-modern" required style="width:100%;">
                        <option value="">Select Ethnicity</option>
                        <option value="Abelling">Abelling</option>
                        <option value="Agta">Agta</option>
                        <option value="Aeta">Aeta</option>
                        <option value="Apayao">Apayao</option>
                        <option value="Ati">Ati</option>
                        <option value="Bagobo">Bagobo</option>
                        <option value="Bajau">Bajau</option>
                        <option value="Balangao">Balangao</option>
                        <option value="Batak">Batak</option>
                        <option value="Binukid">Binukid</option>
                        <option value="Bisaya">Bisaya</option>
                        <option value="Blaan">Blaan</option>
                        <option value="Bontoc">Bontoc</option>
                        <option value="Bukidnon">Bukidnon</option>
                        <option value="Bulalakawnon">Bulalakawnon</option>
                        <option value="Cebuano">Cebuano</option>
                        <option value="Dibabawon">Dibabawon</option>
                        <option value="Gaddang">Gaddang</option>
                        <option value="Higaonon">Higaonon</option>
                        <option value="Ibaloi">Ibaloi</option>
                        <option value="Ifugao">Ifugao</option>
                        <option value="Ilocano">Ilocano</option>
                        <option value="Ilongo">Ilongo</option>
                        <option value="Ivatan">Ivatan</option>
                        <option value="Kalinga">Kalinga</option>
                        <option value="Kamayo">Kamayo</option>
                        <option value="Kankanaey">Kankanaey</option>
                        <option value="Kapampangan">Kapampangan</option>
                        <option value="Kinaray-a">Kinaray-a</option>
                        <option value="Maguindanaon">Maguindanaon</option>
                        <option value="Mamanwa">Mamanwa</option>
                        <option value="Mangyan">Mangyan</option>
                        <option value="Manobo">Manobo</option>
                        <option value="Maranao">Maranao</option>
                        <option value="Masbateño">Masbateño</option>
                        <option value="Matigsalug">Matigsalug</option>
                        <option value="Molbog">Molbog</option>
                        <option value="Negrito">Negrito</option>
                        <option value="Palawan">Palawan</option>
                        <option value="Pangasinan">Pangasinan</option>
                        <option value="Ratagnon">Ratagnon</option>
                        <option value="Sama">Sama</option>
                        <option value="Sangil">Sangil</option>
                        <option value="Subanen">Subanen</option>
                        <option value="Sulod">Sulod</option>
                        <option value="Surigaonon">Surigaonon</option>
                        <option value="Tagakaolo">Tagakaolo</option>
                        <option value="Tagalog">Tagalog</option>
                        <option value="Tagbanwa">Tagbanwa</option>
                        <option value="Tasaday">Tasaday</option>
                        <option value="Tausug">Tausug</option>
                        <option value="Teduray">Teduray</option>
                        <option value="Tiruray">Tiruray</option>
                        <option value="T'boli">T'boli</option>
                        <option value="Waray">Waray</option>
                        <option value="Yakan">Yakan</option>
                        <option value="Zamboangueño">Zamboangueño</option>
                    </select>
                    <input type="hidden" id="race" name="race">
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Location Information Section --}}
<div class="card" style="margin-top:1rem;">
    <div class="card-body" style="padding:1.25rem;">
        <h6 style="text-transform:uppercase;font-size:.85rem;font-weight:700;color:var(--text-muted);margin:0 0 1rem 0;letter-spacing:.08em;border-bottom:1px solid rgba(180,195,210,0.3);padding-bottom:.5rem;">Location Information</h6>
        
        <div style="display:flex;flex-direction:column;gap:1.5rem;">
            
            {{-- Current Address --}}
            <div>
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                    <label style="font-size:.85rem;font-weight:600;margin:0;color:var(--primary-color);">Current Address</label>
                    <label style="font-size:.8rem; color:#555; display:flex; align-items:center; gap:5px; cursor:pointer;">
                        <input type="checkbox" id="caManualToggle" onchange="toggleManualAddress('ca')"> Enter Manually
                    </label>
                </div>
                <div id="caDropdowns" style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:.75rem;">
                    <div>
                        <label style="font-size:.75rem;font-weight:500;margin-bottom:4px;display:block;color:#666;">Region</label>
                        <select id="caRegion" class="select-modern" required style="width:100%;"><option value="">Select Region</option></select>
                    </div>
                    <div>
                        <label style="font-size:.75rem;font-weight:500;margin-bottom:4px;display:block;color:#666;">Province</label>
                        <select id="caProvince" class="select-modern" required style="width:100%;"><option value="">Select Province</option></select>
                    </div>
                    <div>
                        <label style="font-size:.75rem;font-weight:500;margin-bottom:4px;display:block;color:#666;">City/Municipality</label>
                        <select id="caCity" class="select-modern" required style="width:100%;"><option value="">Select City</option></select>
                    </div>
                    <div>
                        <label style="font-size:.75rem;font-weight:500;margin-bottom:4px;display:block;color:#666;">Barangay</label>
                        <select id="caBarangay" class="select-modern" required style="width:100%;"><option value="">Select Barangay</option></select>
                    </div>
                </div>
                <div id="caManual" style="display:none;">
                    <input type="text" id="caManualInput" class="input-modern" style="width:100%;" placeholder="Enter full address manually">
                </div>
            </div>

            {{-- Place of Birth --}}
            <div>
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                    <label style="font-size:.85rem;font-weight:600;margin:0;color:var(--primary-color);">Place of Birth</label>
                    <div style="display:flex; gap:15px;">
                        <label style="font-size:.8rem; color:#555; display:flex; align-items:center; gap:5px; cursor:pointer;">
                            <input type="checkbox" id="pbManualToggle" onchange="toggleManualAddress('pb')"> Enter Manually
                        </label>
                        <label style="font-size:.8rem; color:#555; display:flex; align-items:center; gap:5px; cursor:pointer;">
                            <input type="checkbox" id="sameAsCurrentAddress" onchange="toggleSameAsCurrentAddress()"> Same as Current Address
                        </label>
                    </div>
                </div>
                <div id="pbDropdowns" style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:.75rem;">
                    <div>
                        <label style="font-size:.75rem;font-weight:500;margin-bottom:4px;display:block;color:#666;">Region</label>
                        <select id="pbRegion" class="select-modern" required style="width:100%;"><option value="">Select Region</option></select>
                    </div>
                    <div>
                        <label style="font-size:.75rem;font-weight:500;margin-bottom:4px;display:block;color:#666;">Province</label>
                        <select id="pbProvince" class="select-modern" required style="width:100%;"><option value="">Select Province</option></select>
                    </div>
                    <div>
                        <label style="font-size:.75rem;font-weight:500;margin-bottom:4px;display:block;color:#666;">City/Municipality</label>
                        <select id="pbCity" class="select-modern" required style="width:100%;"><option value="">Select City</option></select>
                    </div>
                    <div>
                        <label style="font-size:.75rem;font-weight:500;margin-bottom:4px;display:block;color:#666;">Barangay</label>
                        <select id="pbBarangay" class="select-modern" required style="width:100%;"><option value="">Select Barangay</option></select>
                    </div>
                </div>
                <div id="pbManual" style="display:none;">
                    <input type="text" id="pbManualInput" class="input-modern" style="width:100%;" placeholder="Enter full place of birth manually">
                </div>
            </div>

            {{-- Testing Office --}}
            <div>
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                    <label style="font-size:.85rem;font-weight:600;margin:0;color:var(--primary-color);">Place of Testing Office</label>
                    <div style="display:flex; gap:15px;">
                        <label style="font-size:.8rem; color:#333; display:flex; align-items:center; gap:5px; cursor:pointer;">
                            <input type="radio" name="testingOfficeType" value="fixed" checked onchange="toggleTestingOffice()"> Fixed
                        </label>
                        <label style="font-size:.8rem; color:#333; display:flex; align-items:center; gap:5px; cursor:pointer;">
                            <input type="radio" name="testingOfficeType" value="mobile" onchange="toggleTestingOffice()"> Mobile
                        </label>
                    </div>
                </div>
                
                <div id="testingOfficeFixedContainer">
                    <select id="placeOfExaminationFixed" class="select-modern" required style="width:100%;">
                        <option value="">Select Office</option>
                        <option value="ARO Luzon">ARO Luzon</option>
                        <option value="ARO Visayas">ARO Visayas</option>
                        <option value="ARO Mindanao">ARO Mindanao</option>
                    </select>
                </div>

                <div id="testingOfficeMobileContainer" style="display:none; grid-template-columns:1fr 1fr 1fr 1fr; gap:.75rem;">
                    <div>
                        <label style="font-size:.75rem;font-weight:500;margin-bottom:4px;display:block;color:#666;">Region</label>
                        <select id="toRegion" class="select-modern" style="width:100%;"><option value="">Select Region</option></select>
                    </div>
                    <div>
                        <label style="font-size:.75rem;font-weight:500;margin-bottom:4px;display:block;color:#666;">Province</label>
                        <select id="toProvince" class="select-modern" style="width:100%;"><option value="">Select Province</option></select>
                    </div>
                    <div>
                        <label style="font-size:.75rem;font-weight:500;margin-bottom:4px;display:block;color:#666;">City/Municipality</label>
                        <select id="toCity" class="select-modern" style="width:100%;"><option value="">Select City</option></select>
                    </div>
                    <div>
                        <label style="font-size:.75rem;font-weight:500;margin-bottom:4px;display:block;color:#666;">Barangay</label>
                        <select id="toBarangay" class="select-modern" style="width:100%;"><option value="">Select Barangay</option></select>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

{{-- Examination Information Section --}}
<div class="card" style="margin-top:1rem;">
    <div class="card-body" style="padding:1.25rem;">
        <h6 style="text-transform:uppercase;font-size:.85rem;font-weight:700;color:var(--text-muted);margin:0 0 1rem 0;letter-spacing:.08em;border-bottom:1px solid rgba(180,195,210,0.3);padding-bottom:.5rem;">Examination Information</h6>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:.75rem;">
            <div>
                <label style="font-size:.8rem;font-weight:500;margin-bottom:4px;display:block;color:#333;">Date of Examination</label>
                <input type="date" id="dateOfExamination" class="input-modern" required style="width:100%;">
            </div>
            <div>
                <label style="font-size:.8rem;font-weight:500;margin-bottom:4px;display:block;color:#333;">Purpose</label>
                <select id="purposeSelect" onchange="document.getElementById('purpose').value = this.value;" class="select-modern" required style="width:100%;">
                    <option value="">Select Purpose</option>
                    <option value="OCC">OCC</option>
                    <option value="CSC">CSC</option>
                </select>
                <input type="hidden" id="purpose" name="purpose">
            </div>
            <div>
                <label style="font-size:.8rem;font-weight:500;margin-bottom:4px;display:block;color:#333;">Batch/Cycle</label>
                <input type="text" id="batch" class="input-modern" placeholder="Enter Batch" required style="width:100%;">
            </div>
            <div>
                <label style="font-size:.8rem;font-weight:500;margin-bottom:4px;display:block;color:#333;">Unit/Organization</label>
                <input type="text" id="unitorg" class="input-modern" placeholder="Enter Unit/Org." required style="width:100%;">
            </div>
        </div>
    </div>
</div>

{{-- Additional Information Section --}}
<div class="card" style="margin-top:1rem;">
    <div class="card-body" style="padding:1.25rem;">
        <h6 style="text-transform:uppercase;font-size:.85rem;font-weight:700;color:var(--text-muted);margin:0 0 1rem 0;letter-spacing:.08em;border-bottom:1px solid rgba(180,195,210,0.3);padding-bottom:.5rem;">Additional Information</h6>
        <div>
            <label style="font-size:.8rem;font-weight:500;margin-bottom:4px;display:block;color:#333;">Other Data</label>
            <textarea id="otherData" class="input-modern" placeholder="Enter Other Data" required style="width:100%;min-height:80px;"></textarea>
        </div>
    </div>
</div>

{{-- Actions Section --}}
<div class="card" style="margin-top:1rem;border:none;background:transparent;box-shadow:none;">
    <div class="card-body" style="padding:.5rem;text-align:center; display:flex; justify-content:center; gap:10px;">
        <button type="button" onclick="generateQR()" class="btn btn-primary btn-lg" style="padding:.8rem 3rem;font-weight:600;border-radius:12px;box-shadow:var(--shadow-sm);">
            <i class="fas fa-qrcode"></i> Generate QR
        </button>
        <button type="button" onclick="openImportModal()" class="btn btn-lg" style="background:var(--white);border:1px solid var(--border-color);color:var(--text-main);padding:.8rem 3rem;font-weight:600;border-radius:12px;box-shadow:var(--shadow-sm);">
            <i class="fas fa-file-import"></i> Import CSV
        </button>
    </div>
</div>

{{-- Camera Modal --}}
<div class="modal-overlay" id="cameraOverlay"></div>
<div class="modal" id="cameraModal" style="width:600px; height:auto; position:relative;">
    <span onclick="closeCameraModal()" style="position:absolute; top:10px; right:15px; font-size:20px; cursor:pointer; color:#333;">&times;</span>
    <h3>Capture Applicant Photo</h3>
    <video id="video" width="500" height="375" autoplay style="border:1px solid var(--border-color); border-radius:var(--radius);"></video>
    <canvas id="canvas" width="500" height="375" style="display:none;"></canvas>
    <div style="margin-top:10px; display:flex; gap:8px; justify-content:center;">
        <button id="captureBtn" class="btn btn-primary">Capture</button>
        <button id="saveBtn" class="btn btn-success">Save</button>
        <button onclick="closeCameraModal()" class="btn btn-danger">Exit</button>
    </div>
</div>

{{-- QR Modal --}}
<div class="modal-overlay" id="modalOverlay"></div>
<div class="modal" id="qrModal">
    <span onclick="closeQrModal()" style="position:absolute; top:10px; right:15px; font-size:24px; cursor:pointer; color:#666;">&times;</span>
    <h3 id="modalTitle" style="margin-top:30px;">Generated QR Code</h3>
    <div id="qrcode"></div>
    <button onclick="closeQrModal(); clearForm();" class="btn btn-primary" style="margin-top:10px; padding:0.6rem 2rem; border-radius:40px;">Done <i class="fas fa-check" style="margin-left:5px;"></i></button>
</div>

{{-- Image Modal --}}
<div id="imageModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:2000; justify-content:center; align-items:center;">
    <span onclick="closeImageModal()" style="position:absolute; top:20px; right:35px; font-size:40px; font-weight:bold; color:white; cursor:pointer;">&times;</span>
    <img id="modalImg" style="max-width:90%; max-height:90%; border-radius:10px; box-shadow:0 0 15px rgba(0,0,0,0.5);">
</div>

{{-- Import Modal --}}
<div class="modal" id="importModal" style="width:400px; height:auto; position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); display:none; background:white; padding:20px; border-radius:10px; z-index:2001; box-shadow:0 10px 25px rgba(0,0,0,0.2);">
    <span onclick="closeImportModal()" style="position:absolute; top:10px; right:15px; font-size:24px; cursor:pointer; color:#666;">&times;</span>
    <h3 style="margin-top:10px; margin-bottom: 15px;">Import Applicants (CSV)</h3>
    
    <div style="margin-bottom: 20px; font-size: 0.85rem; color: #555;">
        Please upload a CSV file with personal information.
        <br><br>
        <strong>Required Columns:</strong> Applicant No, First Name, Last Name.
        <br>
        <strong>Recommended Format:</strong> Applicant No, Rank, Serial No, Branch, Applicant Code, First Name, Middle Name, Last Name, Birthdate, Age, Sex, Race, Place of Birth, Current Address, Place of Examination, Date of Examination, Purpose, Batch, Unit/Org.
    </div>

    <form id="importForm" onsubmit="submitImport(event)">
        <input type="file" id="csvFile" name="file" accept=".csv" class="input-modern" required style="width:100%; margin-bottom: 15px;">
        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 10px; font-weight: bold;">
            <i class="fas fa-upload"></i> Upload & Import
        </button>
    </form>
</div>

@endsection

@push('scripts')
<script src="{{ asset('js/vendor/jquery-3.5.1.min.js') }}"></script>
<script src="{{ asset('js/vendor/select2-4.0.13.min.js') }}"></script>
<script src="{{ asset('js/vendor/qrcode-1.0.0.min.js') }}"></script>

<script>
    let capturedImage = null;
    let savedPhotoPath = null;
    let applicantCounter = 1;
    let cameraStream = null;
    let isCameraActive = false;

    // Import Modal Functions
    function openImportModal() {
        document.getElementById('modalOverlay').style.display = 'block';
        document.getElementById('importModal').style.display = 'block';
    }

    function closeImportModal() {
        document.getElementById('modalOverlay').style.display = 'none';
        document.getElementById('importModal').style.display = 'none';
        document.getElementById('importForm').reset();
    }

    async function submitImport(e) {
        e.preventDefault();
        const fileInput = document.getElementById('csvFile');
        if (!fileInput.files.length) return;

        const formData = new FormData();
        formData.append('file', fileInput.files[0]);

        const submitBtn = e.target.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Importing...';
        submitBtn.disabled = true;

        try {
            const response = await fetch('{{ route("api.patients.import") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: formData
            });
            const data = await response.json();
            
            if (data.success) {
                alert(data.message);
                closeImportModal();
                // Optionally reload or update UI
            } else {
                alert('Import failed: ' + (data.errors ? data.errors.join('\n') : data.message));
            }
        } catch (err) {
            console.error(err);
            alert('An error occurred during import.');
        } finally {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    }

    // Camera functions for inline photo capture
    function startCamera() {
        if (!window.isSecureContext && window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
            alert("Warning: Camera access requires a secure connection (HTTPS). The camera will not load on regular HTTP.");
        }
        
        const video = document.getElementById('video');
        if (!video) return;
        navigator.mediaDevices.getUserMedia({ video: true })
            .then(stream => {
                cameraStream = stream;
                video.srcObject = stream;
                video.play();
                isCameraActive = true;
            })
            .catch(err => {
                console.error("Error accessing camera:", err);
                alert("Could not access camera. Please ensure permissions are granted and you are on a secure connection (HTTPS).");
            });
    }

    function stopCamera() {
        if (cameraStream) {
            cameraStream.getTracks().forEach(track => track.stop());
            cameraStream = null;
        }
        const video = document.getElementById('video');
        if (video) video.srcObject = null;
        isCameraActive = false;
    }

    function captureAndSavePhoto() {
        const video = document.getElementById('video');
        const canvas = document.getElementById('canvas');
        const preview = document.getElementById('photoPreview');
        if (!video || !canvas) return;

        if (!isCameraActive || !video.srcObject) {
            alert("Camera is not active. Please wait for the camera to load.");
            return;
        }

        const ctx = canvas.getContext('2d');
        canvas.width = video.videoWidth || 640;
        canvas.height = video.videoHeight || 480;
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        
        // Compress the image before uploading to save space
        const capturedImageData = canvas.toDataURL('image/jpeg', 0.6);

        // Upload immediately
        fetch('{{ route('api.photo.upload') }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify({ imageData: capturedImageData })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                savedPhotoPath = data.filePath;
                // Show preview, hide video
                preview.src = capturedImageData;
                preview.style.display = 'block';
                video.style.display = 'none';
                alert("Photo captured and saved successfully.");
            } else {
                alert("Error saving photo: " + data.message);
            }
        })
        .catch(err => {
            console.error("Upload failed:", err);
            alert("Upload failed. Check console.");
        });
    }

    function recapturePhoto() {
        savedPhotoPath = null;
        const video = document.getElementById('video');
        const preview = document.getElementById('photoPreview');
        if (preview) preview.style.display = 'none';
        if (video) {
            video.style.display = 'block';
            if (!isCameraActive) startCamera();
        }
    }

    // QR modal functions
    function closeQrModal() {
        document.getElementById('qrModal').style.display = 'none';
        document.getElementById('modalOverlay').style.display = 'none';
    }

    function clearForm() {
        document.getElementById('rankSelect').value = '';
        document.getElementById('rank').value = '';
        document.getElementById('serialNo').value = '';
        document.getElementById('branch').value = '';
        document.getElementById('appcode').value = '';
        document.getElementById('firstName').value = '';
        document.getElementById('middleName').value = '';
        document.getElementById('lastName').value = '';
        document.getElementById('birthdate').value = '';
        document.getElementById('age').value = '';
        document.getElementById('sex').value = 'male';
        document.getElementById('raceSelect').value = '';
        document.getElementById('race').value = '';
        document.getElementById('placeOfBirth').value = '';
        document.getElementById('currentAddress').value = '';
        document.getElementById('placeOfExamination').value = '';
        document.getElementById('dateOfExamination').value = '';
        document.getElementById('purposeSelect').value = '';
        document.getElementById('purpose').value = '';
        document.getElementById('batch').value = '';
        document.getElementById('unitorg').value = '';
        document.getElementById('otherData').value = '';

        savedPhotoPath = null;
        const preview = document.getElementById('photoPreview');
        const video = document.getElementById('video');
        if (preview) preview.style.display = 'none';
        if (video) video.style.display = 'block';
        if (!isCameraActive) startCamera();

        if ($('#placeOfBirth').data('select2')) $('#placeOfBirth').val(null).trigger('change');
        if ($('#currentAddress').data('select2')) $('#currentAddress').val(null).trigger('change');
        if ($('#placeOfExamination').data('select2')) $('#placeOfExamination').val(null).trigger('change');
    }

    // Camera modal (kept for backward compatibility)
    function closeCameraModal() {
        document.getElementById('cameraModal').style.display = 'none';
        document.getElementById('cameraOverlay').style.display = 'none';
        let video = document.getElementById('video');
        let stream = video.srcObject;
        if (stream) {
            let tracks = stream.getTracks();
            tracks.forEach(track => track.stop());
            video.srcObject = null;
        }
    }

    function openCameraModal() {
        document.getElementById('cameraModal').style.display = 'block';
        document.getElementById('cameraOverlay').style.display = 'block';
        navigator.mediaDevices.getUserMedia({ video: true })
            .then(stream => { document.getElementById('video').srcObject = stream; })
            .catch(err => { console.error("Error accessing camera: ", err); alert("Camera access is not available."); });
    }

    document.getElementById('captureBtn').addEventListener('click', function () {
        const video = document.getElementById('video');
        const canvas = document.getElementById('canvas');
        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        capturedImage = canvas.toDataURL('image/jpeg');
        alert("Photo captured in memory. Click Save to store it.");
    });

    document.getElementById('saveBtn').addEventListener('click', function () {
        if (!capturedImage) { alert("Please capture a photo first."); return; }
        fetch('{{ route('api.photo.upload') }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify({ imageData: capturedImage })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                savedPhotoPath = data.filePath;
                alert("Photo saved successfully.");
                closeCameraModal();
            } else {
                alert("Error saving photo: " + data.message);
            }
        })
        .catch(err => { console.error("Upload failed:", err); alert("Upload failed. Check console."); });
    });

    function getNextApplicantNoFromTable() {
        const applicantTable = document.querySelector('.active-data-table tbody');
        if (!applicantTable) return 1;
        const rows = applicantTable.querySelectorAll('tr');
        let maxApplicantNo = 0;
        rows.forEach(row => {
            const applicantNoCell = row.cells[1];
            if (applicantNoCell) {
                const applicantNo = parseInt(applicantNoCell.textContent, 10);
                if (applicantNo > maxApplicantNo) maxApplicantNo = applicantNo;
            }
        });
        return maxApplicantNo + 1;
    }

    // QR Generation & Save Logic
    function generateQR() {
        // 1. Validation
        const requiredFields = [
            { id: 'appcode', name: 'App. Code' },
            { id: 'firstName', name: 'First Name' },
            { id: 'lastName', name: 'Last Name' },
            { id: 'birthdate', name: 'Birthdate' },
            { id: 'sex', name: 'Sex' },
            { id: 'raceSelect', name: 'Religion' },
            { id: 'dateOfExamination', name: 'Date of Examination' },
            { id: 'purposeSelect', name: 'Purpose of Examination' },
            { id: 'batch', name: 'Batch' },
            { id: 'unitorg', name: 'Unit/Org.' }
        ];

        for (const field of requiredFields) {
            const el = document.getElementById(field.id);
            if (!el || !el.value.trim()) {
                alert(`Please fill in the ${field.name} field before generating the QR code.`);
                if (el) el.focus();
                return;
            }
        }

        // Validate complex fields
        const isFixed = document.querySelector('input[name="testingOfficeType"]:checked').value === 'fixed';
        if (isFixed && !document.getElementById('placeOfExaminationFixed').value.trim()) {
            alert('Please fill in the Place of Examination field.');
            return;
        }

        // 2. Build Location Strings
        const getSelectText = (id) => {
            const select = document.getElementById(id);
            return select && select.options[select.selectedIndex] ? select.options[select.selectedIndex].dataset.name || select.options[select.selectedIndex].text : '';
        };

                const buildLocation = (prefix) => {
            const manualToggle = document.getElementById(prefix + 'ManualToggle');
            if (manualToggle && manualToggle.checked) {
                return document.getElementById(prefix + 'ManualInput').value.trim();
            }
            const r = getSelectText(prefix + 'Region');
            const p = getSelectText(prefix + 'Province');
            const c = getSelectText(prefix + 'City');
            const b = getSelectText(prefix + 'Barangay');
            return [b, c, p, r].filter(Boolean).join(', ');
        };

        let currentAddressStr = buildLocation('ca');
        let placeOfBirthStr = document.getElementById('sameAsCurrentAddress').checked ? currentAddressStr : buildLocation('pb');
        
        let placeOfExaminationStr = '';
        if (isFixed) {
            placeOfExaminationStr = document.getElementById('placeOfExaminationFixed').value;
        } else {
            placeOfExaminationStr = buildLocation('to');
        }

        if (!currentAddressStr.trim()) {
            alert('Please fill in the Current Address.');
            return;
        }
        if (!placeOfBirthStr.trim()) {
            alert('Please fill in the Place of Birth.');
            return;
        }

        // 3. Fetch Next Applicant No & Save
        const nextApplicantNoFromTable = getNextApplicantNoFromTable();
        fetch('{{ route('api.patient.next-applicant-no') }}', { headers: { 'Accept': 'application/json' } })
            .then(response => response.json())
            .then(data => {
                const nextApplicantNoFromDB = data.nextApplicantNo;
                applicantCounter = nextApplicantNoFromDB;

                const dataToSave = {
                    applicant_no: applicantCounter,
                    rank: document.getElementById('rankSelect').value,
                    serialNo: document.getElementById('serialNo').value,
                    branch: document.getElementById('branch').value,
                    firstName: document.getElementById('firstName').value,
                    middleName: document.getElementById('middleName').value,
                    lastName: document.getElementById('lastName').value,
                    birthdate: document.getElementById('birthdate').value,
                    age: document.getElementById('age').value,
                    sex: document.getElementById('sex').value,
                    raceSelect: document.getElementById('raceSelect').value,
                    placeOfBirth: placeOfBirthStr,
                    currentAddress: currentAddressStr,
                    placeOfExamination: placeOfExaminationStr,
                    dateOfExamination: document.getElementById('dateOfExamination').value,
                    purpose: document.getElementById('purposeSelect').value,
                    batch: document.getElementById('batch').value,
                    unitorg: document.getElementById('unitorg').value,
                    appcode: document.getElementById('appcode').value,
                    otherData: document.getElementById('otherData').value,
                    status: 'inactive',
                    photo_path: savedPhotoPath ? savedPhotoPath : null
                };

                return fetch('{{ route('api.patient.save') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(dataToSave)
                });
            })
            .then(response => response.json())
            .then(result => {
                if (result.status === 'success') {
                    // 4. Generate & Show QR Code
                    const firstName = document.getElementById('firstName').value;
                    const middleName = document.getElementById('middleName').value;
                    const lastName = document.getElementById('lastName').value;
                    const birthdate = document.getElementById('birthdate').value;
                    const qrData = JSON.stringify({
                        patient_no: result.patient_no
                    });
                    
                    document.getElementById('qrcode').innerHTML = '';
                    new QRCode(document.getElementById('qrcode'), { text: qrData, width: 300, height: 300, correctLevel: QRCode.CorrectLevel.L });
                    var logo = document.createElement("img");
                    logo.src = "apmclogo.jpg";
                    logo.classList.add("logo");
                    document.getElementById('qrcode').appendChild(logo);
                    
                    document.getElementById('modalTitle').textContent = `${firstName} ${lastName}'s QR Code`;
                    document.getElementById('qrModal').style.display = 'block';
                    document.getElementById('modalOverlay').style.display = 'block';
                    
                    alert('Applicant entry successfully created!');
                } else {
                    alert('Failed to save data: ' + (result.message || JSON.stringify(result.errors || result))); console.error(result);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while saving data. ' + error.message);
            });
    }

    function startProcess(button) {
        const row = button.closest('tr');
        const cells = row.cells;
        const data = {
            applicant_no: cells[0].textContent,
            rank: cells[1].textContent,
            serialNo: cells[2].textContent,
            branch: cells[3].textContent,
            firstName: cells[4].textContent,
            middleName: cells[5].textContent,
            lastName: cells[6].textContent,
            birthdate: cells[7].textContent,
            age: cells[8].textContent,
            sex: cells[9].textContent,
            raceSelect: cells[10].textContent,
            placeOfBirth: cells[11].textContent,
            currentAddress: cells[12].textContent,
            placeOfExamination: cells[13].textContent,
            dateOfExamination: cells[14].textContent,
            purpose: cells[15].textContent,
            batch: cells[16].textContent,
            unitorg: cells[17].textContent,
            appcode: cells[18].textContent,
            otherData: cells[19].textContent,
            status: 'inactive',
            photo_path: savedPhotoPath ? savedPhotoPath : null
        };

        fetch('{{ route('api.patient.save') }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            if (result.status === 'success') {
                alert('Data saved successfully!');
            } else {
                alert('Failed to save data: ' + (result.message || JSON.stringify(result.errors || result))); console.error(result);
            }
        })
        .catch(error => { console.error('Error:', error); alert('An error occurred while saving data.'); });
    }

    function removeRow(button) {
        const row = button.closest('tr');
        row.remove();
    }

    function calculateAge() {
        const birthdate = new Date(document.getElementById('birthdate').value);
        const today = new Date();
        let age = today.getFullYear() - birthdate.getFullYear();
        const m = today.getMonth() - birthdate.getMonth();
        if (m < 0 || (m === 0 && today.getDate() < birthdate.getDate())) age--;
        document.getElementById('age').value = age;
    }

    // Active applicant table rendering can be added here when the markup is ready.

    // Image modal
    function enlargeImage(imgElement) {
        const modal = document.getElementById("imageModal");
        const modalImg = document.getElementById("modalImg");
        modal.style.display = "flex";
        modalImg.src = imgElement.src;
    }

    function closeImageModal() {
        document.getElementById("imageModal").style.display = "none";
    }

    window.addEventListener('click', function(event) {
        const modal = document.getElementById("imageModal");
        if (event.target === modal) closeImageModal();
    });

    // PSGC Integration logic
    const PSGC_API = "https://psgc.gitlab.io/api";
    
    async function fetchPSGC(endpoint) {
        try {
            const response = await fetch(`${PSGC_API}${endpoint}`);
            if (!response.ok) return [];
            return await response.json();
        } catch (error) {
            console.error('PSGC Fetch Error:', error);
            return [];
        }
    }

    function populateSelect(selectId, data, placeholder) {
        const select = document.getElementById(selectId);
        select.innerHTML = `<option value="">${placeholder}</option>`;
        data.forEach(item => {
            const option = new Option(item.name, item.code);
            option.dataset.name = item.name;
            select.add(option);
        });
        $(`#${selectId}`).trigger('change.select2');
    }

    async function initLocationSelects(prefix) {
        $(`#${prefix}Region`).select2({ placeholder: 'Select Region', allowClear: true });
        $(`#${prefix}Province`).select2({ placeholder: 'Select Province', allowClear: true });
        $(`#${prefix}City`).select2({ placeholder: 'Select City/Municipality', allowClear: true });
        $(`#${prefix}Barangay`).select2({ placeholder: 'Select Barangay', allowClear: true });

        const regions = await fetchPSGC('/regions.json');
        regions.sort((a,b) => a.name.localeCompare(b.name));
        populateSelect(`${prefix}Region`, regions, 'Select Region');

        $(`#${prefix}Region`).on('change', async function() {
            const regionCode = this.value;
            populateSelect(`${prefix}Province`, [], 'Select Province');
            populateSelect(`${prefix}City`, [], 'Select City/Municipality');
            populateSelect(`${prefix}Barangay`, [], 'Select Barangay');
            if (regionCode) {
                let provinces = await fetchPSGC(`/regions/${regionCode}/provinces.json`);
                provinces.sort((a,b) => a.name.localeCompare(b.name));
                populateSelect(`${prefix}Province`, provinces, 'Select Province');
                
                if (provinces.length === 0) {
                    let cities = await fetchPSGC(`/regions/${regionCode}/cities-municipalities.json`);
                    cities.sort((a,b) => a.name.localeCompare(b.name));
                    populateSelect(`${prefix}City`, cities, 'Select City/Municipality');
                }
            }
            triggerCopyIfSame();
        });

        $(`#${prefix}Province`).on('change', async function() {
            const provinceCode = this.value;
            populateSelect(`${prefix}City`, [], 'Select City/Municipality');
            populateSelect(`${prefix}Barangay`, [], 'Select Barangay');
            if (provinceCode) {
                let cities = await fetchPSGC(`/provinces/${provinceCode}/cities-municipalities.json`);
                cities.sort((a,b) => a.name.localeCompare(b.name));
                populateSelect(`${prefix}City`, cities, 'Select City/Municipality');
            }
            triggerCopyIfSame();
        });

        $(`#${prefix}City`).on('change', async function() {
            const cityCode = this.value;
            populateSelect(`${prefix}Barangay`, [], 'Select Barangay');
            if (cityCode) {
                let barangays = await fetchPSGC(`/cities-municipalities/${cityCode}/barangays.json`);
                barangays.sort((a,b) => a.name.localeCompare(b.name));
                populateSelect(`${prefix}Barangay`, barangays, 'Select Barangay');
            }
            triggerCopyIfSame();
        });
        
        $(`#${prefix}Barangay`).on('change', triggerCopyIfSame);
    }

                    function toggleManualAddress(prefix) {
            const isManual = document.getElementById(prefix + 'ManualToggle').checked;
            
            const selects = [
                document.getElementById(prefix + 'Region'),
                document.getElementById(prefix + 'Province'),
                document.getElementById(prefix + 'City'),
                document.getElementById(prefix + 'Barangay')
            ];

            if (isManual) {
                document.getElementById(prefix + 'Dropdowns').style.display = 'none';
                document.getElementById(prefix + 'Manual').style.display = 'block';
                document.getElementById(prefix + 'ManualInput').required = true;
                selects.forEach(s => { if(s) s.required = false; });
            } else {
                document.getElementById(prefix + 'Dropdowns').style.display = 'grid';
                document.getElementById(prefix + 'Manual').style.display = 'none';
                document.getElementById(prefix + 'ManualInput').required = false;
                selects.forEach(s => { if(s) s.required = true; });
            }
            if(prefix === 'ca') triggerCopyIfSame();
        }

                
        function toggleSameAsCurrentAddress() {
            const isSame = document.getElementById('sameAsCurrentAddress').checked;
            
            // Handle Manual Toggle sync
            const caManualToggle = document.getElementById('caManualToggle');
            const pbManualToggle = document.getElementById('pbManualToggle');
            const caManualInput = document.getElementById('caManualInput');
            const pbManualInput = document.getElementById('pbManualInput');
            
            if (isSame) {
                pbManualToggle.checked = caManualToggle.checked;
                pbManualToggle.disabled = true;
                if (caManualToggle.checked) {
                    pbManualInput.value = caManualInput.value;
                    pbManualInput.disabled = true;
                }
                toggleManualAddress('pb');
            } else {
                pbManualToggle.disabled = false;
                pbManualInput.disabled = false;
            }

            const fields = ['Region', 'Province', 'City', 'Barangay'];
            fields.forEach(field => {
                const caSelect = $(`#ca${field}`);
                const pbSelect = $(`#pb${field}`);
                if (isSame) {
                    pbSelect.html(caSelect.html());
                    pbSelect.val(caSelect.val());
                    pbSelect.prop('disabled', true);
                } else {
                    pbSelect.prop('disabled', false);
                }
                pbSelect.trigger('change.select2');
            });
        }


        function triggerCopyIfSame() {
        if (document.getElementById('sameAsCurrentAddress').checked) {
            toggleSameAsCurrentAddress();
        }
    }

    function toggleTestingOffice() {
        const isFixed = document.querySelector('input[name="testingOfficeType"]:checked').value === 'fixed';
        document.getElementById('testingOfficeFixedContainer').style.display = isFixed ? 'block' : 'none';
        document.getElementById('testingOfficeMobileContainer').style.display = isFixed ? 'none' : 'grid';
        document.getElementById('placeOfExaminationFixed').required = isFixed;
        document.getElementById('toRegion').required = !isFixed;
    }

    document.addEventListener('DOMContentLoaded', function() {
        startCamera();
        
        // Init Fixed Dropdown
        $('#placeOfExaminationFixed').select2({ placeholder: "Select Office", allowClear: true });

        // Init Location fields
        initLocationSelects('ca');
        initLocationSelects('pb');
        initLocationSelects('to');
    });
</script>
@endpush


















