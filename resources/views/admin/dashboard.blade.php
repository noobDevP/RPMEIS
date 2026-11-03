@extends('layouts.portal')

@section('title', 'Admin Dashboard')

@section('sidebar-nav')
<div class="nav-group-title">Administration</div>
<a href="{{ route('admin.dashboard') }}" class="nav-item {{ request()->is('admin/dashboard') || request()->is('admin/dashboard/*') ? 'active' : '' }}">Users Masterlist</a>
@endsection

@section('header-title', 'Admin Dashboard')



@section('content')
{{-- Flash messages --}}
@if (session('success'))
    <div class="card" style="padding:16px 20px;margin-bottom:20px;background:#d1fae5;color:#065f46;border:1px solid #a7f3d0;border-radius:var(--radius);">
        {{ session('success') }}
    </div>
@endif

{{-- Users Masterlist --}}
<div class="card" style="margin-bottom:24px;">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
        <span>Users Masterlist</span>
        <button type="button" class="btn btn-primary" onclick="openCreateUserModal()">
            <i class="fas fa-plus"></i> Create User
        </button>
    </div>
    <div class="card-body" style="padding:0;">
        <div class="table-responsive" style="max-height:400px;overflow-y:auto;overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;min-width:600px;">
                <thead>
                    <tr>
                        <th>Admin #</th>
                        <th>Portal Access</th>
                                                <th>Username</th>
                        <th>Last Login Date</th>
                        <th>Last Login IP</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr onclick="openEditUserModal({{ $user->user_id }}, '{{ addslashes($user->username) }}', '{{ addslashes($user->user_username) }}', '{{ addslashes($user->rank) }}', '{{ addslashes($user->afpos) }}', '{{ addslashes($user->status) }}', '{{ addslashes($user->position) }}')" style="cursor:pointer;">
                            <td style="padding:12px 16px;text-align:center;">{{ $user->user_id }}</td>
                            <td style="padding:12px 16px;text-align:center;">{{ $user->username }}</td>
                            <td style="padding:12px 16px;text-align:center;">{{ $user->user_username }}</td>
                            <td style="padding:12px 16px;text-align:center;">{{ $user->last_login_at ? $user->last_login_at->format('M d, Y h:i A') : 'Never' }}</td>
                            <td style="padding:12px 16px;text-align:center;">{{ $user->last_login_ip ?? '-' }}</td>
                            <td style="padding:12px 16px;text-align:center;">
                                <form action="{{ route('admin.users.destroy', $user->user_id) }}" method="POST" style="display:inline" onsubmit="return confirm('Are you sure you want to delete this user?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="btn btn-secondary btn-sm" onclick="event.stopPropagation(); openEditUserModal({{ $user->user_id }}, '{{ addslashes($user->username) }}', '{{ addslashes($user->user_username) }}', '{{ addslashes($user->rank) }}', '{{ addslashes($user->afpos) }}', '{{ addslashes($user->status) }}', '{{ addslashes($user->position) }}')" style="margin-right: 5px; background: #6b7280; color: white;">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="event.stopPropagation();">
                                        <i class="fas fa-trash-alt"></i> Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="padding:20px;text-align:center;color:var(--text-muted);">No users found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>



