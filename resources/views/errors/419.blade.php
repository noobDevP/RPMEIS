<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>419 - Session Expired | RPMEIS</title>
    <link rel="stylesheet" href="{{ asset('css/error.css') }}">
</head>
<body>
    <div class="error-card">
        <h1 class="error-code error-code-amber">419</h1>
        <div class="error-title">Session Expired</div>
        <p class="error-msg">Your security session or CSRF token has expired. Please refresh the page and sign in again.</p>
        <a href="/login" class="btn-home">Sign In</a>
    </div>
</body>
</html>
