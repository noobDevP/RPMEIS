document.addEventListener('DOMContentLoaded', function () {
    const copyBtn = document.getElementById('copyCodesBtn');
    const downloadBtn = document.getElementById('downloadCodesBtn');
    const printBtn = document.getElementById('printCodesBtn');
    const regenForm = document.getElementById('regenCodesForm');

    function getRecoveryCodesList() {
        return Array.from(document.querySelectorAll('.recovery-code-item'))
            .map(el => el.textContent.trim())
            .filter(Boolean);
    }

    if (copyBtn) {
        copyBtn.addEventListener('click', function () {
            const codes = getRecoveryCodesList();
            if (!codes.length) return;
            const text = "PME Portal 2FA Backup Recovery Codes:\n" + codes.join("\n");
            navigator.clipboard.writeText(text).then(() => {
                const label = document.getElementById('copyCodesBtnText');
                if (label) {
                    label.textContent = 'Copied!';
                    setTimeout(() => { label.textContent = 'Copy Codes'; }, 2000);
                }
            });
        });
    }

    if (downloadBtn) {
        downloadBtn.addEventListener('click', function () {
            const codes = getRecoveryCodesList();
            if (!codes.length) return;
            const content = [
                "==========================================",
                "  PME PORTAL - 2FA BACKUP RECOVERY CODES  ",
                "==========================================",
                "Generated: " + new Date().toLocaleString(),
                "Important: Store these in a secure place.",
                "Each code can only be used once to sign in.",
                "------------------------------------------",
                ...codes.map((c, i) => (i + 1) + ". " + c),
                "=========================================="
            ].join("\r\n");

            const blob = new Blob([content], { type: "text/plain;charset=utf-8" });
            const url = URL.createObjectURL(blob);
            const a = document.createElement("a");
            a.href = url;
            a.download = "pme-portal-2fa-recovery-codes.txt";
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        });
    }

    if (printBtn) {
        printBtn.addEventListener('click', function () {
            window.print();
        });
    }

    if (regenForm) {
        regenForm.addEventListener('submit', function (e) {
            if (!confirm('Generate 8 new recovery codes? This will immediately invalidate your current unused codes.')) {
                e.preventDefault();
            }
        });
    }
});