{{-- Create User Modal --}}
<div id="createUserModal" class="modal-overlay" onclick="if(event.target===this)closeCreateUserModal()">
    <div class="card" style="width:90%;max-width:420px;" onclick="event.stopPropagation()">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
            <span>Create New User</span>
            <span class="modal-close" onclick="closeCreateUserModal()">&times;</span>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.users.store') }}" method="POST">
                @csrf
                <div style="margin-bottom:12px;">
                    <label style="display:block;margin-bottom:5px;font-weight:500;">Portal / Page Access</label>
                    <select name="username" class="select-modern" required>
                        <option value="" disabled selected>Select Portal Access</option>
                        <option value="RECRUITER">Recruiter Portal</option>
                        <option value="REGISTRAR">Registrar Portal</option>
                        <option value="DENTAL">Clerk Station - Dental</option>
                        <option value="EENT">Clerk Station - EENT</option>
                        <option value="ECG">Clerk Station - ECG</option>
                        <option value="NP">Clerk Station - NP</option>
                        <option value="LABORATORY">Clerk Station - Laboratory</option>
                        <option value="GPE">Clerk Station - GPE</option>
                        <option value="DOCTOR">Doctor (Generic)</option>
                        <option value="DOCTOR_GPE">Specialist Doctor - GPE</option>
                        <option value="DOCTOR_DENTAL">Specialist Doctor - Dental</option>
                        <option value="DOCTOR_NP">Specialist Doctor - Neuro/Psych</option>
                        <option value="DOCTOR_ECG_XRAY">Specialist Doctor - ECG / X-Ray</option>
                        <option value="DOCTOR_OPTHA_EENT">Specialist Doctor - Optha / EENT</option>
                        <option value="DOCTOR_LAB_DRUGTEST">Specialist Doctor - Lab / Drugtest</option>
                        <option value="EXECUTIVE">Chief Doctor / Executive Dashboard</option>
                    </select>
                </div>
                <div style="margin-bottom:12px;">
                    <label style="display:block;margin-bottom:5px;font-weight:500;">Rank (Abbreviation)</label>
                    <select name="rank" class="select-modern" style="width: 100%;">
                          <option value="">- None -</option>
                          @foreach(['PVT', 'PFC', 'CPL', 'SGT', 'SSG', 'TSG', 'MSG', '2LT', '1LT', 'CPT', 'MAJ', 'LTC', 'COL'] as $r)
                              <option value="{{ $r }}">{{ $r }}</option>
                          @endforeach
                      </select>
                </div>
                <div style="margin-bottom:12px;">
                    <label style="display:block;margin-bottom:5px;font-weight:500;">Full Name</label>
                    <input type="text" name="user_username" class="input-modern" placeholder="Username or Full Name" required>
                </div>
                <div style="margin-bottom:12px;">
                    <label style="display:block;margin-bottom:5px;font-weight:500;">AFPOS</label>
                    <input type="text" name="afpos" class="input-modern" placeholder="e.g. MC">
                </div>
                <div style="margin-bottom:12px;">
                    <label style="display:block;margin-bottom:5px;font-weight:500;">Status (Optional)</label>
                    <input type="text" name="status" class="input-modern" placeholder="e.g. GSC">
                </div>
                <div style="margin-bottom:12px;">
                    <label style="display:block;margin-bottom:5px;font-weight:500;">Position</label>
                    <input type="text" name="position" class="input-modern" placeholder="e.g. MEMBER">
                </div>
                <div style="margin-bottom:16px;">
                    <label style="display:block;margin-bottom:5px;font-weight:500;">Password</label>
                    <input type="password" name="password" class="input-modern" placeholder="Password" required>
                </div>
                <button type="submit" class="btn btn-success" style="width:100%;">Create</button>
            </form>
        </div>
    </div>
</div>

