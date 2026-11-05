<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Setup 2FA - RPMEIS</title>
  {{-- Font & icons --}}
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <link rel="stylesheet" href="{{ asset('css/2fa-setup.css') }}">
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
        {!! $qrCode ?? $qrCodeSvg ?? '' !!}
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
