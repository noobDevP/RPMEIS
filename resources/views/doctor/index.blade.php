@extends('layouts.portal')

@section('title', 'Chief Doctor - Pending Evaluations')

@php
    if (!isset($patientsPayload, $batchOptions)) {
        ['patientsPayload' => $patientsPayload, 'batchOptions' => $batchOptions] = app(\App\Services\PatientTablePayloadService::class)
            ->buildDirectoryData($patients, ['mode' => 'doctor_chief']);
    }
@endphp

@section('sidebar-nav')
<div class="nav-group-title">Medical Assessment</div>
<a href="#" class="nav-item" onclick="event.preventDefault();">
    <i class="fas fa-user-doctor"></i> Chief Doctor Evaluation
</a>
<div class="nav-group-title" style="margin-top: 15px;">Management</div>
<a href="#" id="btnManageSigningDoctors" data-action="manage-signing-doctors" class="nav-item" onclick="event.preventDefault(); openSigningDoctorsModal();">
    <i class="fas fa-user-md"></i> Manage Signing Doctors
</a>
<a href="#" id="btnManageApprovingAuthorities" data-action="manage-approving-authorities" class="nav-item" onclick="event.preventDefault(); openApprovingAuthoritiesModal();">
    <i class="fas fa-id-badge"></i> Manage Approving Authorities
</a>
<a href="#" id="btnManageChiefSurgeons" data-action="manage-chief-surgeons" class="nav-item" onclick="event.preventDefault(); openChiefSurgeonsModal();">
    <i class="fas fa-user-tie"></i> Manage Chief Surgeons
</a>
@endsection

@section('header-title', 'Chief Doctor Dashboard')

@section('header-actions')
@endsection

@section('content')
<div class="spec-dashboard">
    <aside class="card queue-panel">
        <div class="queue-header">
            <div class="panel-title">Active Patients</div>
            @include('partials.patient-filters-toggle', ['targetId' => 'doctorChiefFilters', 'storageKey' => 'doctor_chief'])
        </div>

        <div id="doctorChiefFilters" class="filter-collapsible-body is-collapsed" data-filter-body>
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
                                <option value="All">Status: All</option>
                                <option value="Ongoing">Ongoing</option>
                                <option value="Cleared">Cleared</option>
                                <option value="Disqualified">Disqualified</option>
                            </select>
                        </div>
                        <div style="flex: 1; min-width: 240px; position: relative;">
                            <i class="fas fa-search" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
                            <input type="text" id="searchInput" class="input-modern" style="padding-left: 40px;" placeholder="Search Applicant">
                        </div>
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

{{-- Assign Approving Authority Modal --}}
<div id="assignAuthModal" class="modal">
    <div class="modal-content" style="background:var(--white);border-radius:var(--radius);max-width: 500px; width: 90%; padding: 30px;">
        <span class="close" onclick="closeAssignAuthModal()" style="color:#aaa;font-size:28px;font-weight:bold;cursor:pointer;float:right;">&times;</span>
        <h2 style="margin-top:0;color:var(--primary-color);">Assign Approving Authority</h2>
        <form id="assignAuthForm">
            <input type="hidden" id="assignAuthPatientNo" name="patient_no">
            
            <div style="margin-bottom: 24px;">
                <label style="display: block; font-weight: 600; margin-bottom: 8px; color: var(--text-main);">Approving Authority:</label>
                <select id="assignAuthSelect" class="select-modern" style="width: 100%;">
                    <option value="">-- Select Approving Authority --</option>
                    @foreach($approvingAuthorities as $doc)
                        <option value="{{ $doc->signatureLine() }}">{{ $doc->signatureLine() }}</option>
                    @endforeach
                </select>
            </div>

            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" onclick="closeAssignAuthModal()" class="btn btn-secondary">Cancel</button>
                <button type="button" onclick="submitAssignAuth()" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

