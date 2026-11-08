@extends('layouts.portal')

@section('title', 'Executive Account')

@section('sidebar-nav')
<div class="nav-group-title">Executive</div>
<a href="{{ route('executive.processed') }}" class="nav-item {{ request()->routeIs('executive.processed') ? 'active' : '' }}">
    <i class="fas fa-list-check"></i> Applicant Data
</a>

<a href="#" id="showAnalyticsBtn" class="nav-item">
    <i class="fas fa-chart-line"></i> Show Analytics
</a>
@endsection

@section('header-title', 'Executive Dashboard')

@section('header-actions')
<button type="button" class="btn btn-outline btn-sm" onclick="openPwModal()">
    <i class="fas fa-key"></i> Change Password
</button>
@endsection

@section('content')
{{-- Filters --}}
<form method="get" class="filters" id="filterForm" style="display:flex;justify-content:center;gap:12px;flex-wrap:wrap;margin-bottom:20px;">
    <select name="batch" class="select-modern" style="min-width:160px;" onchange="document.getElementById('filterForm').submit()">
        <option value="" {{ $selectedBatch == '' ? 'selected' : '' }}>-- Select Batch --</option>
        <option value="ALL" {{ $selectedBatch == 'ALL' ? 'selected' : '' }}>All Batches</option>
        @foreach ($batches as $batch)
            <option value="{{ $batch }}" {{ $selectedBatch == $batch ? 'selected' : '' }}>
                {{ $batch }}
            </option>
        @endforeach
    </select>

    <select name="sex" class="select-modern" style="min-width:160px;" onchange="document.getElementById('filterForm').submit()">
        <option value="" {{ $selectedSex == '' ? 'selected' : '' }}>-- Select Sex --</option>
        <option value="Male" {{ $selectedSex == 'Male' ? 'selected' : '' }}>Male</option>
        <option value="Female" {{ $selectedSex == 'Female' ? 'selected' : '' }}>Female</option>
    </select>

    <select name="category" class="select-modern" style="min-width:160px;" onchange="document.getElementById('filterForm').submit()">
        <option value="" {{ $selectedCategory == '' ? 'selected' : '' }}>-- Select Category --</option>
        @foreach ($categoryMap as $key => $column)
            <option value="{{ $key }}" {{ $selectedCategory == $key ? 'selected' : '' }}>
                {{ str_replace('_', '/', $key) }}
            </option>
        @endforeach
    </select>
</form>

