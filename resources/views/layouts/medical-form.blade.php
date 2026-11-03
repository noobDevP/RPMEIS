@extends('layouts.portal')

@section('title', 'Medical Examination Form — AFPRG 165-3661')

@section('content')
<div class="container" style="width: 100%; max-width: 1400px;">
    <div class="page-header" style="margin-bottom: 2rem;">
        <div>
            <h1><i class="fas fa-file-medical" style="margin-right: 12px; opacity: 0.9;"></i> Medical Examination Form</h1>
            <p>AFPRG 165-3661 &bull; Official Standard Form</p>
        </div>
        <a href="{{ route('doctor.show', ['station' => 'gpe']) }}" class="btn btn-outline" style="border-color: rgba(255,255,255,0.4); color: white;">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    @yield('wizard-content')
</div>
@endsection