{{-- Action Modal (Approve/Disqualify) --}}
<div id="actionModal" class="modal">
    <div class="modal-content" style="background:var(--white);border-radius:var(--radius);max-width: 600px; width: 90%; padding: 30px;">
        <span class="close" onclick="closeActionModal()" style="color:#aaa;font-size:28px;font-weight:bold;cursor:pointer;float:right;">&times;</span>
        <h2 id="actionModalTitle" style="margin-top:0;color:var(--primary-color);"></h2>
        <form id="actionForm">
            <input type="hidden" id="actionPatientNo" name="patient_no">
            <input type="hidden" id="actionType" name="action_type">
            <div style="margin-bottom: 16px;">
                <label for="chiefDiagnosis" style="display: block; font-weight: 600; margin-bottom: 8px; color: var(--text-main);">Diagnosis: <span style="color:red;">*</span></label>
                <textarea id="chiefDiagnosis" name="diagnosis" rows="4" required class="input-modern" style="width: 100%;"></textarea>
            </div>
            <div style="margin-bottom: 24px;">
                <label for="chiefRemarks" style="display: block; font-weight: 600; margin-bottom: 8px; color: var(--text-main);">Remarks: <span style="color:red;">*</span></label>
                <textarea id="chiefRemarks" name="remarks" rows="4" required class="input-modern" style="width: 100%;"></textarea>
            </div>
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" onclick="closeActionModal()" class="btn btn-secondary">Cancel</button>
                <button type="button" onclick="submitAction()" class="btn btn-primary">Submit</button>
            </div>
        </form>
    </div>
</div>

{{-- Signing Doctors Modal --}}
<div id="signingDoctorsModal" class="modal">
    <div class="modal-content" style="background:var(--white);border-radius:var(--radius);max-width: 700px; width: 90%; padding: 30px; max-height: 90vh; overflow-y: auto;">
        <span class="close" onclick="closeSigningDoctorsModal()" style="color:#aaa;font-size:28px;font-weight:bold;cursor:pointer;float:right;">&times;</span>
        <h2 style="margin-top:0;color:var(--primary-color);">Manage Signing Doctors</h2>
        
        <div style="margin-bottom: 24px; padding-bottom: 24px; border-bottom: 1px solid var(--border-color);">
            <h3 style="font-size: 1rem; margin-bottom: 12px; color: var(--text-main);">Add New Doctor</h3>
            <form action="{{ route('signing-doctors.store') }}" method="POST" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; align-items: end;">
                @csrf
                <div>
                    <label style="font-size: 0.8rem; font-weight: 600; display: block; margin-bottom: 4px;">Full Name</label>
                    <input type="text" name="name" class="input-modern" required placeholder="Dr. John Doe" style="width: 100%;">
                </div>
                  <div>
                      <label style="font-size: 0.8rem; font-weight: 600; display: block; margin-bottom: 4px;">Rank</label>
                      <input type="text" name="rank" class="input-modern" required placeholder="e.g. Colonel" style="width: 100%;">
                  </div>
                  <div>
                      <label style="font-size: 0.8rem; font-weight: 600; display: block; margin-bottom: 4px;">AFPOS (Optional)</label>
                      <input type="text" name="afpos" class="input-modern" placeholder="e.g. MC" style="width: 100%;">
                  </div>
                <div>
                    <label style="font-size: 0.8rem; font-weight: 600; display: block; margin-bottom: 4px;">Status (Optional)</label>
                    <input type="text" name="status" class="input-modern" placeholder="GSC" style="width: 100%;">
                </div>
                <div>
                    <label style="font-size: 0.8rem; font-weight: 600; display: block; margin-bottom: 4px;">Position</label>
                    <input type="text" name="position" class="input-modern" required placeholder="MEMBER" style="width: 100%;">
                </div>
                <div>
                    <button type="submit" class="btn btn-primary" style="padding: 10px 16px; width: 100%;">Add</button>
                </div>
            </form>
        </div>

        <div>
            <h3 style="font-size: 1rem; margin-bottom: 12px; color: var(--text-main);">Existing Doctors</h3>
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--border-color); color: var(--text-muted); font-size: 0.85rem;">
                        <th style="padding: 8px;">Name</th>
                        <th style="padding: 8px;">Rank</th>
                        <th style="padding: 8px;">AFPOS</th>
                        <th style="padding: 8px;">Status</th>
                        <th style="padding: 8px;">Position</th>
                        <th style="padding: 8px; width: 80px; text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($signingDoctors as $doc)
                    <tr style="border-bottom: 1px solid var(--border-color); font-size: 0.9rem;">
                        <td style="padding: 12px 8px;">{{ $doc->name }}</td>
                        <td style="padding: 12px 8px;">{{ $doc->rank }}</td>
                        <td style="padding: 12px 8px;">{{ $doc->afpos }}</td>
                        <td style="padding: 12px 8px;">{{ $doc->status }}</td>
                        <td style="padding: 12px 8px;">{{ $doc->position }}</td>
                        <td style="padding: 12px 8px; text-align: center;">
                            <form action="{{ route('signing-doctors.destroy', $doc->id) }}" method="POST" onsubmit="return confirm('Delete this doctor?');" style="margin:0;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm" style="background: #fee2e2; color: #dc2626; border: none; padding: 6px 10px; border-radius: 6px; cursor: pointer;">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                    @if($signingDoctors->isEmpty())
                    <tr>
                        <td colspan="6" style="padding: 24px; text-align: center; color: var(--text-muted); font-style: italic;">No signing doctors found.</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Approving Authorities Modal --}}