@if ($selectedBatch !== '')
    {{-- Summary Stats Grid --}}
    @if ($selectedBatch !== 'ALL')
        <div class="stats-grid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; margin-bottom:24px;">
            @php
                $totalPassed = $summaryCounts[$selectedBatch]['PASSED'] ?? 0;
                $totalDq = $summaryCounts[$selectedBatch]['DQ'] ?? 0;
                $totalOngoing = $summaryCounts[$selectedBatch]['ON GOING'] ?? 0;
            @endphp
            <div class="card" style="text-align:center; padding:2rem; border-bottom: 4px solid #22c55e; border-radius: 20px;">
                <div style="font-size:3rem; font-weight:700; color:var(--text-main); line-height:1; margin-bottom:0.5rem;">{{ $totalPassed }}</div>
                <div style="font-size:0.85rem; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.05em;">PASSED</div>
            </div>
            <div class="card" style="text-align:center; padding:2rem; border-bottom: 4px solid #ef4444; border-radius: 20px;">
                <div style="font-size:3rem; font-weight:700; color:var(--text-main); line-height:1; margin-bottom:0.5rem;">{{ $totalDq }}</div>
                <div style="font-size:0.85rem; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.05em;">DQ</div>
            </div>
            <div class="card" style="text-align:center; padding:2rem; border-bottom: 4px solid #f59e0b; border-radius: 20px;">
                <div style="font-size:3rem; font-weight:700; color:var(--text-main); line-height:1; margin-bottom:0.5rem;">{{ $totalOngoing }}</div>
                <div style="font-size:0.85rem; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.05em;">ON GOING</div>
            </div>
        </div>
    @endif

    {{-- Summary table --}}
    <div class="card" style="margin-bottom:24px;">
        <div class="card-header">
            Batch Summary
            @if ($selectedBatch !== 'ALL')
                for Batch {{ $selectedBatch }}
            @endif
            @if ($selectedSex !== '')
                ({{ $selectedSex }})
            @endif
        </div>
        <div class="card-body" style="padding:0;">
            <div class="table-responsive" style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;min-width:600px;">
                    <thead>
                        <tr>
                            <th>Batch</th>
                            <th>Passed</th>
                            <th>DQ</th>
                            <th>On Going</th>
                            <th>View Chart</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($summaryCounts as $batch => $counts)
                            <tr style="border-bottom:1px solid var(--border-color);">
                                <td style="padding:12px 16px;text-align:center;"><strong>{{ $batch }}</strong></td>
                                <td style="padding:12px 16px;text-align:center;"><span class="badge badge-success filter-status" data-status="PASSED" style="cursor:pointer;">{{ $counts['PASSED'] }}</span></td>
                                <td style="padding:12px 16px;text-align:center;"><span class="badge badge-danger filter-status" data-status="DQ" style="cursor:pointer;">{{ $counts['DQ'] }}</span></td>
                                <td style="padding:12px 16px;text-align:center;"><span class="badge badge-warning filter-status" data-status="ON GOING" style="cursor:pointer;">{{ $counts['ON GOING'] }}</span></td>
                                <td style="padding:12px 16px;text-align:center;">
                                    <button type="button" class="btn btn-primary btn-sm" onclick='openChartModal(@json($batch), @json($sectionBreakdown))'>View</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="padding:20px;text-align:center;color:var(--text-muted);">No summary data available.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Patient grid --}}
    <div class="card">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
            <span>Patient Records</span>
            <div style="display:flex;gap:10px;align-items:center;">
                <input type="text" id="searchInput" class="input-modern" style="width:250px;" placeholder="Search applicants..." onkeyup="filterTable()">
                <button type="button" class="btn btn-primary btn-sm" onclick="exportTableToExcel('statusTable', 'Patient_Status_Report')">
                    <i class="fas fa-file-excel"></i> Export to Excel
                </button>
            </div>
        </div>
        <div class="card-body" style="padding:0;">
            <div class="table-responsive" style="overflow-x:auto;">
                <table id="statusTable" style="width:100%;border-collapse:collapse;min-width:900px;">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Image</th>
                            <th>Batch</th>
                            <th>Patient No</th>
                            <th>App Code</th>
                            <th>First Name</th>
                            <th>Middle Name</th>
                            <th>Last Name</th>
                            <th>Age</th>
                            <th>Sex</th>
                            <th>{{ $selectedCategory ? strtoupper(str_replace('_', '/', $selectedCategory)) : 'Status' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($patients as $i => $patient)
                            @php
                                $approvalColumns = collect(config('stations.stations'))->pluck('approval')->all();
                                $statuses = array_map(fn ($col) => $patient->{$col}, $approvalColumns);
                                $overall = \App\Http\Controllers\ExecutiveController::patientStatus($statuses);
                                $catStatus = $selectedCategory
                                    ? \App\Http\Controllers\ExecutiveController::categoryStatus($patient->{$categoryMap[$selectedCategory]} ?? null)
                                    : null;
                            @endphp
                            <tr class="row-clickable"
                                style="cursor:pointer;"
                                data-name="{{ e($patient->fullName()) }}"
                                data-gpe="{{ e($patient->approvalValue('gpe') ?? '') }}"
                                data-np="{{ e($patient->approvalValue('np') ?? '') }}"
                                data-lab="{{ e($patient->approvalValue('lab_drugtest') ?? '') }}"
                                data-optha="{{ e($patient->approvalValue('optha_eent') ?? '') }}"
                                data-dental="{{ e($patient->approvalValue('dental') ?? '') }}"
                                data-ecg="{{ e($patient->approvalValue('ecg_xray') ?? '') }}">
                                <td style="padding:12px 16px;text-align:center;">{{ $i + 1 }}</td>
                                <td style="padding:12px 16px;text-align:center;">
                                    @php $photo = $patient->photo_url ?: asset('images/blank.jpg'); @endphp
                                    <img src="{{ $photo }}" alt="Photo" style="width:55px;height:55px;border-radius:50%;object-fit:cover;border:2px solid var(--border-color);cursor:pointer;" onclick="event.stopPropagation(); openImgModal('{{ $photo }}')">
                                </td>
                                <td style="padding:12px 16px;text-align:center;">{{ $patient->applications->first()?->batch }}</td>
                                <td style="padding:12px 16px;text-align:center;">{{ $patient->patient_id }}</td>
                                <td style="padding:12px 16px;text-align:center;">{{ $patient->applications->first()?->app_code }}</td>
                                <td style="padding:12px 16px;text-align:center;">{{ $patient->first_name }}</td>
                                <td style="padding:12px 16px;text-align:center;">{{ $patient->middle_name }}</td>
                                <td style="padding:12px 16px;text-align:center;">{{ $patient->last_name }}</td>
                                <td style="padding:12px 16px;text-align:center;">{{ $patient->age }}</td>
                                <td style="padding:12px 16px;text-align:center;">{{ $patient->sex }}</td>
                                <td style="padding:12px 16px;text-align:center;">
                                    @if ($selectedCategory && $catStatus)
                                        <span class="{{ $catStatus['class'] }}">{{ $catStatus['text'] }}</span>
                                    @else
                                        <span class="{{ $overall['class'] }}">{{ $overall['text'] }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" style="padding:40px;text-align:center;color:var(--text-muted);font-style:italic;">No records found for the selected filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@else
    <div class="card" style="text-align:center;padding:40px;">
        <p style="color:var(--text-muted);font-size:1.1rem;">Please select a batch to view results.</p>
    </div>
@endif

{{-- Image Preview Modal --}}
<div id="imgModal" class="modal-overlay" onclick="if(event.target===this)closeImgModal()">
    <div class="modal-content img-modal-box" style="background:transparent;box-shadow:none;border-radius:0;" onclick="event.stopPropagation()">
        <span class="modal-close" onclick="closeImgModal()">&times;</span>
        <img id="imgPreview" src="" alt="Preview" style="max-width:90vw;max-height:90vh;border-radius:8px;box-shadow:0 5px 15px rgba(0,0,0,0.5);">
    </div>
</div>

{{-- Row Detail Modal --}}
<div id="rowModal" class="modal-overlay" onclick="if(event.target===this)closeRowModal()">
    <div class="card" style="width:90%;max-width:500px;" onclick="event.stopPropagation()">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
            <span>Patient Detail</span>
            <span class="modal-close" onclick="closeRowModal()">&times;</span>
        </div>
        <div class="card-body">
            <h2 id="rowName" style="margin-bottom:15px;color:var(--primary-color);text-align:center;"></h2>
            <table style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr>
                        <th style="padding:10px 12px;text-align:left;font-weight:600;font-size:0.8rem;color:var(--text-muted);text-transform:uppercase;border-bottom:1px solid var(--border-color);">Section</th>
                        <th style="padding:10px 12px;text-align:left;font-weight:600;font-size:0.8rem;color:var(--text-muted);text-transform:uppercase;border-bottom:1px solid var(--border-color);">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style="border-bottom:1px solid var(--border-color);"><td style="padding:10px 12px;">GPE</td><td id="rowGpe"></td></tr>
                    <tr style="border-bottom:1px solid var(--border-color);"><td style="padding:10px 12px;">NP</td><td id="rowNp"></td></tr>
                    <tr style="border-bottom:1px solid var(--border-color);"><td style="padding:10px 12px;">Lab/Drugtest</td><td id="rowLab"></td></tr>
                    <tr style="border-bottom:1px solid var(--border-color);"><td style="padding:10px 12px;">Ophtha/EENT</td><td id="rowOptha"></td></tr>
                    <tr style="border-bottom:1px solid var(--border-color);"><td style="padding:10px 12px;">Dental</td><td id="rowDental"></td></tr>
                    <tr><td style="padding:10px 12px;">ECG/Xray</td><td id="rowEcg"></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Chart Modal --}}
<div id="chartModal" class="modal-overlay" onclick="if(event.target===this)closeChartModal()">
    <div class="card" style="width:90%;max-width:800px;max-width:90vw;max-height:85vh;overflow-y:auto;" onclick="event.stopPropagation()">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
            <span>Batch Section Status</span>
            <span class="modal-close" onclick="closeChartModal()">&times;</span>
        </div>
        <div class="card-body">
            <canvas id="chartCanvas" style="width:100%;height:300px;"></canvas>
            <div style="text-align:center;margin-top:15px;">
                <button type="button" class="btn btn-primary" onclick="closeChartModal()">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- Change Password Modal --}}
