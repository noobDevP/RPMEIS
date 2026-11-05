document.addEventListener('DOMContentLoaded', function() {
    var passwordInput = document.getElementById('password');
    var usernameInput = document.getElementById('username');
    var userUsernameInput = document.getElementById('user_username');

    if (!passwordInput || !usernameInput) {
        return;
    }

    usernameInput.addEventListener('input', function() {
        this.value = this.value.toUpperCase();
    });

    passwordInput.addEventListener('input', function() {
        var username = usernameInput.value;
        var password = this.value;

        if (!username || !password) {
            return;
        }

        var metaCsrf = document.querySelector('meta[name="csrf-token"]');
        var csrfToken = metaCsrf ? metaCsrf.getAttribute('content') : '';

        fetch('/auth/lookup-display-name', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: 'username=' + encodeURIComponent(username) + '&password=' + encodeURIComponent(password),
        })
        .then(function(response) { return response.text(); })
        .then(function(data) {
            if (userUsernameInput) {
                userUsernameInput.value = data;
            }
        })
        .catch(function(error) {
            console.error('Error:', error);
        });
    });
});
