<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>2FA Verification</title>
    <link rel="stylesheet" href="{{ asset('css/2fa.css') }}">
</head>
<body>
    <div class="card">
        <h2>Two-Factor Auth</h2>
        <p>Please enter the 6-digit verification code from your authenticator app.</p>

        @if ($errors->any())
            <div class="error">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('2fa.verify') }}">
            @csrf
            <div class="input-group">
                <input type="text" name="otp" maxlength="6" placeholder="000000" required autofocus autocomplete="one-time-code">
            </div>
            <button type="submit" class="btn">Verify</button>
        </form>

        <div class="recovery-footer">
            <p class="recovery-hint">Don't have your authenticator app?</p>
            <a href="{{ route('2fa.recovery') }}" class="btn-recovery">
                Use a Recovery Code
            </a>
        </div>

        <a href="{{ route('logout') }}" class="back-link">Cancel and return to login</a>
    </div>
</body>
</html>
