@extends('layouts.guest')

@section('title', 'Masuk')

@section('content')
    <div class="text-center mb-4">
        <div class="d-inline-flex p-2 rounded-4 bg-primary bg-opacity-10 mb-3 shadow-xs">
            <img src="{{ asset('images/logo-icon.png') }}" alt="{{ config('jhs.name') }}" height="56" class="d-inline-block">
        </div>
        <h1 class="h4 fw-bold mb-1">Masuk ke Sistem</h1>
        <p class="text-secondary small mb-0">Silakan masukkan email dan kata sandi Anda</p>
    </div>

    <form method="POST" action="{{ route('login') }}" novalidate>
        @csrf

        <div class="mb-3">
            <label for="email" class="form-label">Email Pengguna</label>
            <div class="jhs-input-group @error('email') is-invalid @enderror">
                <span class="jhs-input-group-icon"><i class="bi bi-envelope"></i></span>
                <input type="email" name="email" id="email" value="{{ old('email') }}"
                       class="form-control"
                       placeholder="nama@josjishospital.id" required autofocus autocomplete="username">
            </div>
            @error('email')
                <div class="text-danger small mt-1"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <label for="password" class="form-label mb-0">Kata Sandi</label>
                <a href="{{ route('password.request') }}" class="small text-decoration-none" tabindex="-1">Lupa sandi?</a>
            </div>
            <div class="jhs-input-group @error('password') is-invalid @enderror">
                <span class="jhs-input-group-icon"><i class="bi bi-lock"></i></span>
                <input type="password" name="password" id="password"
                       class="form-control"
                       placeholder="Masukkan kata sandi" required autocomplete="current-password">
                <button class="jhs-password-toggle" type="button" id="togglePassword" title="Lihat/Sembunyikan sandi" tabindex="-1">
                    <i class="bi bi-eye" id="togglePasswordIcon"></i>
                </button>
            </div>
            @error('password')
                <div class="text-danger small mt-1"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>
            @enderror
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="remember" id="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                <label class="form-check-label small text-secondary" for="remember">Ingat sesi saya</label>
            </div>
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold shadow-sm">
            <i class="bi bi-box-arrow-in-right me-2"></i>Masuk ke Sistem
        </button>
    </form>

    <div class="text-center mt-3 pt-3 border-top">
        <p class="small text-secondary mb-0">Belum punya akun pasien? <a href="{{ route('register') }}" class="fw-semibold text-primary text-decoration-none">Daftar sekarang</a></p>
    </div>

    @if (config('jhs.demo_credentials_enabled'))
        <div class="jhs-demo-box jhs-demo-credentials mt-4">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="small fw-bold text-dark">
                    <i class="bi bi-person-badge text-primary me-1"></i>Akun Demo Uji Coba
                </span>
                <span class="badge bg-primary bg-opacity-10 text-primary" style="font-size: 0.68rem;">Klik untuk Isi Otomatis</span>
            </div>
            <p class="text-secondary small mb-2" style="font-size: 0.76rem;">Pilih peran di bawah untuk login cepat saat demonstrasi / penilaian:</p>
            <div class="d-flex flex-wrap gap-1">
                @php
                    $roleMeta = [
                        'Admin' => ['icon' => 'bi-shield-lock-fill', 'color' => 'primary'],
                        'Resepsionis' => ['icon' => 'bi-headset', 'color' => 'info'],
                        'Dokter' => ['icon' => 'bi-heart-pulse-fill', 'color' => 'success'],
                        'Apoteker' => ['icon' => 'bi-capsule', 'color' => 'warning'],
                        'Pasien' => ['icon' => 'bi-person-fill', 'color' => 'secondary'],
                    ];
                @endphp
                @foreach (config('jhs.demo_accounts') as $role => $account)
                    @php $meta = $roleMeta[$role] ?? ['icon' => 'bi-person', 'color' => 'secondary']; @endphp
                    <button type="button" class="jhs-demo-btn js-fill-demo"
                            data-email="{{ $account['email'] }}" data-password="{{ $account['password'] }}"
                            data-role="{{ $role }}">
                        <i class="bi {{ $meta['icon'] }} text-{{ $meta['color'] }}"></i>
                        <span>{{ $role }}</span>
                    </button>
                @endforeach
            </div>
            <div id="demoSelectedHint" class="small text-primary mt-2 d-none" style="font-size: 0.74rem;">
                <i class="bi bi-check-circle-fill me-1"></i>Akun <strong id="demoRoleName"></strong> terisi otomatis.
            </div>
        </div>
    @endif
@endsection

@push('scripts')
<script>
    // Toggle password visibility
    const toggleBtn = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.getElementById('togglePasswordIcon');
    if (toggleBtn && passwordInput && toggleIcon) {
        toggleBtn.addEventListener('click', function () {
            const isPassword = passwordInput.getAttribute('type') === 'password';
            passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
            toggleIcon.classList.toggle('bi-eye', !isPassword);
            toggleIcon.classList.toggle('bi-eye-slash', isPassword);
        });
    }

    // Fill demo credentials
    const hintBox = document.getElementById('demoSelectedHint');
    const roleNameSpan = document.getElementById('demoRoleName');
    document.querySelectorAll('.js-fill-demo').forEach(function (button) {
        button.addEventListener('click', function () {
            document.querySelectorAll('.js-fill-demo').forEach(b => b.classList.remove('active'));
            button.classList.add('active');

            document.getElementById('email').value = button.dataset.email;
            document.getElementById('password').value = button.dataset.password;

            if (hintBox && roleNameSpan) {
                roleNameSpan.textContent = button.dataset.role;
                hintBox.classList.remove('d-none');
            }
        });
    });
</script>
@endpush