<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>2FA Recovery Code Verification</title>
    <link rel="stylesheet" href="{{ asset('css/2fa.css') }}">
</head>
<body>
    <div class="card">
        <h2>Use a Recovery Code</h2>
        <p>Enter one of your 8-character backup recovery codes to sign in without your authenticator app.</p>

        <div class="hint-box">
            <strong>Note:</strong> Each recovery code can only be used once. If you use your last remaining code, a new set of 8 codes will be generated automatically.
        </div>

        @if ($errors->any())
            <div class="error">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('2fa.recovery.verify') }}">
            @csrf
            <div class="input-group">
                <input
                    type="text"
                    name="recovery_code"
                    maxlength="11"
                    placeholder="XXXX-XXXX"
                    required
                    autofocus
                    autocomplete="off"
                >
            </div>
            <button type="submit" class="btn">Verify Recovery Code</button>
        </form>

        <a href="{{ route('2fa.challenge') }}" class="btn-secondary">
            Use 6-Digit Authenticator Code Instead
        </a>

        <a href="{{ route('logout') }}" class="back-link">Cancel and return to login</a>
    </div>
</body>
</html>
