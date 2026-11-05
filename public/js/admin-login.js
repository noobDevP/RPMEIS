document.addEventListener('DOMContentLoaded', function() {
    var adminUsername = document.getElementById('adminUsername');
    if (adminUsername) {
        adminUsername.addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
    }
});
