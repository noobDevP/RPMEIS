@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/station-layout.css') }}">
@endpush

@section('content')
<div class="station-grid">
    <section class="card scanner-panel">
        <h2>Scan QR Code</h2>
        @yield('scanner')
    </section>

    <section class="card patient-panel">
        <h2>@yield('patient-panel-title', 'Patient Record')</h2>
        @yield('patient-panel')
    </section>
</div>

@yield('station-extra')
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
@stack('station-scripts')
@endpush
