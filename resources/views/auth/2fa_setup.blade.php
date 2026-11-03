<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Setup 2FA - RPMEIS</title>
  <!-- Font & icons -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(145deg, #f6f9fc 0%, #e9f0f5 100%);
      padding: 1.5rem;
      color: #1e2b36;
    }
    .login-card {
      width: 100%;
      max-width: 480px;
      background: rgba(255, 255, 255, 0.75);
      backdrop-filter: blur(6px);
      -webkit-backdrop-filter: blur(6px);
      border-radius: 40px;
      padding: 2.5rem 2.5rem 2.8rem;
      box-shadow: 0 20px 40px -12px rgba(0, 20, 30, 0.12), 0 8px 24px -6px rgba(0, 0, 0, 0.02), inset 0 1px 0 rgba(255, 255, 255, 0.6);
      border: 1px solid rgba(255, 255, 255, 0.5);
      transition: box-shadow 0.3s ease;
    }
    .login-card:hover { box-shadow: 0 28px 48px -16px rgba(0, 30, 40, 0.18); }
    .brand {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 12px;
      margin-bottom: 1.5rem;
    }
    .brand-icon {
      display: flex; align-items: center; justify-content: center;
      width: 48px; height: 48px;
      background: rgba(255,255,255,0.9);
      border-radius: 12px; padding: 4px;
    }
    .brand-icon img { height: 100%; width: 100%; object-fit: contain; }
    .brand-name {
      font-size: 2rem; font-weight: 500; letter-spacing: -0.02em;
      color: #1e2b36;
      background: linear-gradient(135deg, #1f2e3a, #2a3f4e);
      -webkit-background-clip: text; -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    .greeting { text-align: center; margin-bottom: 2rem; }
    .greeting h2 { font-size: 1.5rem; font-weight: 500; color: #1f2e3a; letter-spacing: -0.02em; margin-bottom: 6px; }
    .greeting p { font-size: 0.95rem; color: #62778b; font-weight: 400; letter-spacing: 0.01em; }
    
    .input-group { margin-bottom: 1.5rem; position: relative; }
    .input-group label {
      display: block; font-size: 0.8rem; font-weight: 500; text-transform: uppercase;
      letter-spacing: 0.04em; color: #4d6679; margin-bottom: 0.4rem; margin-left: 0.25rem;
    }
    .input-field {
      width: 100%; padding: 0.9rem 1rem 0.9rem 2.8rem;
      font-size: 1rem; font-weight: 400; font-family: 'Inter', sans-serif;
      background: rgba(255, 255, 255, 0.6);
      border: 1px solid rgba(180, 195, 210, 0.4);
      border-radius: 30px; color: #1c2a35;
      transition: all 0.2s ease; outline: none;
    }
    .input-field:focus {
      border-color: #8daec9; background: rgba(255, 255, 255, 0.8);
      box-shadow: 0 4px 12px rgba(60, 100, 130, 0.06), inset 0 1px 4px rgba(0,0,0,0.02);
    }
    .input-field::placeholder { color: #a6bacb; font-weight: 300; }
    .input-icon {
      position: absolute; left: 1rem; top: 2.25rem;
      color: #8ba3b5; font-size: 1.1rem; transition: color 0.2s; pointer-events: none;
    }
    .input-group:focus-within .input-icon { color: #2b4b5e; }
    
    .login-btn {
      width: 100%; padding: 0.9rem 1rem; border: 1px solid rgba(255, 255, 255, 0.15);
      border-radius: 40px; background: linear-gradient(135deg, #1e2f3c, #2a4050);
      color: #f2f7fc; font-size: 1rem; font-weight: 500; font-family: 'Inter', sans-serif;
      letter-spacing: 0.02em; cursor: pointer; transition: all 0.25s ease;
      box-shadow: 0 4px 14px rgba(30, 50, 65, 0.15);
      display: flex; align-items: center; justify-content: center; gap: 10px; margin-top: 1rem;
    }
    .login-btn i { font-size: 0.9rem; opacity: 0.7; transition: transform 0.2s ease; }
    .login-btn:hover {
      background: linear-gradient(135deg, #243b4a, #2f4b5f);
      box-shadow: 0 8px 24px rgba(30, 60, 80, 0.2); transform: scale(1.01); color: #ffffff;
    }
    .login-btn:hover i { transform: translateX(3px); }
    
    .error-msg {
      background: #fdf5f5; color: #c44; padding: 0.75rem 1rem;
      border-radius: 12px; font-size: 0.85rem; margin-bottom: 1.25rem;
      border: 1px solid rgba(204,68,68,0.15); display: flex; align-items: center; gap: 8px;
    }
    .error-msg i { color: #d55; }
    .warning-msg {
      background: #fcf8e3; color: #8a6d3b; padding: 0.75rem 1rem;
      border-radius: 12px; font-size: 0.85rem; margin-bottom: 1.25rem;
      border: 1px solid rgba(138,109,59,0.15); display: flex; align-items: center; gap: 8px;
    }
    .warning-msg i { color: #8a6d3b; }
    
    .qr-container {
      text-align: center; margin-bottom: 1.5rem; padding: 1.2rem;
      background: rgba(255, 255, 255, 0.9); border-radius: 16px;
      border: 1px solid rgba(180, 195, 210, 0.4);
    }
    .secret-code {
      margin-top: 0.8rem; font-family: monospace; font-size: 1.1rem;
      color: #4b5563; font-weight: 500;
    }
    .instructions {
      font-size: 0.85rem; color: #62778b; text-align: center;
      margin-bottom: 1.5rem; line-height: 1.5;
    }
    
    .footer-link { text-align: center; margin-top: 1.5rem; }
    .footer-link a { color: #62778b; font-size: 0.9rem; text-decoration: none; transition: color 0.2s; }
    .footer-link a:hover { color: #1f2e3a; }
  </style>
</head>
<body>

  <div class="login-card">
    <div class="brand">
      <div class="brand-icon">
        <img src="{{ asset('images/Visayas.png') }}" alt="Logo">
      </div>
      <div class="brand-name">RPMEIS</div>
    </div>

    <div class="greeting">
      <h2>Action Required</h2>
      <p>Please secure your account</p>
    </div>

    @if(session('warning'))
      <div class="warning-msg">
        <i class="fas fa-shield-alt"></i> {{ session('warning') }}
      </div>
    @endif

    @if($errors->any())
      <div class="error-msg">
        <i class="fas fa-exclamation-circle"></i> {{ $errors->first() }}
      </div>
    @endif

    <p class="instructions">
      Scan this QR code with your authenticator app (like Google Authenticator or Authy), then enter the 6-digit code below.
    </p>

    <div class="qr-container">
        {!! $qrCode !!}
        <div class="secret-code">
            {{ $secret }}
        </div>
    </div>

    <form method="POST" action="{{ route('2fa.enable') }}" id="loginForm">
      @csrf

      <div class="input-group">
        <label for="otp">Authenticator Code</label>
        <i class="fas fa-key input-icon"></i>
        <input type="text" name="otp" id="otp" class="input-field" 
               placeholder="123456" required autocomplete="off" autofocus />
      </div>

      <button type="submit" class="login-btn">
        Verify & Complete Setup <i class="fas fa-arrow-right"></i>
      </button>
    </form>
    
    <div class="footer-link">
        <a href="/logout"><i class="fas fa-sign-out-alt"></i> Log Out</a>
    </div>
  </div>

</body>
</html>
