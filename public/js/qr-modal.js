function showQrModal(patient) {
    const overlay = document.getElementById('qrModalOverlay');
    const modal = document.getElementById('qrModal');
    if (overlay) overlay.style.display = 'block';
    if (modal) modal.style.display = 'block';
    
    const qrContainer = document.getElementById('qrcode-container');
    if (!qrContainer) return;
    qrContainer.innerHTML = '';
    
    const data = JSON.stringify({
        patient_no: patient.patient_no
    });
    
    if (window.QRCode) {
        new QRCode(qrContainer, {
            text: data,
            width: 200,
            height: 200,
            colorDark : "#000000",
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.H
        });
    }
}

function closeQrModal() {
    const overlay = document.getElementById('qrModalOverlay');
    const modal = document.getElementById('qrModal');
    if (overlay) overlay.style.display = 'none';
    if (modal) modal.style.display = 'none';
}

document.addEventListener('DOMContentLoaded', function() {
    const overlay = document.getElementById('qrModalOverlay');
    if (overlay) {
        overlay.addEventListener('click', closeQrModal);
    }
    const closeBtns = document.querySelectorAll('.qr-modal-close-btn, #closeQrModalBtn');
    closeBtns.forEach(btn => btn.addEventListener('click', closeQrModal));
});

window.showQrModal = showQrModal;
window.closeQrModal = closeQrModal;