{{-- Edit User Modal --}}
<div id="editUserModal" class="modal-overlay" onclick="if(event.target===this)closeEditUserModal()">
    <div class="card" style="width:90%;max-width:420px;" onclick="event.stopPropagation()">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
            <span>Edit User</span>
            <span class="modal-close" onclick="closeEditUserModal()">&times;</span>
        </div>
        <div class="card-body">
            <form id="editUserForm" action="" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="user_id" id="edit_user_id">
                <div style="margin-bottom:12px;">
                    <label style="display:block;margin-bottom:5px;font-weight:500;">Portal / Page Access</label>
                    <select name="username" id="edit_username" class="select-modern" required>
                        <option value="" disabled>Select Portal Access</option>
                        <option value="RECRUITER">Recruiter Portal</option>
                        <option value="REGISTRAR">Registrar Portal</option>
                        <option value="DENTAL">Clerk Station - Dental</option>
                        <option value="EENT">Clerk Station - EENT</option>
                        <option value="ECG">Clerk Station - ECG</option>
                        <option value="NP">Clerk Station - NP</option>
                        <option value="LABORATORY">Clerk Station - Laboratory</option>
                        <option value="GPE">Clerk Station - GPE</option>
                        <option value="DOCTOR">Doctor (Generic)</option>
                        <option value="DOCTOR_GPE">Specialist Doctor - GPE</option>
                        <option value="DOCTOR_DENTAL">Specialist Doctor - Dental</option>
                        <option value="DOCTOR_NP">Specialist Doctor - Neuro/Psych</option>
                        <option value="DOCTOR_ECG_XRAY">Specialist Doctor - ECG / X-Ray</option>
                        <option value="DOCTOR_OPTHA_EENT">Specialist Doctor - Optha / EENT</option>
                        <option value="DOCTOR_LAB_DRUGTEST">Specialist Doctor - Lab / Drugtest</option>
                        <option value="EXECUTIVE">Chief Doctor / Executive Dashboard</option>
                    </select>
                </div>
                <div style="margin-bottom:12px;">
                    <label style="display:block;margin-bottom:5px;font-weight:500;">Rank (Abbreviation)</label>
                    <select name="rank" id="edit_rank" class="select-modern" style="width: 100%;">
                          <option value="">- None -</option>
                          @foreach(['PVT', 'PFC', 'CPL', 'SGT', 'SSG', 'TSG', 'MSG', '2LT', '1LT', 'CPT', 'MAJ', 'LTC', 'COL'] as $r)
                              <option value="{{ $r }}">{{ $r }}</option>
                          @endforeach
                      </select>
                </div>
                <div style="margin-bottom:12px;">
                    <label style="display:block;margin-bottom:5px;font-weight:500;">Full Name</label>
                    <input type="text" name="user_username" id="edit_user_username" class="input-modern" placeholder="Username or Full Name" required>
                </div>
                <div style="margin-bottom:12px;">
                    <label style="display:block;margin-bottom:5px;font-weight:500;">AFPOS</label>
                    <input type="text" name="afpos" id="edit_afpos" class="input-modern" placeholder="e.g. MC">
                </div>
                <div style="margin-bottom:12px;">
                    <label style="display:block;margin-bottom:5px;font-weight:500;">Status (Optional)</label>
                    <input type="text" name="status" id="edit_status" class="input-modern" placeholder="e.g. GSC">
                </div>
                <div style="margin-bottom:12px;">
                    <label style="display:block;margin-bottom:5px;font-weight:500;">Position</label>
                    <input type="text" name="position" id="edit_position" class="input-modern" placeholder="e.g. MEMBER">
                </div>
                <div style="margin-bottom:12px;">
                    <label style="display:block;margin-bottom:5px;font-weight:500;">Password</label>
                    <input type="password" name="password" class="input-modern" placeholder="Leave blank to keep current">
                </div>
                <button type="submit" class="btn btn-success" style="width:100%;">Update</button>
            </form>
        </div>
    </div>
</div>



