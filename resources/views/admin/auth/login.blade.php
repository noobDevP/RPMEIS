<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Admin Login - RPMEIS</title>
  
  {{-- Font & Icons --}}
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  
  <link rel="stylesheet" href="{{ asset('css/admin-login.css') }}">
</head>
<body>

<main class="login-wrapper">
  {{-- Brand Header --}}
  @php
    $logoPath = public_path('images/Visayas.png');
    $logoSrc = file_exists($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : asset('images/Visayas.png');
  @endphp
  <header class="brand">
    <img src="{{ $logoSrc }}" alt="RPMEIS Logo" class="brand-logo" />
    <h1>RPMEIS <span>·</span> <span class="brand-subtitle">Admin</span></h1>
  </header>

  {{-- Admin Badge --}}
  <div class="admin-badge">
    <i class="fas fa-shield-alt"></i> Restricted Access
  </div>

  {{-- Login Form --}}
  <form action="{{ route('admin.login.store') }}" method="post">
    @csrf

    @error('login')
        <div class="error-message">{{ $message }}</div>
    @enderror

    <div class="form-group">
      <label for="adminUsername">
        <i class="fas fa-user-shield"></i> Username
      </label>
      <div class="input-wrap">
        <i class="fas fa-user-shield"></i>
        <input type="text" name="username" id="adminUsername" placeholder="Enter admin username" value="{{ old('username') }}" required />
      </div>
    </div>
    
    <div class="form-group">
      <label for="adminPassword">
        <i class="fas fa-lock"></i> Password
      </label>
      <div class="input-wrap">
        <i class="fas fa-lock"></i>
        <input type="password" name="password" id="adminPassword" placeholder="••••••••" required />
      </div>
    </div>

    <button type="submit" class="login-btn">
      <span>Admin Sign In</span> <i class="fas fa-arrow-right"></i>
    </button>
  </form>

  {{-- Footer --}}
  <footer class="footer-note">
    <i class="fas fa-lock"></i> RPMEIS © APMC 2026
  </footer>
</main>

  <script src="{{ asset('js/admin-login.js') }}"></script>
</body>
</html>
