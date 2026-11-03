<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>2FA Recovery Code Verification</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1e293b, #0f172a);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
        }
        .card {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            padding: 40px;
            border-radius: 16px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.4);
            text-align: center;
        }
        h2 {
            margin-top: 0;
            font-weight: 700;
            font-size: 24px;
            margin-bottom: 10px;
        }
        p {
            color: #94a3b8;
            font-size: 14px;
            margin-bottom: 24px;
            line-height: 1.5;
        }
        .input-group {
            margin-bottom: 20px;
        }
        input[type="text"] {
            width: 100%;
            padding: 14px;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            background: rgba(0, 0, 0, 0.25);
            color: #fff;
            font-size: 20px;
            text-align: center;
            letter-spacing: 3px;
            font-family: 'Courier New', Courier, monospace;
            font-weight: 700;
            text-transform: uppercase;
            box-sizing: border-box;
            outline: none;
        }
        input[type="text"]:focus {
            border-color: #38bdf8;
            box-shadow: 0 0 10px rgba(56, 189, 248, 0.3);
        }
        .btn {
            width: 100%;
            padding: 14px;
            background: #2563eb;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
            box-sizing: border-box;
        }
        .btn:hover {
            background: #1d4ed8;
        }
        .btn-secondary {
            display: block;
            margin-top: 12px;
            padding: 11px;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.25);
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            transition: background 0.2s;
        }
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        .error {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid #ef4444;
            color: #fca5a5;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .back-link {
            display: block;
            margin-top: 20px;
            color: #94a3b8;
            text-decoration: none;
            font-size: 13px;
        }
        .back-link:hover {
            color: #fff;
        }
        .hint-box {
            background: rgba(56, 189, 248, 0.1);
            border: 1px solid rgba(56, 189, 248, 0.25);
            color: #bae6fd;
            padding: 10px 12px;
            border-radius: 8px;
            font-size: 12px;
            margin-bottom: 18px;
            text-align: left;
            line-height: 1.45;
        }
    </style>
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
