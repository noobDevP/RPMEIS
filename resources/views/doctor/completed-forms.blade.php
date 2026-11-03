@extends('layouts.portal')

@section('title', 'Completed Red Forms - Doctor Portal')

@section('sidebar-nav')
<div class="nav-group-title">Medical Assessment</div>
<a href="{{ route('doctor.index') }}" class="nav-item {{ request()->is('doctor') && !request()->is('doctor/*') ? 'active' : '' }}">
    <i class="fas fa-list"></i> Patient Evaluations
</a>
<a href="{{ route('doctor.completed-forms') }}" class="nav-item {{ request()->is('doctor/completed-forms') ? 'active' : '' }}">
    <i class="fas fa-file-medical"></i> Red Forms
</a>
<a href="{{ route('doctor.logs') }}" class="nav-item {{ request()->is('doctor/logs') ? 'active' : '' }}">
    <i class="fas fa-history"></i> Upload History
</a>
@endsection

@section('header-title', 'Red Forms')

@section('content')
<div class="card">
    <div class="card-header">all Red Forms</div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th>Patient No</th>
                        <th>Name</th>
                        <th>Batch</th>
                        <th>Action</th>
                    </tr>
                </thead>
            <tbody>
                @forelse($patients as $patient)
                    <tr>
                        <td>{{ $patient->patient_id }}</td>
                        <td>{{ $patient->fullName() }}</td>
                        <td>{{ $patient->applications->first()?->batch }}</td>
                        <td>
                            @php
                                $exam = $patient->applications->first()?->latestExamination();
                            @endphp
                            @if($exam && $exam->allAssessmentsCompleted())
                                <a href="{{ route('medical-form.pdf', $exam->examination_id) }}" target="_blank" class="btn btn-primary btn-sm">
                                    <i class="fas fa-file-pdf"></i> Download PDF
                                </a>
                            @else
                                <button type="button" class="btn btn-secondary btn-sm" disabled title="Specialist assessments are not complete yet">
                                    <i class="fas fa-file-pdf"></i> Download PDF
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" style="text-align:center;">No completed forms found.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
@endsection

