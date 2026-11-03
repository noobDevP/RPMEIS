@extends('layouts.portal')

@section('title', 'Registrar - Archived Batches')

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

@section('header-title', 'Archived Batches')

@section('content')
<div class="spec-dashboard">
    <div class="card" style="width: 100%;">
        <div class="card-header">
            <h3><i class="fas fa-archive"></i> Inactive Batches</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>Batch Name</th>
                            <th>Total Applicants</th>
                            <th style="width: 250px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($batches as $batch)
                            <tr>
                                <td style="font-weight: 600;">{{ $batch->batch }}</td>
                                <td>{{ $batch->total_patients }}</td>
                                <td>
                                    <a href="{{ route('registrar.archives.view', $batch->batch) }}" class="btn btn-secondary btn-sm" style="background-color: #64748b; color: white; text-decoration: none; padding: 6px 12px; border-radius: 4px; margin-right: 4px;">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <button class="btn btn-primary btn-sm" onclick="confirmMarkActive('{{ $batch->batch }}')">
                                        <i class="fas fa-undo"></i> Mark Active
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="confirmDeleteBatch('{{ $batch->batch }}')" style="background-color: #ef4444; color: white;">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" style="text-align: center; padding: 2rem;">No archived batches found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Mark Active -->
<div class="modal-overlay" id="markActiveModal" style="display: none; align-items: center; justify-content: center;">
    <div class="modal-content" style="max-width: 400px; padding: 24px; border-radius: 12px; background: white; text-align: center;">
        <h3 style="margin-top: 0; color: #1e293b;">Mark Batch Active</h3>
        <p style="color: #64748b; margin-bottom: 24px;">Are you sure you want to unarchive <strong id="activeBatchName"></strong>? This will restore all applicants in this batch to the active queues.</p>
        <div style="display: flex; gap: 12px; justify-content: center;">
            <button class="btn btn-secondary" onclick="closeModal('markActiveModal')">Cancel</button>
            <button class="btn btn-primary" onclick="submitMarkActive()">Confirm</button>
        </div>
    </div>
</div>

<!-- Modal Delete -->
<div class="modal-overlay" id="deleteBatchModal" style="display: none; align-items: center; justify-content: center;">
    <div class="modal-content" style="max-width: 400px; padding: 24px; border-radius: 12px; background: white; text-align: center;">
        <div style="width: 48px; height: 48px; border-radius: 50%; background: #fee2e2; color: #ef4444; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; font-size: 24px;">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h3 style="margin-top: 0; color: #1e293b;">Delete Batch</h3>
        <p style="color: #64748b; margin-bottom: 24px;">Are you absolutely sure you want to delete <strong id="deleteBatchName"></strong>? This action is <strong style="color: #ef4444;">permanent</strong> and cannot be undone. All applicant data for this batch will be erased.</p>
        <div style="display: flex; gap: 12px; justify-content: center;">
            <button class="btn btn-secondary" onclick="closeModal('deleteBatchModal')">Cancel</button>
            <button class="btn btn-danger" onclick="submitDeleteBatch()" style="background-color: #ef4444; color: white; border:none; border-radius:6px; padding: 0.5rem 1rem; font-weight: 500;">Yes, Delete It</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
    let selectedBatch = '';

    function confirmMarkActive(batch) {
        selectedBatch = batch;
        document.getElementById('activeBatchName').innerText = batch;
        document.getElementById('markActiveModal').style.display = 'flex';
    }

    function confirmDeleteBatch(batch) {
        selectedBatch = batch;
        document.getElementById('deleteBatchName').innerText = batch;
        document.getElementById('deleteBatchModal').style.display = 'flex';
    }

    function closeModal(id) {
        document.getElementById(id).style.display = 'none';
        selectedBatch = '';
    }

    async function submitMarkActive() {
        if (!selectedBatch) return;
        
        try {
            const res = await fetch('{{ route('registrar.archives.mark-active') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ batch: selectedBatch })
            });
            const data = await res.json();
            if (data.success) {
                alert(data.message);
                window.location.reload();
            } else {
                alert('Error: ' + (data.message || 'Unknown error'));
            }
        } catch (e) {
            alert('An error occurred.');
        }
    }

    async function submitDeleteBatch() {
        if (!selectedBatch) return;
        
        try {
            const res = await fetch('{{ route('registrar.archives.delete') }}', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ batch: selectedBatch })
            });
            const data = await res.json();
            if (data.success) {
                alert(data.message);
                window.location.reload();
            } else {
                alert('Error: ' + (data.message || 'Unknown error'));
            }
        } catch (e) {
            alert('An error occurred.');
        }
    }
</script>
@endpush
@endsection
