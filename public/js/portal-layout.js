document.addEventListener('DOMContentLoaded', function () {
    // Sidebar Toggle
    const hamburgerBtn = document.getElementById('hamburgerBtn');
    if (hamburgerBtn) {
        hamburgerBtn.addEventListener('click', () => {
            const sidebar = document.getElementById('sidebar');
            if (sidebar) sidebar.classList.toggle('open');
        });
    }

    // Modal triggers
    const openBtns = document.querySelectorAll('[data-action="open-layout-password-modal"]');
    openBtns.forEach(btn => btn.addEventListener('click', openLayoutPasswordModal));

    const closeBtns = document.querySelectorAll('[data-action="close-layout-password-modal"]');
    closeBtns.forEach(btn => btn.addEventListener('click', closeLayoutPasswordModal));

    const submitBtn = document.getElementById('submitLayoutPasswordBtn');
    if (submitBtn) {
        submitBtn.addEventListener('click', submitLayoutPassword);
    }

    // Global delegation for modal triggers
    document.addEventListener('click', function (e) {
        const openTrigger = e.target.closest('[data-action="open-layout-password-modal"]');
        if (openTrigger) {
            e.preventDefault();
            openLayoutPasswordModal();
            return;
        }
        const closeTrigger = e.target.closest('[data-action="close-layout-password-modal"]');
        if (closeTrigger) {
            e.preventDefault();
            closeLayoutPasswordModal();
            return;
        }
    });
});

function openLayoutPasswordModal(e) { 
    if (e && typeof e.preventDefault === 'function') e.preventDefault();
    const modal = document.getElementById('layoutPasswordModal');
    if (modal) modal.classList.add('active'); 
}

function closeLayoutPasswordModal(e) { 
    if (e && typeof e.preventDefault === 'function') e.preventDefault();
    const modal = document.getElementById('layoutPasswordModal');
    if (modal) modal.classList.remove('active'); 
}

function submitLayoutPassword() {
    const p = document.getElementById('layoutPwNew')?.value.trim() || '';
    const c = document.getElementById('layoutPwConfirm')?.value.trim() || '';
    const msg = document.getElementById('layoutPwMsg');
    
    if (p !== '' && p !== c) { 
        if (msg) {
            msg.style.display = 'block'; 
            msg.style.background = '#fee2e2'; 
            msg.style.color = '#991b1b'; 
            msg.style.border = '1px solid rgba(239, 68, 68, 0.3)';
            msg.textContent = 'Passwords do not match!'; 
        }
        return; 
    }
    
    const fd = new FormData();
    fd.append('new_password', p);
    fd.append('user_username', document.getElementById('layoutDisplayName') ? document.getElementById('layoutDisplayName').value : '');
    
    if (document.getElementById('layoutRank')) fd.append('rank', document.getElementById('layoutRank').value);
    if (document.getElementById('layoutAfpos')) fd.append('afpos', document.getElementById('layoutAfpos').value);
    if (document.getElementById('layoutStatus')) fd.append('status', document.getElementById('layoutStatus').value);
    if (document.getElementById('layoutPosition')) fd.append('position', document.getElementById('layoutPosition').value);
    
    if (document.getElementById('layoutCredName')) fd.append('credential_name', document.getElementById('layoutCredName').value);
    if (document.getElementById('layoutCredTitle')) fd.append('credential_title', document.getElementById('layoutCredTitle').value);
    if (document.getElementById('layoutCredLicense')) fd.append('credential_license', document.getElementById('layoutCredLicense').value);
    if (document.getElementById('layoutCredStatus')) fd.append('credential_status', document.getElementById('layoutCredStatus').value);
    
    if (document.getElementById('layoutESignature') && document.getElementById('layoutESignature').files[0]) {
        fd.append('e_signature', document.getElementById('layoutESignature').files[0]);
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const targetUrl = document.getElementById('layoutPasswordModal')?.dataset?.changePasswordUrl || '/password/change';
    
    fetch(targetUrl, { 
        method: 'POST', 
        body: fd, 
        headers: { 'X-CSRF-TOKEN': csrfToken } 
    })
    .then(r => r.json())
    .then(d => {
        if (!msg) return;
        msg.style.display = 'block';
        if (d.status === 'success' || d.success) { 
            msg.style.background = '#dcfce7'; 
            msg.style.color = '#166534'; 
            msg.style.border = '1px solid #a7f3d0';
            msg.textContent = 'Password updated successfully!'; 
            setTimeout(() => closeLayoutPasswordModal(), 2000); 
        } else { 
            msg.style.background = '#fee2e2'; 
            msg.style.color = '#991b1b'; 
            msg.textContent = d.message || 'Error occurred'; 
        }
    }).catch(() => { 
        if (!msg) return;
        msg.style.display = 'block'; 
        msg.style.background = '#fee2e2'; 
        msg.style.color = '#991b1b'; 
        msg.textContent = 'Network error'; 
    });
}

window.openLayoutPasswordModal = openLayoutPasswordModal;
window.closeLayoutPasswordModal = closeLayoutPasswordModal;
window.submitLayoutPassword = submitLayoutPassword;
