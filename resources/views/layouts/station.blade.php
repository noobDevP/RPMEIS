@extends('layouts.app')

@push('styles')
<style>
    .station-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }

    @media (max-width: 900px) {
        .station-grid {
            grid-template-columns: 1fr;
        }
    }

    .scanner-panel video,
    .scanner-panel canvas {
        width: 100%;
        max-width: 480px;
        border-radius: 8px;
        background: #000;
    }

    .scanner-panel {
        text-align: center;
    }

    .patient-panel table {
        width: 100%;
        border-collapse: collapse;
    }

    .patient-panel th,
    .patient-panel td {
        border: 1px solid #ddd;
        padding: 8px 10px;
        text-align: left;
        font-size: 0.9rem;
    }

    .patient-panel th {
        background: #eef2f7;
    }
</style>
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