{{-- Create Signing Doctor Modal --}}
<div id="createSigningDoctorModal" class="modal-overlay" onclick="if(event.target===this)closeCreateSigningDoctorModal()">
    <div class="card" style="width:90%;max-width:500px;" onclick="event.stopPropagation()">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
            <span>Create Approving Authority / Signing Officer</span>
            <span class="modal-close" onclick="closeCreateSigningDoctorModal()">&times;</span>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.signing-doctors.store') }}" method="POST" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                @csrf
                <div style="grid-column: span 2;">
                    <label style="display:block;margin-bottom:5px;font-weight:500;">Full Name</label>
                    <input type="text" name="name" class="input-modern" placeholder="Dr. John Doe" required style="width:100%;">
                </div>
                <div>
                    <label style="display:block;margin-bottom:5px;font-weight:500;">Rank</label>
                    <select name="rank" class="select-modern" required style="width: 100%;">
                          <option value="">- Select Rank -</option>
                          @foreach(['PVT', 'PFC', 'CPL', 'SGT', 'SSG', 'TSG', 'MSG', '2LT', '1LT', 'CPT', 'MAJ', 'LTC', 'COL'] as $r)
                              <option value="{{ $r }}">{{ $r }}</option>
                          @endforeach
                      </select>
                </div>
                <div>
                    <label style="display:block;margin-bottom:5px;font-weight:500;">AFPOS</label>
                    <input type="text" name="afpos" class="input-modern" placeholder="e.g. MC" style="width:100%;">
                </div>
                <div>
                    <label style="display:block;margin-bottom:5px;font-weight:500;">Status</label>
                    <input type="text" name="status" class="input-modern" placeholder="e.g. (GSC) PA" style="width:100%;">
                </div>
                <div>
                    <label style="display:block;margin-bottom:5px;font-weight:500;">Position</label>
                    <input type="text" name="position" class="input-modern" placeholder="e.g. Chief Surgeon" required style="width:100%;">
                </div>
                <div style="grid-column: span 2; margin-top: 12px;">
                    <button type="submit" class="btn btn-success" style="width:100%;">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Signing Doctor Modal --}}
<div id="editSigningDoctorModal" class="modal-overlay" onclick="if(event.target===this)closeEditSigningDoctorModal()">
    <div class="card" style="width:90%;max-width:500px;" onclick="event.stopPropagation()">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
            <span>Edit Approving Authority / Signing Officer</span>
            <span class="modal-close" onclick="closeEditSigningDoctorModal()">&times;</span>
        </div>
        <div class="card-body">
            <form id="editSigningDoctorForm" action="" method="POST" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                @csrf
                @method('PUT')
                <div style="grid-column: span 2;">
                    <label style="display:block;margin-bottom:5px;font-weight:500;">Full Name</label>
                    <input type="text" name="name" id="edit_sd_name" class="input-modern" required style="width:100%;">
                </div>
                <div>
                    <label style="display:block;margin-bottom:5px;font-weight:500;">Rank</label>
                    <select name="rank" id="edit_sd_rank" class="select-modern" required style="width: 100%;">
                          <option value="">- Select Rank -</option>
                          @foreach(['PVT', 'PFC', 'CPL', 'SGT', 'SSG', 'TSG', 'MSG', '2LT', '1LT', 'CPT', 'MAJ', 'LTC', 'COL'] as $r)
                              <option value="{{ $r }}">{{ $r }}</option>
                          @endforeach
                      </select>
                </div>
                <div>
                    <label style="display:block;margin-bottom:5px;font-weight:500;">AFPOS</label>
                    <input type="text" name="afpos" id="edit_sd_afpos" class="input-modern" style="width:100%;">
                </div>
                <div>
                    <label style="display:block;margin-bottom:5px;font-weight:500;">Status</label>
                    <input type="text" name="status" id="edit_sd_status" class="input-modern" style="width:100%;">
                </div>
                <div>
                    <label style="display:block;margin-bottom:5px;font-weight:500;">Position</label>
                    <input type="text" name="position" id="edit_sd_position" class="input-modern" required style="width:100%;">
                </div>
                <div style="grid-column: span 2; margin-top: 12px;">
                    <button type="submit" class="btn btn-success" style="width:100%;">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>




