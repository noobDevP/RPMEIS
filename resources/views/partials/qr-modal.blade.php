<link rel="stylesheet" href="{{ asset('css/qr-modal.css') }}">

<div id="qrModalOverlay"></div>
<div id="qrModal">
    <span class="qr-modal-close-btn">&times;</span>
    <h3 id="qrModalTitle" class="qr-modal-title">Applicant QR Code</h3>
    <div id="qrcode-container"></div>
    <button type="button" class="btn btn-primary" id="closeQrModalBtn" style="margin-top:10px;">Close</button>
</div>

<script src="{{ asset('js/vendor/qrcode-1.0.0.min.js') }}"></script>
<script src="{{ asset('js/qr-modal.js') }}" defer></script>