<div id="approvingAuthoritiesModal" class="modal">
    <div class="modal-content" style="background:var(--white);border-radius:var(--radius);max-width: 700px; width: 90%; padding: 30px; max-height: 90vh; overflow-y: auto;">
        <span class="close" onclick="closeApprovingAuthoritiesModal()" style="color:#aaa;font-size:28px;font-weight:bold;cursor:pointer;float:right;">&times;</span>
        <h2 style="margin-top:0;color:var(--primary-color);">Manage Approving Authorities</h2>
        
        <div style="margin-bottom: 24px; padding-bottom: 24px; border-bottom: 1px solid var(--border-color);">
            <h3 style="font-size: 1rem; margin-bottom: 12px; color: var(--text-main);">Add New Authority</h3>
            <form action="{{ route('approving-authorities.store') }}" method="POST" enctype="multipart/form-data" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; align-items: end;">
                @csrf
                <div style="grid-column: 1 / -1;">
                    <label style="font-size: 0.8rem; font-weight: 600; display: block; margin-bottom: 4px;">Full Name</label>
                    <input type="text" name="name" class="input-modern" required placeholder="enter full name" style="width: 100%;">
                </div>
                <div>
                    <label style="font-size: 0.8rem; font-weight: 600; display: block; margin-bottom: 4px;">Rank</label>
                    <select name="rank" class="select-modern" required style="width: 100%;">
                        <option value="">enter rank</option>
                        @foreach(['Pvt', 'PFC', 'Cpl', 'Sgt', 'SSg', 'TSg', 'MSg', '2LT', '1LT', 'CPT', 'MAJ', 'LTC', 'COL'] as $r)
                            <option value="{{ $r }}">{{ $r }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="font-size: 0.8rem; font-weight: 600; display: block; margin-bottom: 4px;">AFPOS</label>
                    <input type="text" name="afpos" class="input-modern" placeholder="enter afpos" style="width: 100%;">
                </div>
                <div>
                    <label style="font-size: 0.8rem; font-weight: 600; display: block; margin-bottom: 4px;">Status</label>
                    <input type="text" name="status" class="input-modern" placeholder="enter status" style="width: 100%;">
                </div>
                <div style="grid-column: span 2;">
                    <label style="font-size: 0.8rem; font-weight: 600; display: block; margin-bottom: 4px;">Position</label>
                    <input type="text" name="position" class="input-modern" required placeholder="enter position" style="width: 100%;">
                </div>
                <div style="grid-column: 1 / -1;">
                    <label style="font-size: 0.8rem; font-weight: 600; display: block; margin-bottom: 4px;">E-Signature (Transparent PNG)</label>
                    <input type="file" name="e_signature" class="input-modern" accept=".png" style="width: 100%;">
                </div>
                <div style="grid-column: 1 / -1;">
                    <button type="submit" class="btn btn-success" style="width: 100%;">Add Authority</button>
                </div>
            </form>
        </div>

        <div>
            <h3 style="font-size: 1rem; margin-bottom: 12px; color: var(--text-main);">Current Authorities</h3>
            @if(isset($approvingAuthorities) && $approvingAuthorities->count() > 0)
                <div style="display: grid; gap: 12px;">
                    @foreach($approvingAuthorities as $aa)
                        <div style="border: 1px solid {{ $aa->is_default ? 'var(--primary-color)' : 'var(--border-color)' }}; border-radius: var(--radius); padding: 12px; display: flex; justify-content: space-between; align-items: center; background: {{ $aa->is_default ? '#f0fdf4' : '#fafafa' }};">
                            <div>
                                <div style="font-weight: 600; color: var(--text-main); margin-bottom: 4px;">
                                    {{ $aa->signatureLine() }}
                                    @if($aa->is_default)
                                        <span style="font-size:0.75rem; background:var(--primary-color); color:#fff; padding:2px 6px; border-radius:4px; margin-left:8px;">Default</span>
                                    @endif
                                </div>
                                <div style="font-size: 0.85rem; color: var(--text-muted);">
                                    Full Name: {{ $aa->name }} | Rank: {{ $aa->rank }} | AFPOS: {{ $aa->afpos }} | Status: {{ $aa->status }} | Pos: {{ $aa->position }}
                                </div>
                                @if($aa->e_signature_path)
                                <div style="margin-top: 8px;">
                                    <img src="{{ asset('storage/' . $aa->e_signature_path) }}" alt="Signature" style="max-height: 40px; border: 1px dashed #ccc; padding: 2px;">
                                </div>
                                @endif
                            </div>
                            <div style="display: flex; gap: 8px; align-items: center;">
                                @if(!$aa->is_default)
                                <form action="{{ route('approving-authorities.set-default', $aa->is_chief ? 'chief_doctor' : $aa->id) }}" method="POST" style="margin:0;">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit" class="btn btn-outline btn-sm" style="font-size:0.75rem;">Set as Default</button>
                                </form>
                                @endif
                                @if(empty($aa->is_chief))
                                <form action="{{ route('approving-authorities.destroy', $aa->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this authority?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                                </form>
                                @else
                                <span style="font-size:0.8rem;color:#777;font-style:italic;">Chief Doctor</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p style="font-size: 0.9rem; color: var(--text-muted); font-style: italic;">No approving authorities added yet.</p>
            @endif
        </div>
        </div>
    </div>

    {{-- Chief Surgeons (Reviewing Officers) Management Modal --}}
<div id="chiefSurgeonsModal" class="modal">
    <div class="modal-content" style="background:var(--white);border-radius:var(--radius);max-width: 700px; width: 90%; padding: 30px; max-height: 90vh; overflow-y: auto;">
        <span class="close" onclick="closeChiefSurgeonsModal()" style="color:#aaa;font-size:28px;font-weight:bold;cursor:pointer;float:right;">&times;</span>
        <h2 style="margin-top:0;color:var(--primary-color);">Manage Chief Surgeons (Reviewing Officers)</h2>
        
        <div style="margin-bottom: 24px; padding-bottom: 24px; border-bottom: 1px solid var(--border-color);">
            <h3 style="font-size: 1rem; margin-bottom: 12px; color: var(--text-main);">Add New Chief Surgeon</h3>
            <form action="{{ route('chief-surgeons.store') }}" method="POST" enctype="multipart/form-data" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; align-items: end;">
                @csrf
                <div style="grid-column: 1 / -1;">
                    <label style="font-size: 0.8rem; font-weight: 600; display: block; margin-bottom: 4px;">Full Name</label>
                    <input type="text" name="name" class="input-modern" required placeholder="enter full name" style="width: 100%;">
                </div>
                <div>
                    <label style="font-size: 0.8rem; font-weight: 600; display: block; margin-bottom: 4px;">Rank</label>
                    <select name="rank" class="select-modern" required style="width: 100%;">
                        <option value="">enter rank</option>
                        @foreach(['Pvt', 'PFC', 'Cpl', 'Sgt', 'SSg', 'TSg', 'MSg', '2LT', '1LT', 'CPT', 'MAJ', 'LTC', 'COL'] as $r)
                            <option value="{{ $r }}">{{ $r }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="font-size: 0.8rem; font-weight: 600; display: block; margin-bottom: 4px;">AFPOS</label>
                    <input type="text" name="afpos" class="input-modern" placeholder="enter afpos" style="width: 100%;">
                </div>
                <div>
                    <label style="font-size: 0.8rem; font-weight: 600; display: block; margin-bottom: 4px;">Status</label>
                    <input type="text" name="status" class="input-modern" placeholder="enter status" style="width: 100%;">
                </div>
                <div style="grid-column: span 2;">
                    <label style="font-size: 0.8rem; font-weight: 600; display: block; margin-bottom: 4px;">Position</label>
                    <input type="text" name="position" class="input-modern" required value="Chief Surgeon" readonly style="width: 100%; background: #f3f4f6; cursor: not-allowed; color: #6b7280;">
                </div>
                <div style="grid-column: 1 / -1;">
                    <label style="font-size: 0.8rem; font-weight: 600; display: block; margin-bottom: 4px;">E-Signature (Transparent PNG)</label>
                    <input type="file" name="e_signature" class="input-modern" accept=".png" style="width: 100%;">
                </div>
                <div style="grid-column: 1 / -1;">
                    <button type="submit" class="btn btn-success" style="width: 100%;">Add Chief Surgeon</button>
                </div>
            </form>
        </div>

        <div>
            <h3 style="font-size: 1rem; margin-bottom: 12px; color: var(--text-main);">Current Chief Surgeons</h3>
            @if(isset($chiefSurgeons) && $chiefSurgeons->count() > 0)
                <div style="display: grid; gap: 12px;">
                    @foreach($chiefSurgeons as $cs)
                        <div style="border: 1px solid {{ $cs->is_default ? 'var(--primary-color)' : 'var(--border-color)' }}; border-radius: var(--radius); padding: 12px; display: flex; justify-content: space-between; align-items: center; background: {{ $cs->is_default ? '#f0fdf4' : '#fafafa' }};">
                            <div>
                                <div style="font-weight: 600; color: var(--text-main); margin-bottom: 4px;">
                                    {{ $cs->signatureLine() }} 
                                    @if($cs->is_default)
                                        <span style="font-size:0.75rem; background:var(--primary-color); color:#fff; padding:2px 6px; border-radius:4px; margin-left:8px;">Default</span>
                                    @endif
                                </div>
                                <div style="font-size: 0.85rem; color: var(--text-muted);">
                                    Full Name: {{ $cs->name }} | Rank: {{ $cs->rank }} | AFPOS: {{ $cs->afpos }} | Status: {{ $cs->status }} | Pos: {{ $cs->position }}
                                </div>
                                @if($cs->e_signature_path)
                                <div style="margin-top: 8px;">
                                    <img src="{{ asset('storage/' . $cs->e_signature_path) }}" alt="Signature" style="max-height: 40px; border: 1px dashed #ccc; padding: 2px;">
                                </div>
                                @endif
                            </div>
                            <div style="display: flex; gap: 8px; align-items: center;">
                                @if(!$cs->is_default)
                                <form action="{{ route('chief-surgeons.set-default', $cs->id) }}" method="POST" style="margin:0;">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit" class="btn btn-outline btn-sm" title="Set as default for new applicants">Set as Default</button>
                                </form>
                                @endif
                                <form action="{{ route('chief-surgeons.destroy', $cs->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this chief surgeon?');" style="margin:0;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p style="font-size: 0.9rem; color: var(--text-muted); font-style: italic;">No chief surgeons added yet.</p>
            @endif
        </div>
    </div>
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
        if (state.status === 'Ongoing') {
            result = result.filter(p => p.overall_stat === 'NOT DONE');
        } else if (state.status === 'Cleared') {
            result = result.filter(p => p.overall_stat === 'DONE' && p.chief_doctor_approval === 'PASSED');
        } else if (state.status === 'Disqualified') {
            result = result.filter(p => (p.overall_stat === 'DONE' && p.chief_doctor_approval === 'FAILED'));
        }
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

    let stationCardsHtml = '';
    Object.keys(patient.stations).forEach(key => {
        const station = patient.stations[key];
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

            <section class="details-section">
                <div class="details-section-header">Chief Doctor Evaluation</div>
                <div class="details-section-body">
                    <div class="info-grid" style="grid-template-columns: 1fr;">
                        <div class="info-item">
                            <div class="info-label">Status</div>
                            <div class="info-value">
                                ${patient.chief_doctor_approval === 'PASSED' ? '<span class="status-badge-chief status-approved">Approved</span>' : patient.chief_doctor_approval === 'FAILED' ? '<span class="status-badge-chief status-disqualified">Disqualified</span>' : '<span class="status-badge-chief status-pending">Pending</span>'}
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Diagnosis</div>
                            <div class="info-value">${escapeHtml(patient.doctors_diagnosis) || '-'}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Remarks</div>
                            <div class="info-value">${escapeHtml(patient.doctors_remarks) || '-'}</div>
                        </div>
                    </div>
                    <div style="margin-top: 16px; display: flex; gap: 10px; flex-wrap: wrap;">
                        ${(patient.overall_stat === 'NOT DONE' && patient.chief_doctor_approval !== 'PASSED' && patient.chief_doctor_approval !== 'FAILED') ? `
                            ${patient.can_approve ? `
                                <button type="button" class="btn btn-success" data-action="open-action-modal" data-patient-no="${patient.patient_no}" data-action-type="approve" onclick="openActionModal('${patient.patient_no}', 'approve')">
                                    <i class="fas fa-check"></i> Approve Applicant
                                </button>
                            ` : ''}
                            ${patient.can_disqualify ? `
                                <button type="button" class="btn btn-danger" data-action="open-action-modal" data-patient-no="${patient.patient_no}" data-action-type="disqualify" onclick="openActionModal('${patient.patient_no}', 'disqualify')">
                                    <i class="fas fa-times"></i> Disqualify Applicant
                                </button>
                            ` : `
                                <div style="font-size:0.85rem; color:var(--text-muted); font-style:italic; padding: 6px 0;">
                                    Waiting for all specialist doctors to finish assessments before a decision can be made.
                                </div>
                            `}
                        ` : `
                            <button type="button" class="btn btn-warning" data-action="return-applicant" data-patient-no="${patient.patient_no}" onclick="returnApplicant('${patient.patient_no}')">
                                <i class="fas fa-undo"></i> Return Applicant
                            </button>
                        `}
                        ${patient.medical_form_id && patient.medical_form_exists && patient.all_assessments_completed ? `
                            <a href="{{ url('/medical-form/pdf') }}/${patient.medical_form_id}" target="_blank" class="btn btn-primary btn-sm">
                                <i class="fas fa-file-pdf"></i> Download Redform
                            </a>
                        ` : `
                            <button type="button" class="btn btn-secondary btn-sm" disabled title="Red Form not created or specialist assessments incomplete">
                                <i class="fas fa-file-pdf"></i> Download Redform
                            </button>
                        `}
                        <button type="button" class="btn btn-info btn-sm" onclick="openAssignAuthModal('${patient.patient_no}')">
                            <i class="fas fa-id-badge"></i> ${patient.approving_authority_name ? 'Change Authority' : 'Add Approving Authority'}
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

window.addEventListener('click', function (e) {
    if (e.target === document.getElementById('imageModal')) {
        closeImageModal();
    }
});

function openAssignAuthModal(patientNo) {
    const patient = patients.find(p => p.patient_no === patientNo);
    document.getElementById('assignAuthPatientNo').value = patientNo;
    
    document.getElementById('assignAuthSelect').value = patient?.approving_authority_name || '';
    
    document.getElementById('assignAuthModal').style.display = 'flex';
}

function closeAssignAuthModal() {
    document.getElementById('assignAuthModal').style.display = 'none';
}

function submitAssignAuth() {
    const patientNo = document.getElementById('assignAuthPatientNo').value;
    const authName = document.getElementById('assignAuthSelect').value;
    
    saveApprovingAuthority(patientNo, authName);
}

function saveApprovingAuthority(patientNo, authName) {
    fetch(`/api/patients/${patientNo}/approving-authority`, {
        method: 'PUT',
        headers: { 
            'X-CSRF-TOKEN': csrfToken,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ 
            approving_authority: authName
        })
    }).then(response => {
        if (response.ok) {
            alert('Approving authority assigned successfully!');
            const patient = patients.find(p => p.patient_no === patientNo);
            if (patient) {
                patient.approving_authority_name = authName;
            }
            closeAssignAuthModal();
            renderAll();
        } else {
            alert('Failed to assign approving authority.');
        }
    }).catch(err => alert('Network error: ' + err.message));
}

function openActionModal(patientNo, actionType) {
    const patient = patients.find(p => p.patient_no === patientNo);
    const modal = document.getElementById('actionModal');
    const titleEl = document.getElementById('actionModalTitle');
    
    if (actionType === 'approve') {
        titleEl.textContent = 'Edit to Approve Applicant';
    } else {
        titleEl.textContent = 'Disqualify Applicant';
    }
    
    document.getElementById('actionPatientNo').value = patientNo;
    document.getElementById('actionType').value = actionType;
    document.getElementById('chiefDiagnosis').value = patient?.doctors_diagnosis || '';
    document.getElementById('chiefRemarks').value = patient?.doctors_remarks || '';
    
    modal.style.display = 'flex';
}

function closeActionModal() {
    const modal = document.getElementById('actionModal');
    modal.style.display = 'none';
    document.getElementById('actionForm').reset();
}

function submitAction() {
    const patientNo = document.getElementById('actionPatientNo').value;
    const actionType = document.getElementById('actionType').value;
    const diagnosis = document.getElementById('chiefDiagnosis').value.trim();
    const remarks = document.getElementById('chiefRemarks').value.trim();

    if (!diagnosis || !remarks) {
        alert('Please fill in both Diagnosis and Remarks.');
        return;
    }

    // First update doctor notes
    const fdNotes = new FormData();
    fdNotes.append('patient_no', patientNo);
    fdNotes.append('diagnosis', diagnosis);
    fdNotes.append('remarks', remarks);

    fetch('{{ route('api.doctor.update-patient') }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken },
        body: fdNotes
    }).then(response => {
        if (!response.ok) {
            throw new Error('Failed to update notes');
        }
        // If action is approve, call approve endpoint
        if (actionType === 'approve') {
            const fdApprove = new FormData();
            fdApprove.append('patient_no', patientNo);
            return fetch('{{ route('api.patients.approve') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken },
                body: fdApprove
            });
        } else if (actionType === 'disqualify') {
            const fdDisqualify = new FormData();
            fdDisqualify.append('patient_no', patientNo);
            return fetch('{{ route('api.patients.disqualify') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken },
                body: fdDisqualify
            });
        }
        return Promise.resolve({ ok: true });
    }).then(response => {
        if (response.ok) {
            alert('Patient updated successfully!');
            closeActionModal();
            const patient = patients.find(p => p.patient_no === patientNo);
            if (patient) {
                patient.doctors_diagnosis = diagnosis;
                patient.doctors_remarks = remarks;
                if (actionType === 'approve') {
                    patient.chief_doctor_approval = 'PASSED';
                    patient.overall_stat = 'DONE';
                } else if (actionType === 'disqualify') {
                    patient.chief_doctor_approval = 'FAILED';
                    patient.overall_stat = 'DONE';
                }
            }
            renderAll();
        } else {
            alert('Error updating patient.');
        }
    }).catch(error => {
        alert('Network error: ' + error.message);
    });
}

