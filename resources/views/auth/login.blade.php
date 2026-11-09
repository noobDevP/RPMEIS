<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Login - RPMEIS</title>
  {{-- Font & icons --}}
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <link rel="stylesheet" href="{{ asset('css/login.css') }}">
</head>
<body>
  <div class="login-card">

    {{-- brand: RPMEIS --}}
    @php
      $logoPath = public_path('images/Visayas.png');
      $logoSrc = file_exists($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : asset('images/Visayas.png');
    @endphp
    <div class="brand">
      <div class="brand-icon">
        <img src="{{ $logoSrc }}" alt="RPMEIS Logo" />
      </div>
      <div class="brand-name">RPMEIS</div>
    </div>

    {{-- greeting --}}
    <div class="greeting">
      <h2>Welcome Back</h2>
      <p>Sign in to your staff account</p>
    </div>

    {{-- login form --}}
    <form action="{{ route('login') }}" method="post" autocomplete="off">
      @csrf
      
      @if ($errors->any())
          <div class="error-message">{{ $errors->first() }}</div>
      @endif
      @if (session('warning'))
          <div class="error-message" style="background: #fffbeb; color: #b45309; border-color: #fde68a;">{{ session('warning') }}</div>
      @endif
      @if (session('status'))
          <div class="error-message" style="background: #eff6ff; color: #1e40af; border-color: #bfdbfe;">{{ session('status') }}</div>
      @endif

      {{-- username field --}}
      <div class="input-group">
        <label for="username">Username</label>
        <input type="text" name="username" id="username" class="input-field" placeholder="Enter your username" value="{{ old('username') }}" required />
        <i class="fas fa-user input-icon"></i>
      </div>

      {{-- password field --}}
      <div class="input-group">
        <label for="password">Password</label>
        <input type="password" name="password" id="password" class="input-field" placeholder="••••••••" required />
        <i class="fas fa-lock input-icon"></i>
      </div>

      <input type="hidden" name="user_username" id="user_username" readonly>

      {{-- login button --}}
      <button type="submit" class="login-btn">
        <span>Log In</span>
        <i class="fas fa-arrow-right"></i>
      </button>

    </form>
    
    <div class="version">
        Version 1.1 | Developed by MIB (PMC)
    </div>
  </div>

  <script src="{{ asset('js/login-lookup.js') }}"></script>
</body>
</html>
