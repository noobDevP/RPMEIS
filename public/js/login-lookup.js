document.addEventListener('DOMContentLoaded', function() {
    var usernameInput = document.getElementById('username');
    var form = document.querySelector('form');

    if (usernameInput) {
        usernameInput.addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
    }

    if (form) {
        form.addEventListener('submit', function() {
            var btn = form.querySelector('button[type="submit"]');
            if (btn && !btn.disabled) {
                // Prevent duplicate form submissions while request is in-flight
                setTimeout(function() {
                    btn.disabled = true;
                    btn.style.opacity = '0.7';
                    btn.style.cursor = 'not-allowed';
                }, 10);
            }
        });
    }
});