{{-- System Activity Logs --}}
<div class="card" style="margin-top: 24px; margin-bottom: 24px;">
    <div class="card-header">
        <span>System Activity Logs</span>
    </div>
    <div class="card-body" style="padding:0;">
        <div class="table-responsive" style="max-height:400px;overflow-y:auto;overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;min-width:800px;">
                <thead style="background:#f3f4f6;position:sticky;top:0;z-index:1;">
                    <tr>
                        <th style="padding:12px 16px;text-align:center;font-weight:600;color:#374151;border-bottom:2px solid #e5e7eb;">Date & Time</th>
                        <th style="padding:12px 16px;text-align:center;font-weight:600;color:#374151;border-bottom:2px solid #e5e7eb;">User</th>
                        <th style="padding:12px 16px;text-align:center;font-weight:600;color:#374151;border-bottom:2px solid #e5e7eb;">Role</th>
                        <th style="padding:12px 16px;text-align:center;font-weight:600;color:#374151;border-bottom:2px solid #e5e7eb;">Action</th>
                        <th style="padding:12px 16px;text-align:center;font-weight:600;color:#374151;border-bottom:2px solid #e5e7eb;">Applicant No</th>
                        <th style="padding:12px 16px;text-align:center;font-weight:600;color:#374151;border-bottom:2px solid #e5e7eb;">IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($activityLogs ?? [] as $log)
                        <tr style="border-bottom:1px solid #e5e7eb;">
                            <td style="padding:12px 16px;text-align:center;">{{ $log->created_at->format('Y-m-d h:i A') }}</td>
                            <td style="padding:12px 16px;text-align:center;font-weight:500;">{{ $log->user_username ?? 'System' }}</td>
                            <td style="padding:12px 16px;text-align:center;">{{ $log->role ?? '-' }}</td>
                            <td style="padding:12px 16px;text-align:center;"><span class="badge" style="background:#e0f2fe;color:#0369a1;padding:4px 8px;border-radius:4px;font-size:0.85rem;font-weight:500;">{{ $log->action }}</span></td>
                            <td style="padding:12px 16px;text-align:center;">{{ $log->patient_no ?? '-' }}</td>
                            <td style="padding:12px 16px;text-align:center;font-family:monospace;color:#4b5563;">{{ $log->ip_address ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding:20px;text-align:center;color:#6b7280;">No activity logs found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function openCreateUserModal() {
        document.getElementById('createUserModal').classList.add('active');
    }
    function closeCreateUserModal() {
        document.getElementById('createUserModal').classList.remove('active');
    }

    function openEditUserModal(userId, username, userUsername, rank, afpos, status, position) {
        document.getElementById('edit_user_id').value = userId;
        document.getElementById('edit_username').value = username;
        document.getElementById('edit_user_username').value = userUsername;
        document.getElementById('edit_rank').value = rank;
        document.getElementById('edit_afpos').value = afpos;
        document.getElementById('edit_status').value = status;
        document.getElementById('edit_position').value = position;
        document.getElementById('editUserForm').action = '/admin/users/' + userId;
        document.getElementById('editUserModal').classList.add('active');
    }
    function closeEditUserModal() {
        document.getElementById('editUserModal').classList.remove('active');
    }

    function openCreateSigningDoctorModal() {
        document.getElementById('createSigningDoctorModal').classList.add('active');
    }
    function closeCreateSigningDoctorModal() {
        document.getElementById('createSigningDoctorModal').classList.remove('active');
    }
    
    function openEditSigningDoctorModal(id, name, rank, afpos, status, position) {
        document.getElementById('edit_sd_name').value = name;
        document.getElementById('edit_sd_rank').value = rank;
        document.getElementById('edit_sd_afpos').value = afpos;
        document.getElementById('edit_sd_status').value = status;
        document.getElementById('edit_sd_position').value = position;
        document.getElementById('editSigningDoctorForm').action = '/admin/signing-doctors/' + id;
        document.getElementById('editSigningDoctorModal').classList.add('active');
    }
    function closeEditSigningDoctorModal() {
        document.getElementById('editSigningDoctorModal').classList.remove('active');
    }
</script>
@endpush
