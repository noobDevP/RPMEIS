document.addEventListener('DOMContentLoaded', function() {
    var adminUsername = document.getElementById('adminUsername');
    var form = document.querySelector('form');

    if (adminUsername) {
        adminUsername.addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
    }

    if (form) {
        form.addEventListener('submit', function() {
            var btn = form.querySelector('button[type="submit"]');
            if (btn && !btn.disabled) {
                setTimeout(function() {
                    btn.disabled = true;
                    btn.style.opacity = '0.7';
                    btn.style.cursor = 'not-allowed';
                }, 10);
            }
        });
    }
});