function returnApplicant(patientNo) {
        if (!confirm('Are you sure you want to return this applicant?')) {
            return;
        }
        const fd = new FormData();
        fd.append('patient_no', patientNo);
        fetch('{{ route('api.patients.return') }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            body: fd
        }).then(response => {
            if (response.ok) {
                alert('Applicant returned successfully!');
                const patient = patients.find(p => p.patient_no === patientNo);
                if (patient) {
                    patient.chief_doctor_approval = null;
                    patient.overall_stat = 'NOT DONE';
                }
                renderAll();
            } else {
                alert('Error returning applicant.');
            }
        }).catch(error => {
            alert('Network error.');
        });
    }

function downloadRedForm(patientNo) {
    window.location.href = '/api/patients/' + patientNo + '/pdf';
}

window.addEventListener('click', function(e) {
    const modal = document.getElementById('actionModal');
    if (e.target === modal) {
        closeActionModal();
    }
    if (e.target === document.getElementById('signingDoctorsModal')) {
        closeSigningDoctorsModal();
    }
    if (e.target === document.getElementById('approvingAuthoritiesModal')) {
        closeApprovingAuthoritiesModal();
    }
    if (e.target === document.getElementById('assignAuthModal')) {
        closeAssignAuthModal();
    }
});

function openSigningDoctorsModal() {
    document.getElementById('signingDoctorsModal').style.display = 'flex';
}