<div id="pwModal" class="modal-overlay" onclick="if(event.target===this)closePwModal()">
    <div class="card" style="width:90%;max-width:420px;" onclick="event.stopPropagation()">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
            <span>Change Password</span>
            <span class="modal-close" onclick="closePwModal()">&times;</span>
        </div>
        <div class="card-body">
            <form id="pwForm" onsubmit="submitPwChange(event)">
                @csrf
                <div style="margin-bottom:12px;">
                    <label style="display:block;margin-bottom:5px;font-weight:500;">New Password</label>
                    <input type="password" name="newPassword" required class="input-modern">
                </div>
                <div id="pwMsg" style="margin:8px 0;font-size:14px;min-height:20px;"></div>
                <div style="text-align:center;">
                    <button type="submit" class="btn btn-primary" style="width:100%;">Update Password</button>
                </div>
            </form>
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
<script>
    // ===== Status badge class mapping =====
    function getStatusBadgeClass(value) {
        const v = (value || '').toString().trim().toUpperCase();
        if (!v || v === 'NULL') return 'badge badge-neutral';
        if (v === 'GO') return 'badge badge-success';
        if (v === 'NO GO' || v === 'DQ') return 'badge badge-danger';
        if (v === 'COMPLIANCE' || v === 'WAITING FOR RESULT' || v === 'ON GOING') return 'badge badge-warning';
        return 'badge badge-warning';
    }

    function getModalStatusClass(value) {
        const v = (value || '').toString().trim().toUpperCase();
        if (!v || v === 'NULL') return '<span class="badge badge-neutral">-</span>';
        if (v === 'GO') return '<span class="badge badge-success">GO</span>';
        if (v === 'NO GO' || v === 'DQ') return '<span class="badge badge-danger">NO GO</span>';
        if (v === 'COMPLIANCE' || v === 'WAITING FOR RESULT') return '<span class="badge badge-warning">' + v + '</span>';
        return '<span class="badge badge-warning">' + v + '</span>';
    }

    // ===== Search =====
    function filterTable() {
        const filter = document.getElementById('searchInput').value.toLowerCase();
        const rows = document.querySelectorAll('#statusTable tbody tr');
        rows.forEach(row => {
            row.style.display = row.innerText.toLowerCase().includes(filter) ? '' : 'none';
        });
        renumberTable();
    }

    function renumberTable() {
        const rows = document.querySelectorAll('#statusTable tbody tr');
        let counter = 1;
        rows.forEach(row => {
            if (row.style.display !== 'none') {
                row.cells[0].innerText = counter++;
            }
        });
    }

    // ===== Image Modal =====
    function openImgModal(src) {
        document.getElementById('imgPreview').src = src;
        document.getElementById('imgModal').classList.add('active');
    }

    function closeImgModal() {
        document.getElementById('imgModal').classList.remove('active');
    }

    // ===== Row Detail Modal =====
    document.querySelectorAll('.row-clickable').forEach(row => {
        row.addEventListener('click', function(e) {
            if (e.target.tagName.toLowerCase() === 'img') return;
            document.getElementById('rowName').innerText = this.dataset.name;
            document.getElementById('rowGpe').innerHTML = getModalStatusClass(this.dataset.gpe);
            document.getElementById('rowNp').innerHTML = getModalStatusClass(this.dataset.np);
            document.getElementById('rowLab').innerHTML = getModalStatusClass(this.dataset.lab);
            document.getElementById('rowOptha').innerHTML = getModalStatusClass(this.dataset.optha);
            document.getElementById('rowDental').innerHTML = getModalStatusClass(this.dataset.dental);
            document.getElementById('rowEcg').innerHTML = getModalStatusClass(this.dataset.ecg);
            document.getElementById('rowModal').classList.add('active');
        });
    });

    function closeRowModal() {
        document.getElementById('rowModal').classList.remove('active');
    }

    // ===== Summary Click-to-Filter =====
    let activeFilter = null;
    document.querySelectorAll('.filter-status').forEach(cell => {
        cell.addEventListener('click', function() {
            const status = this.dataset.status;
            activeFilter = (activeFilter === status) ? null : status;
            document.querySelectorAll('#statusTable tbody tr').forEach(row => {
                const badge = row.querySelector('td:last-child span');
                if (!badge) return;
                const rowStatus = badge.innerText.trim().toUpperCase();
                row.style.display = (!activeFilter || rowStatus === activeFilter) ? '' : 'none';
            });
            renumberTable();
        });
    });

    // ===== Chart Modal =====
    let chartInstance = null;

    function openChartModal(batch, sectionData) {
        document.getElementById('chartModal').classList.add('active');
        const labels = Object.keys(sectionData);
        const compliance = labels.map(l => sectionData[l]['COMPLIANCE'] || 0);
        const noGo = labels.map(l => sectionData[l]['NO GO'] || 0);
        const dq = labels.map(l => sectionData[l]['DQ'] || 0);
        const go = labels.map(l => sectionData[l]['GO'] || 0);
        const ongoing = labels.map(l => sectionData[l]['ON GOING'] || 0);

        const ctx = document.getElementById('chartCanvas').getContext('2d');
        if (chartInstance) chartInstance.destroy();

        chartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'DQ / NO GO (Red)',
                        data: labels.map((_, i) => dq[i] + noGo[i]),
                        backgroundColor: '#d62828',
                        borderColor: '#b02121',
                        borderWidth: 1
                    },
                    {
                        label: 'GO (Green)',
                        data: go,
                        backgroundColor: '#2d6a4f',
                        borderColor: '#245c43',
                        borderWidth: 1
                    },
                    {
                        label: 'COMPLIANCE (Yellow)',
                        data: compliance,
                        backgroundColor: '#ffba08',
                        borderColor: '#e6a500',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' },
                    title: {
                        display: true,
                        text: 'Section Status Breakdown' + (batch ? ' - Batch ' + batch : '')
                    },
                    datalabels: {
                        anchor: 'end',
                        align: 'top',
                        color: '#333',
                        font: { weight: 'bold', size: 12 },
                        formatter: value => value === 0 ? '' : value
                    }
                },
                scales: {
                    y: { beginAtZero: true, ticks: { maxTicksLimit: 6 } }
                }
            },
            plugins: [ChartDataLabels]
        });
    }

    function closeChartModal() {
        document.getElementById('chartModal').classList.remove('active');
    }

    // ===== Excel Export =====
    function exportTableToExcel(tableID, filename) {
        const table = document.getElementById(tableID);
        const exportTable = document.createElement('table');
        for (let i = 0; i < table.rows.length; i++) {
            if (table.rows[i].style.display === 'none') continue;
            const newRow = exportTable.insertRow();
            for (let j = 0; j < table.rows[i].cells.length; j++) {
                if (j === 1 || j === 4) continue; // skip Image and Appcode
                const newCell = newRow.insertCell();
                newCell.innerHTML = table.rows[i].cells[j].innerText;
            }
        }
        const tableHTML = exportTable.outerHTML.replace(/ /g, '%20');
        const link = document.createElement('a');
        document.body.appendChild(link);
        link.href = 'data:application/vnd.ms-excel,' + tableHTML;
        link.download = (filename || 'excel_data') + '.xls';
        link.click();
        document.body.removeChild(link);
    }

    // ===== Change Password =====
    function openPwModal() {
        document.getElementById('pwModal').classList.add('active');
    }

    function closePwModal() {
        document.getElementById('pwModal').classList.remove('active');
    }

    async function submitPwChange(e) {
        e.preventDefault();
        const msg = document.getElementById('pwMsg');
        msg.style.color = '#333';
        msg.innerText = 'Updating...';
        try {
            const form = document.getElementById('pwForm');
            const formData = new FormData(form);
            const res = await fetch('{{ route("password.executive") }}', {
                method: 'POST',
                body: formData,
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
            });
            const data = await res.json();
            if (data.status === 'success') {
                msg.style.color = '#10b981';
                msg.innerText = (data.message || 'Password updated. Logging out...');
                setTimeout(() => { window.location.href = '{{ route("logout") }}'; }, 1500);
            } else {
                msg.style.color = '#ef4444';
                msg.innerText = data.message || 'Failed to update password.';
            }
        } catch (err) {
            msg.style.color = '#ef4444';
            msg.innerText = 'Network or server error.';
        }
    }

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