function closeSigningDoctorsModal() {
    document.getElementById('signingDoctorsModal').style.display = 'none';
}

function openApprovingAuthoritiesModal() {
    document.getElementById('approvingAuthoritiesModal').style.display = 'flex';
}
function closeApprovingAuthoritiesModal() {
    document.getElementById('approvingAuthoritiesModal').style.display = 'none';
}

function openChiefSurgeonsModal() {
    document.getElementById('chiefSurgeonsModal').style.display = 'flex';
}
function closeChiefSurgeonsModal() {
    document.getElementById('chiefSurgeonsModal').style.display = 'none';
}

// Attach direct event listeners to manage navigation buttons
document.getElementById('btnManageSigningDoctors')?.addEventListener('click', function (e) {
    e.preventDefault();
    openSigningDoctorsModal();
});
document.getElementById('btnManageApprovingAuthorities')?.addEventListener('click', function (e) {
    e.preventDefault();
    openApprovingAuthoritiesModal();
});
document.getElementById('btnManageChiefSurgeons')?.addEventListener('click', function (e) {
    e.preventDefault();
    openChiefSurgeonsModal();
});

// Event delegation for action modals & buttons
document.addEventListener('click', function(e) {
    const actionBtn = e.target.closest('[data-action="open-action-modal"]');
    if (actionBtn) {
        const pNo = actionBtn.getAttribute('data-patient-no');
        const aType = actionBtn.getAttribute('data-action-type');
        if (pNo && aType) {
            openActionModal(pNo, aType);
        }
        return;
    }
    const returnBtn = e.target.closest('[data-action="return-applicant"]');
    if (returnBtn) {
        const pNo = returnBtn.getAttribute('data-patient-no');
        if (pNo) {
            returnApplicant(pNo);
        }
        return;
    }
});

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

